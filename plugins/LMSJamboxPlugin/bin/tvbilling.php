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

function localtime2() {
	global $fakedate;
	if (!empty($fakedate)) {
		$date = explode("/", $fakedate);
		return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
	} else
		return time();
}

$fakedate = (array_key_exists('fakedate', $options) ? $options['fakedate'] : NULL);
$cdate = strftime("%s", localtime2());
$numberplanid = 1; // FIXMETVSGT NA PRAWIDŁOWY

$to_insert = array();

$res = $DB->GetAll("SELECT * FROM tv_billingevent WHERE docid = 0 OR docid IS NULL ORDER BY account_id");
if (!empty($res))
	foreach ($res as $key => $r) {
		if (!isset($to_insert[$r['cust_number']]))
			$to_insert[$r['cust_number']] = array();
		$to_insert[$r['cust_number']][] = $r;
	}

foreach ($to_insert as $key => $i){
	//$customerid = $DB->GetOne('SELECT id FROM customers WHERE cust_number = ?', array($i[0]['customerid']));
	$customerid = $i[0]['customerid'];
	$customer = $DB->GetRow("SELECT lastname, name, address, city, zip, ssn, ten, countryid, divisionid, paytime
		FROM customers WHERE id = ?", array($customerid));
	$number = $LMS->GetNewDocumentNumber(DOC_INVOICE, $numberplanid, $cdate);
	
	$paytime = $customer['paytime'];
	if ($paytime == -1) $paytime = '14';

	$DB->Execute("INSERT INTO documents (number, numberplanid, type, countryid, divisionid, 
		customerid, name, address, zip, city, ten, ssn, cdate, sdate, paytime, paytype) 
		VALUES(?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		array($number, $numberplanid, $customer['countryid'], $customer['divisionid'], 
		$customerid, $customer['lastname']." ".$customer['name'], $customer['address'], $customer['zip'],
		$customer['city'], $customer['ten'], $customer['ssn'], $cdate, $cdate, $paytime, 2));
	$docid = $DB->GetLastInsertID('documents');

	$itemid = 0;
	foreach ($i as $idx => $item) {
		$taxval =  $item['be_vat'] * 100;
		$taxid = $DB->GetOne("SELECT id FROM taxes WHERE value = ?
			AND validfrom < ? AND (validto = 0 OR validto <= ?)",
			array($taxval, $cdate, $cdate));	
	
		$itemid++;
		$be_gross = str_replace(',', '.', $item['be_gross']);

		$DB->Execute("INSERT INTO invoicecontents (docid, value, taxid, prodid,
			content, count, description, tariffid, itemid)
			VALUES (?, ?, ?, '', 'usl.', 1, ?, 'FIXMETVSGT', ?)",
			array($docid, $item['be_gross'], $taxid, $item['be_desc'], $itemid));

		$value =  str_replace(",", ".", $item['be_gross'] * -1);
		$DB->Execute("INSERT INTO cash (time, value, taxid, customerid, comment, docid, itemid)
			VALUES (?, ?, ?, ?, ?, ?, ?)",
			array($cdate, $value, $taxid, $customerid, 'usl.', $item['be_desc'], $docid, $itemid));
					
		$DB->Execute('UPDATE tv_billingevent SET docid = ? WHERE id = ?', array($docid, $item['id']));
	}
}

$DB->Destroy();

?>
