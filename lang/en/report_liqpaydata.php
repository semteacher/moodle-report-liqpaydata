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
 * Plugin strings are defined here.
 *
 * @package     report_liqpaydata
 * @category    string
 * @copyright   Semenets A.V.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LiqPay payments report';
$string['mypayments'] = 'My LiqPay payments';
$string['allpayments'] = 'All users\' LiqPay payments';
$string['profilepayments'] = 'LiqPay payments report';
$string['liqpaydata:siteview'] = 'LiqPay payments report - sitewide access';
$string['liqpaydata:courseview'] = 'LiqPay payments report - access in course';
$string['recordedpayments'] = 'Recorded payments';
$string['rpaymentssummary'] = 'Successfull payments: {$a->count}, Total amount: {$a->currency} {$a->gross}';
$string['tcoluser']= 'User';
$string['tcolenroldate']= 'Enrolment date';
$string['tcolenrolstatus'] = 'Enroll status';
$string['tcolcourse'] = 'Course';
$string['tcolpaymenttype'] = 'Payment Type';
$string['tcolupayed'] = 'User payed';
$string['tcolucomission'] = 'User\'s comission';
$string['tcolucurrency'] = 'User\'s currency';
$string['tcolprice'] = 'Price';
$string['tcolrcomission'] = 'Receiver\'s comission';
$string['tcolreceived'] = 'Received';
$string['tcolcurrency'] = 'Currency';
$string['tcolupaymentstatus'] = 'Payment Status';
$string['tcollpayid'] = 'LP: PaymentID';
$string['tcollerrcode'] = ' LP: ErrorCode';
$string['tcollorder'] = 'LP: OrderID';
$string['stactive'] = 'Active';
$string['stsuspended'] = 'Suspended';
$string['stunenrolled'] = 'Unenroled';
$string['stunknown'] = 'Unknown';
$string['dosuspend'] = '(Suspend)';
$string['doactivate'] = '(Activate)';
$string['displayonetimes'] = 'One time payments';
$string['displaysubscriptions'] = 'Subscriptions';
$string['fselectpaymenttype'] = 'Display payments of the following type:&nbsp;';
$string['fselectperpage'] = 'Display the following number of rows:&nbsp;';

//report settings
$string['invcompanyname'] = 'Company name';
$string['configcompanyname'] = 'Provide company name';
$string['invcompanyaddress'] = 'Company address';
$string['configcompanyaddress'] = 'Provide company address';
$string['invhstnumber'] = 'HST number';
$string['confighstnumber'] = 'Provide HST number';
$string['invoicefooter'] = 'Invoice footer';
$string['configinvoicefooter'] = 'Provide text of invoice footer';
