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


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

define('PAYMENTS_ALL',          1);
define('PAYMENTS_ONETIME',      2);
define('PAYMENTS_SUBSCRIPTION', 3);
define('REPORT_PER_USER',    '/report/liqpaydata/mypayments.php');
define('REPORT_ALL',         '/report/liqpaydata/allpayments.php');

//table calss definition
class report_mypayments extends table_sql
{
    
    private $show_all_users = false;
    private $report_filename = REPORT_PER_USER;
    
    function __construct($uniqueid, $showallusers=false)
    {
        parent::__construct($uniqueid);
        $this->show_all_users = $showallusers;
        $this->sort_default_column = 'timeupdated';
        $this->sort_default_order  = SORT_DESC;

        $columns = array(
            'timeupdated'      => 'Enrolment date', 
            'enroll_satus'     => 'Enroll status', 
            'item_name'        => 'Course',
            'amount'           => 'Price',
            'payment_type'     => 'Payment Type',
            'amount_debit'     => 'User payed',
            'commission_debit' => 'User\'s comission',
            'commission_credit'=> 'Receiver\'s comission',
            'amount_credit'    => 'Received',
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
    }

    function col_userid($row)
    {
        $user = \core_user::get_user($row->userid);
        if (!$user || !core_user::is_real_user($row->userid)) {
            throw new moodle_exception('invaliduser', 'error');
        }
        $userurl = new moodle_url(REPORT_PER_USER, array('userid' => $row->userid));
        return '<a href="' . $userurl . '">' . fullname($user) . '</a>';
    }

    function col_timeupdated($row)
    {
        return userdate($row->timeupdated, '%H:%M, %b %e, %Y');
    }

    // price + currency
    function col_amount($row) 
    {
        return strval($row->amount) .' '. strtoupper($row->currency);
    }
    function col_amount_debit($row) 
    {
        return strval($row->amount_debit) .' '. strtoupper($row->currency_debit);
    }
    function col_commission_debit($row) 
    {
        return strval($row->commission_debit) .' '. strtoupper($row->currency_debit);
    }
    function col_amount_credit($row) 
    {
        return strval($row->amount_credit - $row->commission_credit) .' '. strtoupper($row->currency_credit);
    }
    function col_commission_credit($row) 
    {
        return strval($row->commission_credit) .' '. strtoupper($row->currency_credit);
    }

    //Make "course name" a link
    function col_item_name($row) 
    {
        if ( ! $this->is_downloading()) {
            return '<a href="' . new moodle_url('/course/view.php',
                    ['id' => $row->courseid]) . '">' . $row->item_name . '</a>';
        } else {
            $row->item_name;
        }
    }

    //manage enroll_satus
    function col_enroll_satus($row) 
    {
        $coursecontext = \context_course::instance($row->courseid);
        if (is_enrolled($coursecontext, $row->userid, '', true) && isset($row->userenrollmentid)) {
            $subscrtext = '<strong>Active</strong>';

            if (has_capability('enrol/liqpay:unenrol', $coursecontext)) {
                $suspendurl = new moodle_url($this->report_filename, array('userid' => $row->userid, 'courseid' => $row->courseid, 'enrolmentstatuschange'=> ENROL_USER_SUSPENDED));
                $subscrtext .= '<br><a href="' . $suspendurl . '">(Suspend)</a>';
            }                 
        } elseif (is_enrolled($coursecontext, $row->userid, '', false) && isset($row->userenrollmentid)) {
            $subscrtext = '<strong>Suspended</strong>';

            if (has_capability('enrol/liqpay:unenrol', $coursecontext)) {
                $suspendurl = new moodle_url($this->report_filename, array('userid' => $row->userid, 'courseid' => $row->courseid, 'enrolmentstatuschange'=> ENROL_USER_ACTIVE));
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
}