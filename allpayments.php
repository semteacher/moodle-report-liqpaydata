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
 * All Users' LiqPay Payments - site report
 *
 * @package    report_liqpaydata
 * @copyright  2018 Andrii Sements - LearnFormulaFMCorz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require('../../config.php');
require_once($CFG->dirroot . '/report/liqpaydata/mypayments.class.php');

global $DB, $CFG;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$showpage = optional_param('page', 0, PARAM_INT);
$paymenttypeid = optional_param('paymenttypeid', PAYMENTS_ALL, PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$enrolstripeid = optional_param('enrolstripeid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$suspendenrollment = optional_param('suspendenrollment', null, PARAM_INT);

$url = new moodle_url('/report/liqpaydata/allpayments.php', array('paymenttypeid'=>$paymenttypeid));
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('allpayments', 'report_liqpaydata'));
$PAGE->set_heading(get_string('allpayments', 'report_liqpaydata'));

// Check that the user is a valid user.
$user = \core_user::get_user($userid);
if (!$user || !core_user::is_real_user($userid)) {
    throw new moodle_exception('invaliduser', 'error');
}
//required at list site-wide manager role to access
require_capability('moodle/user:update', $context);

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
$table = new report_mypayments('report_allpayments', true);

//list of fields to retreive
$fields = 'es.id as esid, el.timeupdated, el.installment, el.subscription, el.subscription_plan, el.lastrunstatus, el.payment_currency, el.item_name, el.courseid, el.cost, el.cost_full, el.tax, el.tax_percent, el.payment_gross, el.total, el.quantity, el.nextrun, el.alreadyruns, el.totalruns, el.userid';
$from  = "{enrol_liqpay} el";

//prepare for filtering by paymet type
$displaylist = array(
                        PAYMENTS_ALL=>'All', 
                        PAYMENTS_ONETIME=>'One time payments', 
                        PAYMENTS_SUBSCRIPTION=>'Subscriptions', 
                        PAYMENTS_ISTALLMENT=>'Instalments'
                        );
if ($paymenttypeid == PAYMENTS_SUBSCRIPTION) {
    $wherepaymenttype = ' and el.subscription > 0';
} elseif ($paymenttypeid == PAYMENTS_ISTALLMENT) {
    $wherepaymenttype = ' and el.installment > 0';
} elseif ($paymenttypeid == PAYMENTS_ONETIME) {
    $wherepaymenttype = ' and (el.subscription = 0 and el.installment = 0)';
} else {
    $wherepaymenttype = '';  //show all
}

$where      = 'el.userid > 0' . $wherepaymenttype;
$whereGroup = $where;
$params     = array();

$table->set_count_sql("SELECT COUNT(DISTINCT(el.id)) FROM {$from} WHERE {$where}", $params);
$table->set_sql($fields, $from, $whereGroup, $params);
$table->define_baseurl($PAGE->url);

$wheresuccesstotal = '(el.alreadyruns>0)'.$wherepaymenttype;
$totals = $DB->get_record_sql("
SELECT SUM(q1.cnt) AS count, SUM(q1.payment_gross) AS payment_gross, MAX(q1.payment_currency)  AS payment_currency 
FROM 
    (SELECT IF(el.installment=0, el.alreadyruns, 1) AS cnt, el.payment_currency, IF(el.subscription>0, el.payment_gross*el.alreadyruns, el.payment_gross) AS payment_gross FROM $from WHERE $wheresuccesstotal) AS q1
                              ", $params);

$table->is_downloading($download, "all-users-$displaylist[$paymenttypeid]-payments");                               

//display or download table
if ( !$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Recorded payments');
    echo $OUTPUT->heading("Successfull payments: $totals->count, Total amount: $totals->payment_currency $totals->payment_gross", 5);

    //payment filter form
    echo '<form action="'.s($PAGE->url->out(false)).'" method="post" id="paymentsfilterform">';
    echo '<div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo html_writer::tag('label', 'Display payments of the following type:&nbsp;', array('for' => 'paymenttypeselect'));
    echo html_writer::select($displaylist, 'paymenttypeid', $paymenttypeid, array('0' => 'choosedots'), array('id' => 'paymenttypeselect'));
    echo '<noscript style="display:inline">';
    echo '<div><input type="submit" value="'.get_string('ok').'" /></div>';
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

$table->out(20, true);

if ( !$table->is_downloading()) {
    echo $OUTPUT->footer(); 
}