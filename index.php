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
 * Users' LiqPay Payments - course report
 *
 * @package    report_liqpaydata
 * @copyright  2020 Andrii Sements
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require('../../config.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

$id       = optional_param('id', null, PARAM_INT); // course id.
$download = optional_param('download', '', PARAM_ALPHA);

$url = new moodle_url('/report/liqpaydata/index.php', array('id' => $id));


$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

// Get course details.
$course = null;
if (isset($id)) {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($course->id);
} else {
    require_login();
    $context = context_system::instance();
    $PAGE->set_context($context);
}

require_capability('moodle/course:update', $context);

if (empty($course) || ($course->id == $SITE->id)) {
    //admin_externalpage_setup('reportlog', '', null, '', array('pagelayout' => 'report'));
    $PAGE->set_title($SITE->shortname . ': user payments');
    $PAGE->set_heading($SITE->shortname);
} else {
    $PAGE->set_title($course->shortname . ': user payments');
    $PAGE->set_heading($course->fullname);
}


class report_liqpaydata extends table_sql
{
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        $this->sort_default_column = 'timecreated';
        $this->sort_default_order  = SORT_DESC;

        $columns = array(
            'user'                 => 'User',
            'enrol'                => 'Enrolment',
            'amount_credit'        => 'Payment',
            'commission_credit'    => 'Comission',
            'currency_credit'      => 'Currency',
            'payment_status'       => 'Payment Status',
            'timecreated'          => 'Enrolment date'
        );
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->column_class('timecreated', 'text-xs-right');
    }

    function col_timecreated($row)
    {
        return userdate($row->timecreated, '%b %e, %Y');
    }

    function col_user($row)
    {
        if ( ! $this->is_downloading()) {
            return '<a href="' . new moodle_url('/user/profile.php',
                    ['id' => $row->userid]) . '">' . fullname($row) . '</a>';
        } else {
            return fullname($row);
        }
    }
}


$table = new report_liqpaydata('report_liqpaydata');
$table->is_downloading($download, "course-$id-payments");
$from       = "{enrol} e 
JOIN {user_enrolments} ue ON ue.enrolid = e.id
JOIN {user} u ON ue.userid = u.id
LEFT JOIN {enrol_liqpay} el ON ue.userid = el.userid AND ue.enrolid = el.instanceid";
$where      = 'e.courseid = ?';
$whereGroup = $where . ' GROUP by u.id';
$params     = [
    $id
];
$table->set_count_sql("SELECT COUNT(DISTINCT(ue.id)) FROM {$from} WHERE {$where}", $params);

$fields = 'DISTINCT(ue.id), e.enrol, ue.userid, el.amount_credit, el.commission_credit, el.currency_credit, ue.timecreated, el.payment_status, u.email, '
          . get_all_user_name_fields(true, 'u');
$table->set_sql($fields, $from, $whereGroup, $params);
$table->define_baseurl($PAGE->url);

$totals = $DB->get_record_sql("SELECT 
COUNT(DISTINCT (u.id)) as count, 
SUM(el.amount_credit) as payment_gross 
FROM $from WHERE $where",
    $params);

//$payment_gross = money_format('%.2n', $totals->payment_gross);
$payment_gross = $totals->payment_gross;

if ( ! $table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Course payments');
    echo $OUTPUT->heading("Users: $totals->count, Total amount: $payment_gross", 5);
}

$table->out(20, true);

if ( ! $table->is_downloading()) {
    echo $OUTPUT->footer();
}