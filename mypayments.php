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
 * User LiqPay Payments.
 *
 * @package    report_liqpaydata
 * @copyright  2018 Andrii Sements - LearnFormulaFMCorz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require('../../config.php');
require_once($CFG->dirroot . '/report/liqpaydata/mypayments.class.php');

// reference the Mpdf namespace
use Mpdf\Mpdf;

global $DB, $CFG;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$invoiceid = optional_param('invoiceid', null, PARAM_INT);
$invoicetype = optional_param('invoicetype', null, PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);
$enrolstripeid = optional_param('enrolstripeid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$suspendenrollment = optional_param('suspendenrollment', null, PARAM_INT);

// Check that the user is a valid user.
$user = \core_user::get_user($userid);
if (!$user || !core_user::is_real_user($userid)) {
    throw new moodle_exception('invaliduser', 'error');
}

$url = new moodle_url('/report/liqpaydata/mypayments.php', array('userid' => $userid));

$PAGE->set_context(context_user::instance($userid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(fullname($user) . ': user payments');
$PAGE->set_heading(fullname($user). ': user payments');

//Action - suspend user's enrolment
if (isset($courseid)&&isset($suspendenrollment)){
    $course = get_course($courseid);
    $coursecontext = \context_course::instance($courseid);
    if (is_enrolled($coursecontext, $userid, '', true)) {
        //\enrol_stripe\purchase::setStripeStudentEnrollmentState($courseid, $userid, ENROL_USER_SUSPENDED);
        // Check if courrse contacts cache needs to be cleared.
        core_course_category::user_enrolment_changed($courseid, $userid, ENROL_USER_SUSPENDED);
        if (!is_enrolled($coursecontext, $userid, '', true)) {
            \core\notification::info('Student\'s enrollment in course ' . $course->fullname . ' for user ' .  fullname($user) . ' has been suspended succesfully');
        } else {
            \core\notification::error('Failed to suspend student\'s enrollment in course ' . $course->fullname . ' for user ' .  fullname($user));
        }
    }
}

//prepare table data
$table = new report_mypayments('report_mypayments');
$table->is_downloading($download, "user-$userid-payments");
$from       = "{enrol_liqpay} el";

$where      = "el.userid = ?";
$whereGroup = $where;
$params     = [$userid];
$table->set_count_sql("SELECT COUNT(DISTINCT(el.id)) FROM {$from} WHERE {$where}", $params);

//list of fields to retreive
$fields = 'el.id as esid, el.timeupdated, el.installment, el.subscription, el.subscription_plan, el.lastrunstatus, el.payment_currency, el.item_name, el.courseid, el.cost, el.cost_full, el.tax, el.tax_percent, el.payment_gross, el.total, el.quantity, el.nextrun, el.alreadyruns, el.totalruns, el.userid';

$table->set_sql($fields, $from, $whereGroup, $params);
$table->define_baseurl($PAGE->url);

$wheresuccesstotal      = 'el.userid = ? and el.alreadyruns>0';
$totals = $DB->get_record_sql("
SELECT SUM(q1.cnt) AS count, SUM(q1.payment_gross) AS payment_gross, MAX(q1.payment_currency)  AS payment_currency 
FROM 
    (SELECT IF(el.installment=0, el.alreadyruns, 1) AS cnt, el.payment_currency, IF(el.subscription>0, el.payment_gross*el.alreadyruns, el.payment_gross) AS payment_gross FROM $from WHERE $wheresuccesstotal) AS q1
                              ", $params);

//Action - generate and force download invoice pdf!
if (isset($invoiceid)&&isset($invoicetype)){
    //get invoice data
    $sql = 'SELECT '.$fields. ' FROM '.$from.' WHERE el.id = ?';
    $data = $DB->get_record_sql($sql, array($invoiceid));
    //get subscription plan description as readable string
    $subscription_name = \enrol_liqpay\purchase::getFormattedSubcriptionTermName($data);
    $course_price = \enrol_liqpay\purchase::getPriceFromSubcription($data->subscription_plan);
    $course_cost = \enrol_liqpay\purchase::getCurrencySymbol($data->payment_currency).$data->cost;
    //setud data to invoice template
    $invoicedata = array (
            'companyname'       =>get_config('report_liqpaydata', 'invcompanyname'), 
            'companyaddress'    =>get_config('report_liqpaydata', 'invcompanyaddress'), 
            'hstnumber'         =>get_config('report_liqpaydata', 'invhstnumber'), 
            'invoicefooter'     =>get_config('report_liqpaydata', 'invoicefooter'), 
            'created'           =>$data->timeupdated, 
            'invoiceid'         =>$data->elid, 
            'item_name'         =>$data->item_name, 
            'subscription'      =>$subscription_name, 
            'quantity'          =>$data->quantity, 
            'cost'              =>$course_cost, 
            'rate'              =>$course_price, 
            'tax_percent'       =>$data->tax_percent, 
            'tax'               =>$data->tax, 
            'total'             =>$data->payment_gross, 
            'charge_amount'     =>$data->cost_full, 
            'firstname'         =>$user->firstname, 
            'lastname'          =>$user->lastname, 
            'logourl'           =>$OUTPUT->get_compact_logo_url()->out()
            );
    $template = $OUTPUT->render_from_template('report_liqpaydata/invoice', $invoicedata);

    //generate and download pdf document
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $CFG->dataroot . '/temp/mpdf']);
    $mpdf->WriteHTML($template);
    $mpdf->Output('invoice_'.date('M-d-Y', $data->timeupdated).'_'.$data->item_name.'.pdf', 'D');
    exit();
}

//display or download table
if ( !$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Recorded payments');
    echo $OUTPUT->heading("Successfull payments: $totals->count, Total amount: $totals->payment_currency $totals->payment_gross", 5);
}

$table->out(20, true);

if ( !$table->is_downloading()) {
    echo $OUTPUT->footer(); 
}