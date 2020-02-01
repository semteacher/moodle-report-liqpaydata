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
 * @copyright 2018 Andrii Semenets
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

define('PAYMENTS_ALL',          1);
define('PAYMENTS_ONETIME',      2);
define('PAYMENTS_SUBSCRIPTION', 3);
define('PAYMENTS_ISTALLMENT',   4);
define('REPORT_PER_USER',    '/report/liqpaydata/mypayments.php');
define('REPORT_ALL',         '/report/liqpaydata/allpayments.php');

//table calss definition
class report_mypayments extends table_sql
{
    
    private $show_all_users = false;
    private $report_filename = REPORT_PER_USER;
    
    var $chargetypes = array('update.succeeded'=>'Charge Succeeded', 'succeeded' => 'Charge Succeeded', 'paid' => 'Charge Succeeded', 'refunded' => 'Charge Refunded', 'failed' => 'Charge Failed', 'update.failed' => 'Charge Failed', 'update.missed' => 'Charge Not Found during update', 'update.cancelled'=>'Subscription Canceled', 'invoice.payment_succeeded' => 'Invoice: Payment Succeeded', 'invoice.payment_failed' => 'Invoice: Payment Failed', 'transfer.paid' => 'Transfer Paid');
    
    function __construct($uniqueid, $showallusers=false)
    {
        parent::__construct($uniqueid);
        $this->show_all_users = $showallusers;
        $this->sort_default_column = 'timeupdated';
        $this->sort_default_order  = SORT_DESC;

        $columns = array(
            'timeupdated'      => 'Enrolment date', 
            'item_name'        => 'Course',
            'subscription'     => 'Subscription', 
            'quantity'         => 'Qty', 
            'cost'             => 'Amount',
            'tax_percent'      => 'Tax %',
            'tax'              => 'Tax',
            //'total'            => 'Total',
            'lastrunstatus'    => 'Operaion type',
            'payment_gross'    => 'Total',
            'payment_currency' => 'Currency',            
            'lastrunstatus'    => 'Last charge status',
            'alreadyruns'      => 'Times paid'
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
        return userdate($row->timeupdated, '%b %e, %Y');
    }

    function col_cost($row)
    {
        return \enrol_stripe\purchase::getCurrencySymbol($row->payment_currency).$row->cost;
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

    //provide title of subscription plan if exist
    function col_subscription($row) 
    {
        $subscrtext = \enrol_stripe\purchase::getFormattedSubcriptionTermName($row);
        $subscrstatus = \enrol_stripe\purchase::getLocalSubcriptionStatus($row);
        $ismanager = \enrol_stripe\purchase::hasUnenrolCapability($row->courseid);
        if ($row->installment > 0 || $row->subscription > 0){
            if ($subscrstatus) {
                $subscrtext .= ' - Charged';
                if ($ismanager) {
                    $cancelurl = new moodle_url($this->report_filename, array('userid' => $row->userid, 'enrolstripeid' => $row->esid, 'courseid' => $row->courseid, 'cancelcharge'=> 1));
                    $subscrtext .= '<br><a href="' . $cancelurl . '">Cancel</a>';
                }
            } else {
                $coursecontext = \context_course::instance($row->courseid);
                if (is_enrolled($coursecontext, $row->userid, '', true)) {
                    $subscrtext .= ' - Canceled';
                    if ($ismanager) {
                        $suspendurl = new moodle_url($this->report_filename, array('userid' => $row->userid, 'enrolstripeid' => $row->esid, 'courseid' => $row->courseid, 'suspendenrollment'=> 1));
                        $subscrtext .= '<br><a href="' . $suspendurl . '">Suspend enrollment</a>';
                    }                    
                } else {
                    $subscrtext .= ' - Canceled, suspended';
                }

            }
        }
        return $subscrtext;
    }

    //make currency to capital letter
    function col_charge_currency($row) 
    {
        return strtoupper($row->charge_currency);
    }
    
    //Show readable labels instead of statuses if exist
    function col_lastrunstatus($row) 
    {
        if (array_key_exists($row->lastrunstatus, $this->chargetypes)){
            $invoicetypelabel = $this->chargetypes[$row->lastrunstatus];
        } else {
            $invoicetypelabel = $row->lastrunstatus;
        }
        //make all available types as links
        if ( !$this->is_downloading()&& ($row->lastrunstatus=='update.succeeded'||$row->lastrunstatus=='succeeded'||$row->lastrunstatus=='paid')) {
            $invoicepdfurl = new moodle_url(REPORT_PER_USER, array('userid' => $row->userid, 'invoiceid' => $row->esid, 'invoicetype'=> $row->lastrunstatus));
            return '<a href="' . $invoicepdfurl . '">' . $invoicetypelabel . '</a>';
        } else {
            return $invoicetypelabel;
        }
    }
    
    function col_alreadyruns($row) 
    {
        if ($row->installment > 0 || $row->subscription == 0){
            return $row->alreadyruns . ' of ' . $row->totalruns;
        } else {
            return $row->alreadyruns;
        }
    }
}