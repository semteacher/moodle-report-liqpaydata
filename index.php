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
require_once($CFG->dirroot . '/report/liqpaydata/locallib.php');

require_login(null, false);

if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$paymenttypeid          = optional_param('paymenttypeid', PAYMENTS_ALL, PARAM_INT);
$enroluserid            = optional_param('enroluserid', $USER->id, PARAM_INT);
$download               = optional_param('download', '', PARAM_ALPHA);
$courseid               = optional_param('courseid', null, PARAM_INT);
$enrolcourseid          = optional_param('enrolcourseid', null, PARAM_INT);
$enrolmentstatuschange  = optional_param('enrolmentstatuschange', null, PARAM_INT);
$showpage               = optional_param('page', 0, PARAM_INT);     // Which page to show.
$perpage                = optional_param('perpage', 3, PARAM_INT); // How many per page.

// create page url
$params = array('paymenttypeid'=>$paymenttypeid);
if (!empty($showpage)) {
    $params = array_merge($params, array('page'=>$showpage, 'perpage'=>$perpage));
}
if (!isset($courseid)) {
    $context = \context_system::instance();
} else {
    $context = \context_course::instance($courseid);
    $course = \get_course($courseid);
    $params = array_merge($params, array('courseid'=>$courseid));
    $PAGE->set_course($course);
}
$url = new \moodle_url(REPORT_ALL, $params);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('allpayments', 'report_liqpaydata'));
$PAGE->set_heading(get_string('allpayments', 'report_liqpaydata'));
$PAGE->navbar->add(get_string('allpayments', 'report_liqpaydata'), $url);

//required at list site-wide manager role to access
if (!isset($courseid)) {
    require_capability('report/liqpaydata:siteview', $context);
} else {
    require_capability('report/liqpaydata:courseview', $context);
}

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
$table = new \report_liqpaydata\table\mypayments('report_allpayments', true, $paymenttypeid, $courseid);
$table->define_baseurl($PAGE->url);
//prepare for filtering by paymet type
$displaylist = $table->get_payment_option_names();
$table->is_downloading($download, "all-users-$displaylist[$paymenttypeid]-payments");                               
$totals = $table->get_liqpay_success_totals();

//display or download table
if ( !$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Recorded payments');
    echo $OUTPUT->heading("Successfull payments: $totals->cnt, Total amount: $totals->currency $totals->payment_gross", 5);

    //payment filter form
    $table->out_filter_form();
}

$table->out($perpage, true);

if ( !$table->is_downloading()) {
    echo $OUTPUT->footer(); 
}