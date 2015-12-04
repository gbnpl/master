#!/usr/bin/php
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
	'i' => 'incremental',
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
lms-plicbd-localisation.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-plicbd-localisation.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-i, --incremental               use incremental mode

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-plicbd-localisation.php
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
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

$incremental = array_key_exists('incremental', $options);

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

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

$AUTH = NULL;

$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPlicbdPlugin::plugin_directory_name . DIRECTORY_SEPARATOR
	. 'lib' . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_ui_language . DIRECTORY_SEPARATOR . 'strings.php');

/* ********************************************************************
   We should have all hard work here which is being done by our script!
   ********************************************************************/

if (!extension_loaded('zip'))
	die("Zip extension not loaded! In order to use this extension you must compile PHP with zip support by using the --enable-zip configure option." . PHP_EOL);

// getting voip accounts from LMS database
$voipaccounts = $DB->GetAllByKey("SELECT number AS phone, customer_name AS hiperus_customer_name,
		c.lastname, c.name, " . $DB->Concat('c.lastname', "' '", 'c.name') 
			. " AS owner, c.type,
				(SELECT contact FROM customercontacts WHERE customerid = c.id AND type = ? LIMIT 1) AS email,
			c.icn, c.ssn, hp.create_date AS timestamp,
		location, location_city, location_street, location_house, location_flat,
		lc.ident AS city_ident, lst.ident AS street_ident,
		lb.ident AS borough_ident, lb.type AS borough_type, ld.ident AS district_ident, ls.ident AS state_ident,
		lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name
	FROM hv_pstn hp
	JOIN hv_terminal ht ON ht.username = hp.terminal_name
	JOIN hv_customers hc ON hc.id = hp.customerid
	JOIN customers c ON c.id = hc.ext_billing_id
	LEFT JOIN location_streets lst ON lst.id = ht.location_street
	LEFT JOIN location_cities lc ON lc.id = ht.location_city
	LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
	LEFT JOIN location_districts ld ON ld.id = lb.districtid
	LEFT JOIN location_states ls ON ls.id = ld.stateid
	WHERE location_city IS NOT NULL", 'phone', array(CONTACT_EMAIL));
if (empty($voipaccounts))
	$voipaccounts = array();

// getting LMS plicbd state table
$plicbdaccounts = $DB->GetAllByKey("SELECT pl.*,
			lb.id AS borough_id, ld.id AS district_id, ls.id AS state_id,
			lb.name AS borough_name, ld.name AS district_name, ls.name AS state_name
		FROM plicbdlocalisation pl
		LEFT JOIN location_cities lc ON lc.id = pl.location_city
		LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
		LEFT JOIN location_districts ld ON ld.id = lb.districtid
		LEFT JOIN location_states ls ON ls.id = ld.stateid", 'phone');
if (empty($plicbdaccounts))
	$plicbdaccounts = array();

// searching for differencies between current voip data and previous voip data state
$phones = array();
foreach ($voipaccounts as $phone => $voipaccount)
	if (array_key_exists($phone, $plicbdaccounts)) {
		if ($voipaccount['owner'] != $plicbdaccounts[$phone]['owner']
			|| $voipaccount['location'] != $plicbdaccounts[$phone]['location']
			|| $voipaccount['location_city'] != $plicbdaccounts[$phone]['location_city']) {
			$voipaccount['operation'] = 'UPD';
			$phones[$phone] = $voipaccount;
		}
	} else {
		// new phone number added in LMS database
		$voipaccount['operation'] = 'INS';
		$phones[$phone] = $voipaccount;
	}
if ($incremental)
	foreach ($plicbdaccounts as $phone => $plicbdaccount)
		if (!array_key_exists($phone, $voipaccounts)) {
			// phone numer deleted in LMS database
			$plicbdaccount['operation'] = 'DEL';
			$phones[$phone] = $plicbdaccount;
		}

// variables, constants and functions used for creation of phone localisation data files
define('XML_HEADER', "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<request>\n");
define('XML_FOOTER', "</request>\n");
define('DIRLIST_XML_HEADER', "<?xml version=\"1.0\"?>\n<dirlist>\n");
define('DIRLIST_XML_FOOTER', "</dirlist>\n");
define('ROOT_DIR', ConfigHelper::getConfig('plicbd.localisation_root_dir', getcwd()));

$fulldate = strftime("%Y%m%d%H%M%S");
$date = strftime("%Y%m%d");
$hour = strftime("%H");
$type = $incremental ? 'INCR' : 'FULL';

$lastupdate = ConfigHelper::getConfig('plicbd.localisation_last_update', "${date}01");
$filenr = substr($lastupdate, 0, 8) == $date ? intval(substr($lastupdate, 8, 2)) : 1;

$dircomponents = array($type, $date, "${date}_${hour}", "${date}_${hour}_0001");
$files = array();

$dirlist = '';
foreach (array_slice($dircomponents, 0, 3) as $dirpart) {
	$dirlist .= (mb_strlen($dirlist) ? DIRECTORY_SEPARATOR : '') . $dirpart;
	if (!is_dir(ROOT_DIR . DIRECTORY_SEPARATOR . $dirlist))
		mkdir(ROOT_DIR . DIRECTORY_SEPARATOR . $dirlist);
}
$dirlist .= DIRECTORY_SEPARATOR . "DIRLIST_${type}_${date}_${hour}.XML";

$countrycode = ConfigHelper::getConfig('plicbd.country_code', '48');

function create_data_record($voipaccount) {
	global $fulldate, $countrycode;

	$buffer = '';
	$buffer .= "\t<emerep ver=\"3.2.0\">\n";
	$buffer .= "\t\t<eme_event eme_trigger=\"EME_ORG\">\n";
	$buffer .= "\t\t\t<eme_pos>\n";
	$buffer .= "\t\t\t\t<msid type=\"MSISDN\">${countrycode}${voipaccount['phone']}</msid>\n";
	$buffer .= "\t\t\t\t<public>false</public>\n";
	$buffer .= "\t\t\t\t<pbx>false</pbx>\n";
	$buffer .= "\t\t\t\t<pd>\n";
	if ($voipaccount['timestamp']) {
		preg_match('/^(?<year>[0-9]{4})-(?<month>[0-9]{2})-(?<day>[0-9]{2}) (?<hour>[0-9]{2}):(?<minute>[0-9]{2})$/', $voipaccount['timestamp'], $m);
		$timestamp = mktime($m['hour'], $m['minute'], 0, $m['month'], $m['day'], $m['year']);
		$timestamp = strftime("%Y%m%d%H%M%S", $timestamp);
	} else
		$timestamp = $fulldate;
	$buffer .= "\t\t\t\t\t<time utc_off=\"+0100\">$timestamp</time>\n";
	$buffer .= "\t\t\t\t\t<lev_conf>100</lev_conf>\n";
	$buffer .= "\t\t\t\t</pd>\n";
	$buffer .= "\t\t\t</eme_pos>\n";
	if (in_array($voipaccount['operation'], array('INS', 'UPD', 'DEL')))
		$buffer .= "\t\t\t<location_operation>${voipaccount['operation']}</location_operation>\n";
	if (in_array($voipaccount['operation'], array('INS', 'UPD', ''))) {
		$buffer .= "\t\t\t<caller_location>\n";
		if ($voipaccount['type'] == 0) {
			$customer = "${voipaccount['name']}|${voipaccount['lastname']}|${voipaccount['location']}|"
				. (!empty($voipaccount['email']) ? $voipaccount['email'] : '') . "|"
				. (!empty($voipaccount['ssn']) ? "1:${voipaccount['ssn']}" : (!empty($voipaccount['icn']) ? "2:${voipaccount['icn']}" : ''));
		} else
			$customer = "${voipaccount['owner']}||${voipaccount['location']}";
		$buffer .= "\t\t\t\t<customer_name>$customer</customer_name>\n";
		$terc = sprintf("%02d%02d%02d%s", $voipaccount['state_ident'], $voipaccount['district_ident'], $voipaccount['borough_ident'], $voipaccount['borough_type']);
		$buffer .= "\t\t\t\t<Address_line1>*$terc</Address_line1>\n";
		//$simc = sprintf("%07d", $voipaccount['city_ident']);
		$simc = $voipaccount['city_ident'];
		$buffer .= "\t\t\t\t<Address_line2>*$simc</Address_line2>\n";
		$buffer .= "\t\t\t\t<Address_line3>*${voipaccount['street_ident']}</Address_line3>\n";
		$buffer .= "\t\t\t\t<Address_line4>${voipaccount['location_house']}</Address_line4>\n";
		$buffer .= "\t\t\t\t<Address_line5>${voipaccount['location_flat']}</Address_line5>\n";
		$buffer .= "\t\t\t\t<Address_line6></Address_line6>\n";
		$buffer .= "\t\t\t\t<postcode></postcode>\n";
		$buffer .= "\t\t\t</caller_location>\n";
	}
	$buffer .= "\t\t</eme_event>\n\t</emerep>\n";

	return $buffer;
}

function write_to_file($type, $date, $hour, $filenr, $buffer) {
	global $files, $dircomponents;

	$dir = '';
	foreach ($dircomponents as $dirpart) {
		$dir .= (mb_strlen($dir) ? DIRECTORY_SEPARATOR : '') . $dirpart;
		if (!file_exists(ROOT_DIR . DIRECTORY_SEPARATOR . $dir))
			if (!mkdir(ROOT_DIR . DIRECTORY_SEPARATOR . $dir))
				return false;
		if (!is_dir(ROOT_DIR . DIRECTORY_SEPARATOR . $dir))
			return false;
	}

	$number = sprintf("%08d", $filenr);
	$filename =  "${type}_${date}_${hour}_${number}";
	$zip = new ZipArchive();
	if (!$zip->open(ROOT_DIR . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . "$filename.ZIP", ZIPARCHIVE::CREATE))
		return false;
	$zip->addFromString("$filename.XML", XML_HEADER . $buffer . XML_FOOTER);
	$zip->close();

	$files[] = array(
		'name' => "$filename.ZIP",
		'directory' => $dir,
	);
	return true;
}

if (!$incremental)
	$phones = $voipaccounts;

// writing phone localisation data files
$records = 0;
$buffer = '';
foreach ($phones as $voipaccount) {
	if (!$incremental)
		$voipaccount['operation'] = '';
	$buffer .= create_data_record($voipaccount);
	$records++;
	if ($records > 999) {
		write_to_file($type, $date, $hour, $filenr, $buffer);
		$filenr++;
		$records = 0;
		$buffer = '';
	}
}
if ($records)
	write_to_file($type, $date, $hour, $filenr, $buffer);

$lastupdate = sprintf("%s%02d", $date, $filenr + 1);
if (ConfigHelper::getConfig('plicbd_localisation_last_update'))
	$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?",
		array($lastupdate, 'plicbd', 'localisation_last_update'));
else
	$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
		array('plicbd', 'localisation_last_update', $lastupdate));

