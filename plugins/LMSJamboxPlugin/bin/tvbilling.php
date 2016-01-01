<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f:' => 'fakedate:',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
tvbilling.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
tvbilling.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-f, --fakedate=YYYY/MM/DD       override system date

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
tvbilling.php
(C) 2001-2015 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'autoloader.php');

// Do some checks and load config defaults
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

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
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');
$SYSLOG = new SYSLOG($DB);

$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

function localtime2() {
	global $fakedate;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
	} else
		return time();
}

$addinvoices = ConfigHelper::checkConfig('jambox.tvbilling_addinvoices');

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);
$cdate = strftime("%s", localtime2());
$year = strftime("%Y", localtime2());
$month = intval(strftime("%m", localtime2()));
$start_date = date("Y-m-d", mktime(12, 0, 0, $month - 1, 1, $year));
//$end_date = date("Y-m-d", mktime(12, 0, 0, $month, 0, $year));
$end_date = date("Y-m-d");
$starttime = intval($cdate / 86400) * 86400;
$endtime = $starttime + 86400;

$to_insert = array();

// prepare customergroups in sql query
$customergroups = " AND EXISTS (SELECT 1 FROM customergroups g, customerassignments ca 
	WHERE c.id = ca.customerid 
		AND g.id = ca.customergroupid 
		AND (%groups)) ";
$groupnames = ConfigHelper::getConfig('jambox.tvbilling_customergroups');
$groupsql = "";
$groups = preg_split("/[[:blank:]]+/", $groupnames, -1, PREG_SPLIT_NO_EMPTY);
foreach ($groups as $group) {
	if (!empty($groupsql))
		$groupsql .= " OR ";
	$groupsql .= "UPPER(g.name) = UPPER('".$group."')";
}
if (!empty($groupsql))
	$customergroups = preg_replace("/\%groups/", $groupsql, $customergroups);

$res = $DB->GetAll("SELECT b.* FROM tv_billingevent b
	JOIN customers c ON c.id = b.customerid
	WHERE c.tv_suspend_billing = ? AND (docid = 0 OR docid IS NULL)
		AND be_selling_date >= ? AND be_selling_date <= ?
		" . (!empty($groupnames) ? $customergroups : "") . "
	ORDER BY account_id", array(0, $start_date, $end_date));
if (!empty($res))
	foreach ($res as $key => $r) {
		if (!isset($to_insert[$r['cust_number']]))
			$to_insert[$r['cust_number']] = array();
		$to_insert[$r['cust_number']][] = $r;
	}
if (empty($to_insert))
	die("No billing records!\n");

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
if (empty($numberplans))
	die("No invoice number plans found!\n");

//$DB->BeginTrans();
foreach ($to_insert as $key => $i){
	$customerid = $i[0]['customerid'];
	if (empty($customerid) || !$DB->GetOne("SELECT id FROM customers WHERE tv_cust_number = ?", array($i[0]['cust_number'])))
		continue;

	if ($addinvoices)
		$document = null;
	else
		$document = $DB->GetRow("SELECT MAX(d.id) AS id, MAX(itemid) AS itemid FROM documents d
				JOIN invoicecontents ic ON ic.docid = d.id
				WHERE customerid = ? AND sdate >= ? AND sdate < ?
				GROUP BY d.id", array($customerid, $starttime, $endtime));
	if (empty($document)) {
		$customer = $DB->GetRow("SELECT lastname, name, address, city, zip, ssn, ten, countryid, divisionid, paytime, paytype
			FROM customers WHERE id = ?", array($customerid));

		if ($customer['paytime'] != -1)
			$paytime = $customer['paytime'];
		elseif (($paytime = $DB->GetOne("SELECT inv_paytime FROM divisions WHERE id = ?",
			array($customer['divisionid']))) === NULL)
			$paytime = ConfigHelper::getConfig('invoices.paytime');

		if ($customer['paytype'])
			$paytype = $customer['paytype'];
		elseif ($paytype = $DB->GetOne("SELECT inv_paytype FROM divisions WHERE id = ?",
			array($customer['divisionid'])) === NULL)
			if (isset($PAYTYPES[$paytype]))
				$paytype = intval(ConfigHelper::getConfig('invoices.paytype'));

		$numberplanid = $customer['numberplanid'];
		if (empty($numberplanid))
			$numberplanid = $numberplans[$customer['divisionid']];
		if (!isset($numbertemplates[$numberplanid]))
			$numbertemplates[$numberplanid] = $DB->GetOne("SELECT template FROM numberplans WHERE id = ?", array($numberplanid));
		$number = $LMS->GetNewDocumentNumber(DOC_INVOICE, $numberplanid, $cdate);

		$division = $DB->GetRow("SELECT name, shortname, address, city, zip, countryid, ten, regon,
			account, inv_header, inv_footer, inv_author, inv_cplace 
			FROM divisions WHERE id = ?",array($customer['divisionid']));
	
		$DB->Execute("INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
			customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype,
			div_name, div_shortname, div_address, div_city, div_zip, div_countryid, div_ten, div_regon,
			div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber) 
			VALUES(?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($number, $numberplanid, $customer['countryid'], $customer['divisionid'], 
			$customerid, $customer['lastname']." ".$customer['name'], $customer['address'], $customer['zip'],
			$customer['city'], $customer['ten'], $customer['ssn'], $cdate, $cdate, $paytime, 2,
			($division['name'] ? $division['name'] : ''), ($division['shortname'] ? $division['shortname'] : ''),
			($division['address'] ? $division['address'] : ''), ($division['city'] ? $division['city'] : ''),
			($division['zip'] ? $division['zip'] : ''), ($division['countryid'] ? $division['countryid'] : 0),
			($division['ten'] ? $division['ten'] : ''), ($division['regon'] ? $division['regon'] : ''),
			($division['account'] ? $division['account'] : ''), ($division['inv_header'] ? $division['inv_header'] : ''),
			($division['inv_footer'] ? $division['inv_footer'] : ''), ($division['inv_author'] ? $division['inv_author'] : ''),
			($division['inv_cplace'] ? $division['inv_cplace'] : ''), $fullnumber));
		$docid = $DB->GetLastInsertID('documents');
		$itemid = 0;
	} else {
		$docid = $document['id'];
		$itemid = $document['itemid'];
	}

	foreach ($i as $idx => $item) {
		$taxval =  $item['be_vat'] * 100;
		$taxid = $DB->GetOne("SELECT id FROM taxes WHERE value = ?
			AND validfrom < ? AND (validto = 0 OR validto <= ?)",
			array($taxval, $cdate, $cdate));

		$itemid++;
		$be_gross = str_replace(',', '.', $item['be_gross']);

		$DB->Execute("INSERT INTO invoicecontents (docid, value, taxid, prodid,
			content, count, description, itemid)
			VALUES (?, ?, ?, '', 'usl.', 1, ?, ?)",
			array($docid, $item['be_gross'], $taxid, $item['be_desc'], $itemid));

		$value =  str_replace(",", ".", $item['be_gross'] * -1);
		$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid)
			VALUES (?, ?, ?, ?, ?, ?, ?)",
			array($cdate, $value, $taxid, $customerid, $item['be_desc'], $docid, $itemid));

		$DB->Execute("UPDATE tv_billingevent SET docid = ? WHERE id = ?", array($docid, $item['id']));
	}
}
//$DB->RollbackTrans();

$DB->Destroy();

?>
