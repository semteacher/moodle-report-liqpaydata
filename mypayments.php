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
require_once($CFG->dirroot . '/report/liqpaydata/locallib.php');
//require_once($CFG->dirroot . '/report/liqpaydata/mypayments.class.php');

// reference the Mpdf namespace
use Mpdf\Mpdf;

//global $DB, $CFG;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$paymenttypeid          = optional_param('paymenttypeid', PAYMENTS_ALL, PARAM_INT);
$showpage               = optional_param('page', 0, PARAM_INT);     // Which page to show.
$perpage                = optional_param('perpage', 10, PARAM_INT); // How many per page.
$userid                 = optional_param('userid', $USER->id, PARAM_INT);
$invoiceid = optional_param('invoiceid', null, PARAM_INT);
$invoicetype = optional_param('invoicetype', null, PARAM_TEXT);
$download               = optional_param('download', '', PARAM_ALPHA);
$courseid               = optional_param('courseid', null, PARAM_INT);
$enrolcourseid          = optional_param('enrolcourseid', null, PARAM_INT);
$enrolmentstatuschange  = optional_param('enrolmentstatuschange', null, PARAM_INT);

// Check that the user is a valid user.
$user = \core_user::get_user($userid);
if (!$user || !core_user::is_real_user($userid)) {
    throw new moodle_exception('invaliduser', 'error');
}
$params = array('userid' => $userid, 'paymenttypeid'=>$paymenttypeid);
if (!empty($showpage)) {
    $params = array_merge($params, array('page'=>$showpage, 'perpage'=>$perpage));
}
if (!empty($courseid)) {
    $params = array_merge($params, array('courseid'=>$courseid));
}
$url = new \moodle_url(REPORT_PER_USER, $params);

$PAGE->set_context(context_user::instance($userid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(fullname($user) . ': user\'s payments');
$PAGE->set_heading(fullname($user). ': user\'s payments');

//Action - suspend user's enrolment
if (isset($enrolcourseid)&&isset($enrolmentstatuschange)){
    $enrolcourse = get_course($enrolcourseid);
    $coursecontext = \context_course::instance($enrolcourseid);
    if (is_enrolled($coursecontext, $enroluserid, '')) {
        $user = \core_user::get_user($enroluserid);
        if (\enrol_liqpay\util::update_user_enrolmen($enrolcourseid, $enroluserid, $enrolmentstatuschange)) {
            \core\notification::info('Student\'s enrollment status in course ' . $enrolcourse->fullname . ' for user ' .  fullname($user) . ' has been changed succesfully');            
        } else {
            \core\notification::error('Failed to change of student\'s enrollment in course ' . $enrolcourse->fullname . ' for user ' . fullname($user));            
        }
    }
}

//prepare table data
$table = new \report_liqpaydata\table\mypayments('report_mypayments', false, $paymenttypeid, $courseid, $userid);
$table->is_downloading($download, "user-$userid-payments");
$table->define_baseurl($PAGE->url);
$totals = $table->get_liqpay_success_totals();

// TODO: invoice not working for a while!
$from       = "{enrol_liqpay} el";
$where      = "el.userid = ?";
$whereGroup = $where;
$params     = [$userid];
//list of fields to retreive
$fields = 'el.id as esid, el.timeupdated, el.installment, el.subscription, el.subscription_plan, el.lastrunstatus, el.payment_currency, el.item_name, el.courseid, el.cost, el.cost_full, el.tax, el.tax_percent, el.payment_gross, el.total, el.quantity, el.nextrun, el.alreadyruns, el.totalruns, el.userid';
$wheresuccesstotal      = 'el.userid = ? and el.alreadyruns>0';
//Action - generate and force download invoice pdf!
if (isset($invoiceid)&&isset($invoicetype)){
    //get invoice data
    $sql = 'SELECT '.$fields. ' FROM '.$from.' WHERE el.id = ?';
    $data = $DB->get_record_sql($sql, array($invoiceid));
    //get subscription plan description as readable string
    //$subscription_name = \enrol_liqpay\purchase::getFormattedSubcriptionTermName($data);
    //$course_price = \enrol_liqpay\purchase::getPriceFromSubcription($data->subscription_plan);
    //$course_cost = \enrol_liqpay\purchase::getCurrencySymbol($data->payment_currency).$data->cost;
    $subscription_name = 'TODO';
    $course_price = 'TODO';
    $course_cost = 'TODO';
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
    echo $OUTPUT->heading(get_string('recordedpayments', 'report_liqpaydata'));
    echo $OUTPUT->heading(get_string('rpaymentssummary', 'report_liqpaydata', ['count'=>$totals->cnt, 'currency'=>$totals->currency, 'gross'=>$totals->payment_gross ]), 5);

    //payment filter form
    $table->out_filter_form();
}

$table->out($perpage, true);

if ( !$table->is_downloading()) {
    echo $OUTPUT->footer(); 
}