// writing content of dirlist file
if (!empty($files)) {
	$buffer = '';
	foreach ($files as $file) {
		$buffer .= "\t<file>\n";
		$buffer .= "\t\t<name>${file['name']}</name>\n";
		$buffer .= "\t\t<directory>${file['directory']}</directory>\n";
		$buffer .= "\t</file>\n";
	}
}
$fh = fopen(ROOT_DIR . DIRECTORY_SEPARATOR . $dirlist, "w+");
if (!$fh)
	die("Error creating dirlist file!" . PHP_EOL);
fwrite($fh, DIRLIST_XML_HEADER . $buffer . DIRLIST_XML_FOOTER);
fclose($fh);

// updating LMS plicbd state table
$DB->BeginTrans();

if (!$incremental)
	$DB->Execute("DELETE FROM plicbdlocalisation");
foreach ($phones as $voipaccount)
	if ($incremental)
		switch ($voipaccount['operation']) {
			case 'INS':
				$DB->Execute("INSERT INTO plicbdlocalisation (phone, owner, location, location_city)
					VALUES (?, ?, ?, ?)", array($voipaccount['phone'], $voipaccount['owner'],
						$voipaccount['location'], $voipaccount['location_city']));
				break;
			case 'DEL':
				$DB->Execute("DELETE FROM plicbdlocalisation WHERE phone = ?", array($voipaccount['phone']));
				break;
			case 'UPD':
				$DB->Execute("UPDATE plicbdlocalisation SET owner = ?, location = ?, location_city = ? WHERE phone = ?",
					array($voipaccount['owner'], $voipaccount['location'], $voipaccount['location_city'], $voipaccount['phone']));
				break;
		}
	else
		$DB->Execute("INSERT INTO plicbdlocalisation (phone, owner, location, location_city)
			VALUES (?, ?, ?, ?)", array($voipaccount['phone'], $voipaccount['owner'],
				$voipaccount['location'], $voipaccount['location_city']));

