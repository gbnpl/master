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

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	's:' => 'section:',
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
lms-radiususers-dasan.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-radiususers-dasan.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-radiususers-dasan.php
(C) 2001-2015 LMS Developers

EOF;
}

$config_section = (array_key_exists('section', $options) && preg_match('/^[a-z0-9-_]+$/i', $options['section']) ? $options['section'] : 'radiususers');

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE =  DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

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

// Include required files (including sequence is important)

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$config_owneruid = ConfigHelper::getConfig($config_section . '.config_owneruid', 0);
$config_ownergid = ConfigHelper::getConfig($config_section . '.config_ownergid', 0);
$config_permission = ConfigHelper::getConfig($config_section . '.config_permission', '0644');
$config_file = ConfigHelper::getConfig($config_section . '.config_file', '/etc/raddb/radius_users');

$query = "SELECT o.name, m.name AS model, p.name AS profile, o.onudescription AS description,
		host1.ip AS ip1, host2.ip AS ip2,
		phone1.phone AS sipnumber1, phone1.auth AS sipauth1,
		phone2.phone AS sipnumber2, phone2.auth AS sipauth2,
		disabledports.portnames, disabledports.portids
	FROM gpononu o
	JOIN gpononumodels m ON m.id = o.gpononumodelsid
	JOIN gponoltprofiles p ON p.id = o.gponoltprofilesid
	LEFT JOIN (
		SELECT n.id, (" . $DB->Concat('INET_NTOA(ipaddr)', "'/'", 'MASK2PREFIX(INET_ATON(mask))', "' '", 'gateway') . ") AS ip FROM nodes n
		JOIN networks net ON net.address = (n.ipaddr & INET_ATON(mask))
	) host1 ON host1.id = o.host_id1
	LEFT JOIN (
		SELECT n.id, (" . $DB->Concat('INET_NTOA(ipaddr)', "'/'", 'MASK2PREFIX(INET_ATON(mask))', "' '", 'gateway') . ") AS ip FROM nodes n
		JOIN networks net ON net.address = (n.ipaddr & INET_ATON(mask))
	) host2 ON host2.id = o.host_id2
	LEFT JOIN (
		SELECT id, phone, (" . $DB->Concat('login', "' '", 'passwd') . ") AS auth FROM voipaccounts
	) phone1 ON phone1.id = o.voipaccountsid1
	LEFT JOIN (
		SELECT id, phone, (" . $DB->Concat('login', "' '", 'passwd') . ") AS auth FROM voipaccounts
	) phone2 ON phone2.id = o.voipaccountsid2
	LEFT JOIN (
		SELECT onuid,
			(" . $DB->GroupConcat('t.name') . ") AS portnames,
			(" . $DB->GroupConcat('portid') . ") AS portids
		FROM gpononuport p
		JOIN gpononuportstype t ON t.id = p.typeid
		WHERE portdisable = 1
		GROUP BY onuid
	) disabledports ON disabledports.onuid = o.id";
$onus = $DB->GetAll($query);

$fh = fopen($config_file, "w");
if ($fh == NULL)
	die("Fatal error: Unable to write " . $config_file . ", exiting." . PHP_EOL);

if (!empty($onus))
	foreach ($onus as $onu) {
		$contents = sprintf("%s\tCleartext-Password := \"%s\"" . PHP_EOL,
			$onu['name'], $onu['model'], $onu['model']);
		$contents .= sprintf("\tDasan-Gpon-Onu-Profile = \"%s\"," . PHP_EOL . "\tDasan-Gpon-Onu-Description += \"%s\"",
			$onu['profile'], preg_replace("/(\r)?" . PHP_EOL . "/", ', ', $onu['description']));
		for ($i = 1; $i <= 2; $i++)
			if (!empty($onu["ip$i"]))
				$contents .= sprintf("," . PHP_EOL . "\tDasan-Gpon-Onu-Static-Ip += \"%d %s\"",
					$i, $onu["ip$i"]);
		for ($i = 1; $i <= 2; $i++)
			if (!empty($onu["sipnumber$i"])) {
				$contents .= sprintf("," . PHP_EOL . "\tDasan-Gpon-Onu-Voip-Sip-Number += \"%d %s\"",
					$i, $onu["sipnumber$i"]);
				$contents .= sprintf("," . PHP_EOL . "\tDasan-Gpon-Onu-Voip-Sip-Auth += \"%d %s\"",
					$i, $onu["sipauth$i"]);
			}
		if (!empty($onu['portids'])) {
			$portids = explode(',', $onu['portids']);
			$portnames = explode(',', $onu['portnames']);
			foreach ($portids as $idx => $portid)
				$contents .= sprintf("," . PHP_EOL . "\tDasan-Gpon-Onu-Uni-Port-Admin += \"%s %d disable\"",
					$portnames[$idx], $portid);
		}
		$contents .= PHP_EOL . PHP_EOL;
		fwrite($fh, $contents);
	}

fclose($fh);
chmod($config_file, intval($config_permission, 8));
chown($config_file, $config_owneruid);
chgrp($config_file, $config_ownergid);

$DB->Destroy();

?>
