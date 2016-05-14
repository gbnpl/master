#!/usr/bin/php
<?php

/*
 *
 *  (C) Copyright 2012 LMS iNET Developers
 *  (c) Copyright 2015-2016 Tomasz Chiliński <tomasz.chilinski@chilan.com>
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */


/*
 skrypt wystawia faktury VAT, domyślnie za poprzedni miesiąć
 przełącznik --leftmonth=M , M to ilość miesięcy wstecz za które mają być wystawione
 np. dzisiaj mamy sierpień
 --leftmonth=1 -> faktury będą za lipiec
 --leftmonth=3 -> faktury będą za maj
*/

empty($_SERVER['SHELL']) && die('<br><Br>Sorry Winnetou, tylko powloka shell ;-)');

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:'	=>	'config-file:',
	'q'	=>	'quiet',
	'h'	=>	'help',
	'l:'	=>	'leftmonth:',
	'f:'	=>	'fakedate:',
	's'		=>	'subscription-in-advance',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = array();
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

$quiet = array_key_exists('quiet', $options);

if (array_key_exists('help',$options)) {
print <<<EOF
iNET LMS Hiperus C5 Invoice

-C, --config-file      alternatywny plik konfiguracyjny, -C /etc/lms/lms.ini
-h, --help             pomoc
-l, --leftmonth        parametr M to numer miesiaca wstecz za ktory ma byc wystawiona faktura,
                       np. mamy maj a M=1 to f.vat będą za kwiecien, M=2 f.vat za marzec itd
                       Domyslnie wystawia za pełny poprzedni miesiac
                       użycie : -l 1
-f, --fakedate=YYYY/MM/DD       override system date

EOF;
    exit(0);
}

