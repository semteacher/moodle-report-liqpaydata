<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Base class for the table used by a {@link quiz_attempts_report}.
 *
 * @package   report_liqpaydata
 * @copyright 2020 Andrii Semenets
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_liqpaydata\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/report/liqpaydata/locallib.php');

//table calss definition
class mypayments extends \table_sql
{

    private $show_all_users = false;
    private $report_filename = REPORT_PER_USER;
    
    function __construct($uniqueid, $showallusers=false, $paymenttypeid=PAYMENTS_ALL, $courseid=null, $userid=null)
    {
        global $USER;

        parent::__construct($uniqueid);
        $this->show_all_users = $showallusers;
        $this->paymenttypeid = $paymenttypeid;
        $this->courseid = $courseid;
        if (!$showallusers && empty($userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid = $userid;
        }
        $this->sort_default_column = 'timeupdated';
        $this->sort_default_order  = SORT_DESC;

        $columns = array(
            'timeupdated'      => 'Enrolment date', 
            'enroll_satus'     => 'Enroll status', 
            'item_name'        => 'Course',
            'payment_type'     => 'Payment Type',
            'amount_debit'     => 'User payed',
            'commission_debit' => 'User\'s comission',
            'currency_debit'   => 'User\'s currency',
            'amount'           => 'Price',
            'commission_credit'=> 'Receiver\'s comission',
            'amount_credit'    => 'Received',
            'currency_credit'  => 'Currency',
            'payment_status'   => 'Payment Status',
            'err_code'         => 'Error Code',
            'liqpay_order_id'  => 'Liqpay order'
        );
        if ($this->show_all_users) {
            $columns = array_merge(array('userid' => 'User'), $columns);
            $this->report_filename = REPORT_ALL;
        }
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));

        //list of fields to retreive
        $fields = 'el.id as elid, el.timeupdated, el.courseid, el.item_name, el.amount, el.currency, el.userid, el.payment_type, el.amount_debit, el.currency_debit, el.commission_debit, el.amount_credit, el.currency_credit, el.commission_credit, el.liqpay_order_id, el.payment_status, el.description,  el.err_code, el.userenrollmentid';
        $from  = "{enrol_liqpay} el";

        //prepare for filtering by paymet type
        if ($paymenttypeid == PAYMENTS_SUBSCRIPTION) {
            $wherepaymenttype = " and el.payment_type = 'subscribe' ";
        } elseif ($paymenttypeid == PAYMENTS_ONETIME) {
            $wherepaymenttype = " and el.payment_type = 'buy' ";
        } else {
            $wherepaymenttype = "";  //show all
        }
        if ($this->show_all_users) {
            $where = "el.userid > 0" . $wherepaymenttype;
        } else {
            $where = "el.userid = $this->userid" . $wherepaymenttype;
        }
        $this->set_count_sql("SELECT COUNT(DISTINCT(el.id)) FROM {$from} WHERE {$where}", array());
        $this->set_sql($fields, $from, $where, array());

