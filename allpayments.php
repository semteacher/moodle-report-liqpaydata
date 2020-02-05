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
//require_once($CFG->dirroot . '/report/liqpaydata/mypayments.class.php');
require_once($CFG->dirroot . '/report/liqpaydata/locallib.php');

global $DB, $CFG;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$showpage = optional_param('page', 0, PARAM_INT);
$paymenttypeid = optional_param('paymenttypeid', PAYMENTS_ALL, PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$courseid = optional_param('courseid', null, PARAM_INT);
$enrolmentstatuschange = optional_param('enrolmentstatuschange', null, PARAM_INT);

//$url = new moodle_url('/report/liqpaydata/allpayments.php', array('paymenttypeid'=>$paymenttypeid));
$url = new \moodle_url('/report/liqpaydata/allpayments.php', array());

if (!isset($courseid)) {
    $context = \context_system::instance();
} else {
    $context = \context_course::instance($courseid);
    //$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
    $course = \get_course($courseid);
    $PAGE->set_course($course);
}
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('allpayments', 'report_liqpaydata'));
$PAGE->set_heading(get_string('allpayments', 'report_liqpaydata'));
$PAGE->navbar->add(get_string('allpayments', 'report_liqpaydata'), $url);

// Check that the user is a valid user.
$user = \core_user::get_user($userid);
if (!$user || !(\core_user::is_real_user($userid))) {
    throw new \moodle_exception('invaliduser', 'error');
}
//required at list site-wide manager role to access
if (!isset($courseid)) {
    require_capability('moodle/site:configview', $context);
} else {
    require_capability('moodle/user:update', $context);
}

//Action - suspend user's enrolment
if (isset($courseid)&&isset($enrolmentstatuschange)){
    $course = get_course($courseid);
    $coursecontext = \context_course::instance($courseid);
    if (is_enrolled($coursecontext, $userid, '')) {
        if (\enrol_liqpay\util::update_user_enrolmen($courseid, $userid, $enrolmentstatuschange)) {
            \core\notification::info('Student\'s enrollment status in course ' . $course->fullname . ' for user ' .  fullname($user) . ' has been changed succesfully');            
        } else {
            \core\notification::error('Failed to change of student\'s enrollment in course ' . $course->fullname . ' for user ' . fullname($user));            
        }
    }
}

//prepare table data
$table = new \report_liqpaydata\table\mypayments('report_allpayments', true, $paymenttypeid);

//prepare for filtering by paymet type
$displaylist = array(
                    PAYMENTS_ALL=>'All', 
                    PAYMENTS_ONETIME=>'One time payments', 
                    PAYMENTS_SUBSCRIPTION=>'Subscriptions'
                    );

$table->define_baseurl($PAGE->url);
$table->is_downloading($download, "all-users-$displaylist[$paymenttypeid]-payments");                               

$totals = $table->liqpay_totals();

//display or download table
if ( !$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Recorded payments');
    echo $OUTPUT->heading("Successfull payments: $totals->cnt, Total amount: $totals->currency $totals->payment_gross", 5);

    //payment filter form
    echo '<form action="'.s($PAGE->url->out(false)).'" method="post" id="paymentsfilterform">';
    echo '<div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo html_writer::tag('label', 'Display payments of the following type:&nbsp;', array('for' => 'paymenttypeselect'));
    echo html_writer::select($displaylist, 'paymenttypeid', $paymenttypeid, array(), array('id' => 'paymenttypeselect'));
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