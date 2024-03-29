<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Users' LiqPay Payments.
 *
 * @package    report_liqpaydata
 * @copyright  2020 Andrii Sements
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function report_liqpaydata_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course)
{
    if (empty($course)) {
        // We want to display these reports under the site context.
        $course = get_fast_modinfo(SITEID)->get_course();
    }
    $url = new moodle_url('/report/liqpaydata/mypayments.php', array('userid' => $user->id));
    $node = new core_user\output\myprofile\node('reports', 'mypayments',
        get_string('profilepayments', 'report_liqpaydata'), null, $url);
    $tree->add_node($node);
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_liqpaydata_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/log:view', $context)) {
        $url = new moodle_url('/report/liqpaydata/index.php', array('courseid'=>$course->id));
        $navigation->add(get_string('pluginname', 'report_liqpaydata'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}