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
 * Plugin version and other meta-data are defined here.
 *
 * @package     report_liqpaydata
 * @category    upgrade
 * @copyright   2018 Andriy Semenets <semteacher@gmail.com> && LearnFormula
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute report_liqpaydata upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
 
function xmldb_report_liqpaydata_upgrade($oldversion) {
    global $CFG;
    
    //will be executed on each upgrade - ensure that there is no such user menu item already and add it only once
    $customusermenuitems = $CFG->customusermenuitems;
    $items               = explode(PHP_EOL, $customusermenuitems);
    $paymentItem         = 'mypayments,report_liqpaydata|/report/payments/mypayments.php|grades';
    if (!in_array($paymentItem, $items, TRUE)) {
        $items[]             = $paymentItem;
        $customusermenuitems = implode(PHP_EOL, $items);
        set_config('customusermenuitems', $customusermenuitems);
    }

    return true;
}
