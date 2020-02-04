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
 * Adds the event list link to the admin tree
 *
 * @package    report_liqpaydata
 * @copyright  2020 Andrii Sements
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig|| has_capability('moodle/site:configview', context_system::instance())) {
    $url = $CFG->wwwroot . '/report/liqpaydata/allpayments.php';
    $ADMIN->add('reports', new admin_externalpage('reportallpayments', get_string('allpayments', 'report_liqpaydata'), $url));
    
    if ($ADMIN->fulltree&&($hassiteconfig || has_capability('report/security:view', context_system::instance()))) {
        //$settings = new admin_settingpage('report_liqpaydata', get_string('pluginname', 'report_liqpaydata').': a company\'s data');
        //$ADMIN->add('reportplugins', $settings); //DEBUG: led to unreasonable error 'no parents!'

        //company name
        $settings->add(new admin_setting_confightmleditor('report_liqpaydata/invcompanyname',
				get_string('invcompanyname', 'report_liqpaydata'), get_string('configcompanyname', 'report_liqpaydata'),
				'Default Company', PARAM_RAW));
        //company address
        $settings->add(new admin_setting_confightmleditor('report_liqpaydata/invcompanyaddress',
				get_string('invcompanyaddress', 'report_liqpaydata'), get_string('configcompanyaddress', 'report_liqpaydata'),
				'Default Company Address', PARAM_RAW));
        //HST number
        $settings->add(new admin_setting_configtext('report_liqpaydata/invhstnumber',
				get_string('invhstnumber', 'report_liqpaydata'), get_string('confighstnumber', 'report_liqpaydata'),
				'', PARAM_TEXT));
        //Invoice footer
        $settings->add(new admin_setting_confightmleditor('report_liqpaydata/invoicefooter',
				get_string('invoicefooter', 'report_liqpaydata'), get_string('configinvoicefooter', 'report_liqpaydata'),
				'', PARAM_RAW));
    }
}