if (!$quiet) {
	print <<<EOF

lms_hiperus_c5_invoice.php
(C) 2012-2013 LMS iNET,
(C) 2015-2016 Tomasz Chiliński <tomasz.chilinski@chilan.com>

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Nie mozna odczytac pliku konfiguracyjnego file [".$CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path))
	require_once $composer_autoload_path;
else
	die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't working without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

function localtime2() {
	global $fakedate;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
	} else
		return time();
}

function GetDefaultNumberplanidByCustomer($cid, $doctype = DOC_INVOICE) {
	global $DB;

	return $DB->GetOne('SELECT np.id 
		FROM numberplans np 
		JOIN numberplanassignments n ON (n.planid = np.id) 
		JOIN customers c ON (c.divisionid = n.divisionid) 
		WHERE np.doctype = ? AND np.isdefault = 1 AND c.id = ? ;',
		 array($doctype,$cid));
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');
$SYSLOG = new SYSLOG($DB);

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$HIPERUS = new LMSHiperus($DB);

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);

if (!array_key_exists('leftmonth', $options)) 
	$leftmonth = ConfigHelper::getConfig('hiperus_c5.leftmonth', 1);
else
	$leftmonth = $options['leftmonth'];

if (empty($leftmonth))
	$leftmonth = 1;

$subscription_in_advance = array_key_exists('subscription-in-advance', $options);

$currtime = localtime2();
$curr_month = strftime("%m", $currtime);
$curr_year = strftime("%Y", $currtime);
$enddate = mktime(23, 59, 59, $curr_month - $leftmonth + 1, 0, $curr_year);
$date = mktime(12, 0, 0, $curr_month - $leftmonth, 1, $curr_year);
$month = intval(strftime("%m", $date));
$year = intval(strftime("%Y", $date));
$date = mktime(12, 0, 0, $curr_month - $leftmonth + 1, 1, $curr_year);
$year_sub = $subscription_in_advance ? intval(strftime("%Y", $date)) : $year;
$month_sub = $subscription_in_advance ? intval(strftime("%m", $date)) : $month;
$datetime = strftime("%Y-%m-%d %H:%M", $enddate);
$date = strftime("%Y-%m-%d", $enddate);

$customers = $DB->GetAll("SELECT hc.id AS id, hc.ext_billing_id AS id_ext, "
			. $DB->Year('hc.create_date') . " AS create_year, "
			. $DB->Month('hc.create_date') . " AS create_month, "
			. $DB->Day('hc.create_date') . " AS create_day "
			. " FROM hv_customers hc 
			JOIN hv_assign ha ON (ha.customerid = hc.id) 
			WHERE ha.keytype = ? AND ha.keyvalue = ?
				AND hc.create_date <= ?",
			array('issue_invoice', '2', $datetime));

if (empty($customers))
	die("No customers data found!" . PHP_EOL);

$tax = $DB->GetRow('SELECT id, value FROM taxes WHERE value = ? LIMIT 1',
	array(ConfigHelper::getConfig('hiperus_c5.taxrate', ConfigHelper::getConfig('phpui.default_taxrate', 23))));
if (!$tax)
	die("Couldn't find valid tax rate!" . PHP_EOL);
$taxid = $tax['id'];
$vat = ($tax['value'] + 100) / 100;

$months = array('', 'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień');

$invoice_subscription_comment = ConfigHelper::getConfig('hiperus_c5.invoice_subscription_comment',
	'Abonament VoIP: %pricelist %numbers za okres %month_name %year');
$invoice_call_comment = ConfigHelper::getConfig('hiperus_c5.invoice_call_comment',
	'Kosz rozmów poza abonamentem %pricelist %numbers za okres %month_name %year');

foreach ($customers as $i => $customer) {
	$customers[$i]['year'] = $year;
	$customers[$i]['month'] = $month;
	$customers[$i]['sum_cost'] = 0;
	$customers[$i]['terminals'] = array();
	$customers[$i]['numberplanid'] = ConfigHelper::getConfig('hiperus_c5.numberplanid',
		GetDefaultNumberPlanIDByCustomer($customer['id_ext'], DOC_INVOICE));
	$terminals = $DB->GetAll("SELECT t.*, s.name AS subscription_name FROM hv_terminal AS t
		JOIN hv_subscriptionlist s ON s.id = t.id_subscription
		WHERE t.customerid = ? AND (subscription_from IS NULL OR subscription_from <= ?)
			AND (subscription_to IS NULL OR subscription_to >= ?)",
		array($customer['id'], $date, $date));
	if (empty($terminals))
		continue;
	foreach ($terminals as $j => $terminal) {
		$customers[$i]['terminals'][$j]['pricelist_name'] = $terminal['pricelist_name'];
		$customers[$i]['terminals'][$j]['subscription_name'] = $terminal['subscription_name'];
		$customers[$i]['terminals'][$j]['name'] = $terminal['username'];
		$customers[$i]['terminals'][$j]['invoice_value'] = $DB->GetOne("SELECT invoice_value FROM hv_subscriptionlist
			WHERE id = ? LIMIT 1",
			array($terminal['id_subscription']));
		$cost = $HIPERUS->GetListBillingByCustomer2($customer['id'], $year, $month, $terminal['username']);
		$numbers = $DB->GetCol("SELECT number FROM hv_pstn
			WHERE terminal_name = ? AND create_date <= ?",
			array($terminal['username'], $datetime));
		$customers[$i]['terminals'][$j]['cost'] = ($cost[0]['cost'] ? $cost[0]['cost'] : 0);
		$customers[$i]['sum_cost'] += ($customers[$i]['terminals'][$j]['invoice_value'] + $customers[$i]['terminals'][$j]['cost']) * $vat;
		$invoice_value = str_replace(',', '.', $customers[$i]['terminals'][$j]['invoice_value'] * $vat);

		if ($invoice_value > 0) {
			$comment = $invoice_subscription_comment;
			$comment = str_replace('%pricelist', $customers[$i]['terminals'][$j]['pricelist_name'], $comment);
			$comment = str_replace('%subscription', $customers[$i]['terminals'][$j]['subscription_name'], $comment);
			$comment = str_replace('%numbers', (empty($numbers) ? '' : '(' . implode(', ', $numbers) . ')'), $comment);
			$comment = str_replace('%month_name', $months[$month_sub], $comment);
			$comment = str_replace('%year', $year_sub, $comment);

			$customers[$i]['content'][] = array(
				'valuebrutto'	=> $invoice_value,
				'taxid'			=> $taxid,
				'prodid'		=> ConfigHelper::getConfig('hiperus_c5.prodid',''),
				'jm'			=> ConfigHelper::getConfig('hiperus_c5.content','szt'),
				'count'			=> '1',
				'discount'		=> '0',
				'pdiscount'		=> '0',
				'vdiscount'		=> '0',
				'name'			=> $comment,
				'tariffid'		=> 0
			);
		}

		if ($customers[$i]['terminals'][$j]['cost'] > 0) {
			$comment = $invoice_call_comment;
			$comment = str_replace('%pricelist', $customers[$i]['terminals'][$j]['pricelist_name'], $comment);
			$comment = str_replace('%subscription', $customers[$i]['terminals'][$j]['subscription_name'], $comment);
			$comment = str_replace('%numbers', (empty($numbers) ? '' : '(' . implode(', ', $numbers) . ')'), $comment);
			$comment = str_replace('%month_name', $months[$month], $comment);
			$comment = str_replace('%year', $year, $comment);

			$customers[$i]['content'][] = array(
				'valuebrutto'	=> str_replace(',', '.', $customers[$i]['terminals'][$j]['cost'] * $vat),
				'taxid'			=> $taxid,
				'prodid'		=> ConfigHelper::getConfig('hiperus_c5.prodid',''),
				'jm'			=> ConfigHelper::getConfig('hiperus_c5.content','szt'),
				'count'			=> '1',
				'discount'		=> '0',
				'pdiscount'		=> '0',
				'vdiscount'		=> '0',
				'name'			=> $comment,
				'tariffid'		=> 0
			);
		}
	}
	$customers[$i]['sum_cost'] = str_replace(',', '.', $customers[$i]['sum_cost']);
}

unset($vat);

$tabtmp = array();
foreach ($customers as $i => $customer)
	if ($customer['sum_cost'] != 0)
		$tabtmp[] = $customer;
$customers = $tabtmp;
unset($tabtmp);

$starttime = mktime(0, 0, 0, $curr_month, 1, $curr_year);
$endtime = intval($currtime / 86400) * 86400 + 86400;

$numbertemplates = array();
$numberplans = array();
$results = $DB->GetAll("SELECT n.id, n.period, COALESCE(a.divisionid, 0) AS divid, isdefault 
	FROM numberplans n 
	LEFT JOIN numberplanassignments a ON (a.planid = n.id) 
	WHERE doctype = ?",
	array(DOC_INVOICE));
foreach ($results as $row)
	if ($row['isdefault'])
		$numberplans[$row['divid']] = $row['id'];

foreach ($customers as $i => $customer) {
	$cid = intval($customer['id_ext']);
	$add_new_invoices = ConfigHelper::checkValue(ConfigHelper::getConfig('hiperus_c5.add_new_invoices', true));
	if (!$add_new_invoices) {
		$invoice = $DB->GetRow("SELECT d.id, cdate, MAX(itemid) AS items, d3.id AS id2 FROM documents d
			JOIN invoicecontents ic ON ic.docid = d.id
			LEFT JOIN (
				SELECT d2.id, COUNT(ic2.docid) AS items
				FROM documents d2
				JOIN invoicecontents ic2 ON ic2.docid = d2.id
				WHERE d2.type = ? AND sdate >= ? AND sdate < ?
					AND (ic2.description ?LIKE? ? OR ic2.description ?LIKE? ?)
				GROUP BY d2.id
			) d3 ON d3.id = d.id
			WHERE d.customerid = ? AND d.type = ? AND sdate >= ? AND sdate < ?
			GROUP BY d.id, cdate, d3.id
			ORDER BY d.cdate DESC LIMIT 1", 
			array(DOC_INVOICE, $starttime, $endtime,
				'Abonament VoIP%', 'Koszt rozmów poza abonamentem%',
				$cid, DOC_INVOICE, $starttime, $endtime));
		if (!$invoice)
			$add_new_invoices = true;
		elseif (empty($invoice['id2'])) {
			$docid = $invoice['id'];
			$items = $invoice['items'];
			$DB->BeginTrans();
			foreach ($customer['content'] as $content) {
				unset($content['discount']);
				$items++;
				array_unshift($content, $items);
				array_unshift($content, $docid);
				$DB->Execute("INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array_values($content));
				$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
					VALUES (?, ?, ?, ?, ?, ?, ?)",
					array($invoice['cdate'], str_replace(',', '.', $content['valuebrutto'] * -1), $content['taxid'], $cid, $content['name'],
						$docid, $items));
			}
			$DB->CommitTrans();
		}
	}
	if ($add_new_invoices) {
		$customerinfo = $LMS->GetCustomer($cid, true);

		if ($customerinfo['paytime'] != -1)
			$paytime = $customerinfo['paytime'];
		elseif (($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions WHERE id = ?',
			array($customerinfo['divisionid']))) === NULL)
			$paytime = ConfigHelper::getConfig('invoices.paytime');

		if ($customerinfo['paytype'])
			$paytype = $customerinfo['paytype'];
		else
			$paytype = $DB->GetOne('SELECT inv_paytype FROM divisions WHERE id = ?',
				array($customerinfo['divisionid']));
		if (empty($paytype) || !isset($PAYTYPES[$paytype]))
			$paytype = intval(ConfigHelper::getConfig('invoices.paytype'));

		$numberplanid = $customer['numberplanid'];
		if (empty($numberplanid))
			$numberplanid = $numberplans[$customerinfo['divisionid']];
		if (!isset($numbertemplates[$numberplanid]))
			$numbertemplates[$numberplanid] = $DB->GetOne("SELECT template FROM numberplans WHERE id = ?", array($numberplanid));
		$number = $LMS->GetNewDocumentNumber(DOC_INVOICE, $numberplanid, $currtime);
		$fullnumber = docnumber($numberplanid, $numbertemplates[$numberplanid], $currtime);

		$division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
			account, inv_header, inv_footer, inv_author, inv_cplace 
			FROM divisions WHERE id = ? ;',array($customerinfo['divisionid']));

		$DB->BeginTrans();
		$res = $DB->Execute("INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
			customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype,
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber) 
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($number, $numberplanid, DOC_INVOICE, $customerinfo['countryid'], $customerinfo['divisionid'], $cid,
			$customerinfo['lastname']." ".$customerinfo['name'], $customerinfo['address'], $customerinfo['zip'],
			$customerinfo['city'], $customerinfo['ten'], $customerinfo['ssn'], $currtime, $currtime, $paytime, $paytype,
			($division['name'] ? $division['name'] : ''),
			($division['shortname'] ? $division['shortname'] : ''),
			($division['address'] ? $division['address'] : ''), 
			($division['city'] ? $division['city'] : ''), 
			($division['zip'] ? $division['zip'] : ''),
			($division['countryid'] ? $division['countryid'] : 0),
			($division['ten'] ? $division['ten'] : ''), 
			($division['regon'] ? $division['regon'] : ''), 
			($division['account'] ? $division['account'] : ''),
			($division['inv_header'] ? $division['inv_header'] : ''), 
			($division['inv_footer'] ? $division['inv_footer'] : ''), 
			($division['inv_author'] ? $division['inv_author'] : ''), 
			($division['inv_cplace'] ? $division['inv_cplace'] : ''),
			$fullnumber,
		));
		if (!$res) {
			$DB->RollbackTrans();
			continue;
		}

		$docid = $DB->GetLastInsertID('documents');
		$items = 1;
		foreach ($customer['content'] as $content) {
			unset($content['discount']);
			array_unshift($content, $items);
			array_unshift($content, $docid);
			$DB->Execute("INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, pdiscount, vdiscount, description, tariffid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array_values($content));
			$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid) 
				VALUES (?, ?, ?, ?, ?, ?, ?)",
				array($currtime, str_replace(',', '.', $content['valuebrutto'] * -1), $content['taxid'], $cid, $content['name'],
					$docid, $items));
			$items++;
		}
		$DB->CommitTrans();
	}
}

?>