$DB->CommitTrans();

$notification_mail = ConfigHelper::getConfig('plicbd.empty_localisation_notification_mail');
$notification_from = ConfigHelper::getConfig('plicbd.empty_localisation_notification_from');
$notification_subject = ConfigHelper::getConfig('plicbd.empty_localisation_notification_subject');
$notification_body = ConfigHelper::getConfig('plicbd.empty_localisation_notification_body');
if (empty($notification_mail) || empty($notification_from) || empty($notification_subject) || empty($notification_body))
	die;

// getting voip accounts from LMS database which don't have teryt localisation set
$voipaccounts = $DB->GetAll("SELECT id AS terminalid, customerid AS accountid,
		extensions AS phone
	FROM hv_terminal
	WHERE location_city IS NULL");
if (empty($voipaccounts))
	die;

function replace_account_symbols($text, $account) {
	$text = str_replace('%accountid%', $account['accountid'], $text);
	$text = str_replace('%terminalid%', $account['terminalid'], $text);
	$phones = preg_split('/\r?\n/', $account['phone'], -1, PREG_SPLIT_NO_EMPTY);
	$text = str_replace('%phone%', implode(', ', $phones), $text);
	return $text;
}

$body = '';
foreach ($voipaccounts as $account)
	$body .= replace_account_symbols($notification_body, $account) . "\n";
$headers = array(
	'From' => $notification_from,
	'To' => $notification_mail,
	'Subject' => $notification_subject,
);
$result = $LMS->SendMail($notification_mail, $headers, $body);
if (is_string($result))
	echo $result . PHP_EOL;

?>
