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

$string['pluginname'] = 'Звіт про оплати через LiqPay';
$string['mypayments'] = 'Мої LiqPay оплати';
$string['allpayments'] = 'Оплати LiqPay вісх користувачів';
$string['profilepayments'] = 'Звіт про оплати через LiqPay';
$string['liqpaydata:siteview'] = 'Звіт про оплати через LiqPay payments report - доступ на рівні сайту';
$string['liqpaydata:courseview'] = 'Звіт про оплати через LiqPay payments report - доступ на рівні курсу';
$string['recordedpayments'] = 'Зареєстровані оплати';
$string['rpaymentssummary'] = 'Успішних оплат: {a->count}, на загальну суму: {$a->currency} {$a->gross}';
$string['tcoluser']= 'Користувач';
$string['tcolenroldate']= 'Дата зарахування';
$string['tcolenrolstatus'] = 'Статус в курсі';
$string['tcolcourse'] = 'Курс';
$string['tcolpaymenttype'] = 'Тип оплати';
$string['tcolupayed'] = 'Оплачено користувачем';
$string['tcolucomission'] = 'Комісія з користувача';
$string['tcolucurrency'] = 'Валюта користувача';
$string['tcolprice'] = 'Вартість';
$string['tcolrcomission'] = 'Комісія з отримувача';
$string['tcolreceived'] = 'Отримано';
$string['tcolcurrency'] = 'Валюта';
$string['tcolupaymentstatus'] = 'Статус оплати';
$string['tcollpayid'] = 'LP: PaymentID';
$string['tcollerrcode'] = ' LP: ErrorCode';
$string['tcollorder'] = 'LP: OrderID';
$string['stactive'] = 'Активний';
$string['stsuspended'] = 'Призупинений';
$string['stunenrolled'] = 'Відрахований';
$string['stunknown'] = 'Невідомо';
$string['dosuspend'] = '(Призупинити)';
$string['doactivate'] = '(Активувати)';

//report settings
$string['invcompanyname'] = 'Назва компанії';
$string['configcompanyname'] = 'Вкажіть назву компанії';
$string['invcompanyaddress'] = 'Адреса компанії';
$string['configcompanyaddress'] = 'Вкажіть адресу компанії';
$string['invhstnumber'] = 'Код HST';
$string['confighstnumber'] = 'Вкажіть HST код';
$string['invoicefooter'] = 'Інвойс - нижній колонтитул';
$string['configinvoicefooter'] = 'Вкажіть нижній колонтитул інвойсу';
