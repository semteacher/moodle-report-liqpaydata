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

define('PAYMENTS_ALL',          1);
define('PAYMENTS_ONETIME',      2);
define('PAYMENTS_SUBSCRIPTION', 3);
define('REPORT_PER_USER',    '/report/liqpaydata/mypayments.php');
define('REPORT_ALL',         '/report/liqpaydata/index.php');