        $this->no_sorting('enroll_satus'); // force to avoid errors on sorting!
    }

    function col_userid($row)
    {
        $user = \core_user::get_user($row->userid);
        if (!$user || !\core_user::is_real_user($row->userid)) {
            throw new \moodle_exception('invaliduser', 'error');
        }
        if (!$this->is_downloading()){
            $userurl = new \moodle_url(REPORT_PER_USER, array('userid' => $row->userid));
            return '<a href="' . $userurl . '">' . fullname($user) . '</a>';
        } else {
            return fullname($user);
        }

    }

    function col_timeupdated($row)
    {
        return userdate($row->timeupdated, '%H:%M, %b %e, %Y');
    }

    // price + currency
    function col_amount($row) 
    {
        return strval($row->amount);
    }
    function col_amount_debit($row) 
    {
        return strval($row->amount_debit);
    }
    function col_commission_debit($row) 
    {
        return strval($row->commission_debit);
    }
    function col_amount_credit($row) 
    {
        return strval($row->amount_credit - $row->commission_credit);
    }
    function col_commission_credit($row) 
    {
        return strval($row->commission_credit);
    }

    //Make "course name" a link
    function col_item_name($row) 
    {
        if ( !$this->is_downloading()) {
            return '<a href="' . new \moodle_url('/course/view.php',
                    ['id' => $row->courseid]) . '">' . $row->item_name . '</a>';
        } else {
            return $row->item_name;
        }
    }

    //manage enroll_satus
    function col_enroll_satus($row) 
    {
        $coursecontext = \context_course::instance($row->courseid);
        if (is_enrolled($coursecontext, $row->userid, '', true) && isset($row->userenrollmentid)) {
            if (!$this->is_downloading()) {
                $subscrtext = '<strong>Active</strong>';
                if (has_capability('enrol/liqpay:unenrol', $coursecontext)) {
                    $suspendurl = new \moodle_url($this->baseurl, array('enroluserid' => $row->userid, 'enrolcourseid' => $row->courseid, 'enrolmentstatuschange'=> ENROL_USER_SUSPENDED));
                    $subscrtext .= '<br><a href="' . $suspendurl . '">(Suspend)</a>';
                }
            } else {
                $subscrtext = 'Active';
            }
        } elseif (is_enrolled($coursecontext, $row->userid, '', false) && isset($row->userenrollmentid)) {
            $subscrtext = 'Suspended';
            if (!$this->is_downloading() && has_capability('enrol/liqpay:unenrol', $coursecontext)) {
                $suspendurl = new \moodle_url($this->baseurl, array('enroluserid' => $row->userid, 'enrolcourseid' => $row->courseid, 'enrolmentstatuschange'=> ENROL_USER_ACTIVE));
                $subscrtext .= '<br><a href="' . $suspendurl . '">(Activate)</a>';
            }
        } else {
            if(isset($row->userenrollmentid) || $row->payment_status == 'success') {
                $subscrtext = 'Unenroled';
            } else {
                $subscrtext = 'N/a';
            }
        }

        return $subscrtext;
    }

    function get_liqpay_success_totals()
    {
        global $DB;

        return $DB->get_record_sql("
            SELECT COUNT(el.id) AS cnt, SUM(el.amount_credit) AS payment_gross, MAX(el.currency_credit) AS currency, 
                    SUM(el.commission_credit) AS commission 
            FROM {$this->sql->from}
            WHERE (el.payment_status = 'success') and {$this->sql->where} 
                                      ", $this->sql->params);
    }

    function get_payment_option_names()
    {
        return array(
                    PAYMENTS_ALL=>'All', 
                    PAYMENTS_ONETIME=>'One time payments', 
                    PAYMENTS_SUBSCRIPTION=>'Subscriptions'
                    );
    }
    
    function out_filter_form()
    {
        global $PAGE;

    echo '<form action="'.s($this->baseurl->out(false)).'" method="get" id="paymentsfilterform">';
    echo '<div>';
    if (isset($this->courseid)){
        echo '<input type="hidden" name="courseid" value="'.$this->courseid.'" />';
    }
    if (isset($this->userid)){
        echo '<input type="hidden" name="userid" value="'.$this->userid.'" />';
    }
    echo \html_writer::tag('label', 'Display payments of the following type:&nbsp;', array('for' => 'paymenttypeselect'));
    echo \html_writer::select($this->get_payment_option_names(), 'paymenttypeid', $this->paymenttypeid, array(), array('id' => 'paymenttypeselect'));
    echo '<noscript style="display:inline">';
    echo '<div><input type="submit" value="'.\get_string('ok').'" /></div>';
    echo '</noscript>';
    echo '</div>';
    echo '</form>';
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $('#paymenttypeselect').change(function(e) {
                $('form#paymentsfilterform').submit();
            });
        });");
    }
}