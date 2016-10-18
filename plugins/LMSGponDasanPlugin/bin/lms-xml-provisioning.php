#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	'i:' => 'onu-id:',
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
lms-xml-provisioning.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-xml-provisioning.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-i, --onu-id=<onu-id>           generate xml file only for selected onu

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-xml-provisioning.php
(C) 2001-2016 LMS Developers

EOF;
}

$onuid = isset($options['onu-id']) ? intval($options['onu-id']) : 0;

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE =  DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file [' . $CONFIG_FILE . ']!' . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
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

// Include required files (including sequence is important)

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name
	. DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'definitions.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$AUTH = null;
$GPON = new GPON_DASAN();

//$xml_provisioning_admin_login = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_admin_login', 'admin');
$xml_provisioning_admin_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_admin_password', 'password');
$xml_provisioning_telnet_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_telnet_password', $xml_provisioning_admin_password);
$xml_provisioning_web_port = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_web_port', '80');
$path = explode(DIRECTORY_SEPARATOR, dirname(realpath($argv[0])));
array_pop($path);
$plugin_dir_name = implode(DIRECTORY_SEPARATOR, $path);
$xml_provisioning_filename = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_filename',
	$plugin_dir_name . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . '%sn%.xml');

$query = "SELECT o.id AS gpononuid, o.name, o.properties, m.id AS modelid, m.name AS model,
		p.name AS profile, o.onudescription AS description,
		h1.details AS host1, h2.details AS host2, p1.details AS sip1, p2.details AS sip2,
		disabledports.portnames, disabledports.portids,
		(SELECT customersid FROM " . GPON_DASAN::SQL_TABLE_GPONONU2CUSTOMERS . " o2c
			WHERE o2c.gpononuid = o.id ORDER BY id LIMIT 1) AS customerid
	FROM " . GPON_DASAN::SQL_TABLE_GPONONU . " o
	JOIN " . GPON_DASAN::SQL_TABLE_GPONONUMODELS . " m ON m.id = o.gpononumodelsid
	JOIN " . GPON_DASAN::SQL_TABLE_GPONOLTPROFILES . " p ON p.id = o.gponoltprofilesid
	LEFT JOIN (
		SELECT n.id, (" . $DB->Concat('INET_NTOA(ipaddr)', "'/'", 'mask', "'/'", 'gateway', "'/'", 'n.name', "'/'", 'passwd', "'/'", 'n.authtype', "'/'", 'vlanid') . ") AS details
		FROM nodes n
		JOIN networks net ON net.address = (n.ipaddr & INET_ATON(mask))
	) h1 ON h1.id = o.host_id1
	LEFT JOIN (
		SELECT n.id, (" . $DB->Concat('INET_NTOA(ipaddr)', "'/'", 'mask', "'/'", 'gateway', "'/'", 'n.name', "'/'", 'passwd', "'/'", 'n.authtype', "'/'", 'vlanid') . ") AS details
		FROM nodes n
		JOIN networks net ON net.address = (n.ipaddr & INET_ATON(mask))
	) h2 ON h2.id = o.host_id2
	LEFT JOIN (
		SELECT id, (" . $DB->Concat('login', "'/'", 'passwd', "'/'", 'login') . ") AS details FROM voipaccounts
	) p1 ON p1.id = o.voipaccountsid1
	LEFT JOIN (
		SELECT id, (" . $DB->Concat('login', "'/'", 'passwd', "'/'", 'login') . ") AS details FROM voipaccounts
	) p2 ON p2.id = o.voipaccountsid2
	LEFT JOIN (
		SELECT onuid,
			(" . $DB->GroupConcat('t.name') . ") AS portnames,
			(" . $DB->GroupConcat('portid') . ") AS portids
		FROM " . GPON_DASAN::SQL_TABLE_GPONONUPORTS . " p
		JOIN " . GPON_DASAN::SQL_TABLE_GPONONUPORTTYPES . " t ON t.id = p.typeid
		WHERE portdisable = 1
		GROUP BY onuid
	) disabledports ON disabledports.onuid = o.id
	WHERE xmlprovisioning = 1"
	. (!empty($onuid) ? " AND o.id = " . $onuid : '');
$onus = $DB->GetAll($query);

if (empty($onus))
	die("No gpon onus found!" . PHP_EOL);

$models = $DB->GetAllByKey("SELECT id, name, xmltemplate FROM " . GPON_DASAN::SQL_TABLE_GPONONUMODELS
	. " WHERE xmltemplate <> ''", 'id');
if (empty($models)) {
	if (!$quiet)
		echo "No gpon onu models found!" . PHP_EOL;
	die;
}

$SMARTY = new Smarty;
$SMARTY->setTemplateDir(null);
$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);

$default_properties = array(
//	'admin_login' => $xml_provisioning_admin_login,
	'admin_password' => $xml_provisioning_admin_password,
	'telnet_password' => $xml_provisioning_telnet_password,
	'web_port' => $xml_provisioning_web_port,
	'modified_time' => strftime('%Y-%m-%d %H:%M:%S'),
);

foreach ($onus as $onu) {
	extract($onu);
	if (!isset($models[$modelid])) {
		if (!$quiet)
			echo "Onu " . $name . " (model " . $model . ") doesn't support xml provisioning!" . PHP_EOL;
		continue;
	}

	$filename = str_replace('%sn%', $name, $xml_provisioning_filename);
	if (!$quiet)
		echo "Generating ${filename} file ..." . PHP_EOL;

	$SMARTY->clearAllAssign();
	$SMARTY->assign($default_properties);
	if (!empty($properties)) {
		$properties = unserialize($properties);
		if ($properties !== false) {
			if (isset($properties['vlans'])) {
				$properties['vlan_ports'] = array();
				$ports = array_keys($properties['vlans']);
				$ports = array_combine($ports, array_fill(0, count($ports), false));
				foreach ($properties['vlans'] as $portname => $vlanid) {
					if ($vlanid == '')
						$vlanid = 'default';
					if (!isset($properties['vlan_ports'][$vlanid]))
						$properties['vlan_ports'][$vlanid] = $ports;
					$properties['vlan_ports'][$vlanid][$portname] = true;
				}
			}
			$SMARTY->assign($properties);
		}
	}

	$SMARTY->assign('portsettings', $GPON->GetGponOnuPorts($gpononuid));

	$i = 1;
	foreach (array($host1, $host2) as $host) {
		if (!empty($host)) {
			list ($host_ip, $host_mask, $host_gateway, $host_login, $host_password, $host_authtype, $host_vlanid) =
				explode('/', $host);
			$SMARTY->assign(array(
				'host' . $i . '_ip' => $host_ip,
				'host' . $i . '_mask' => $host_mask,
				'host' . $i . '_gateway' => $host_gateway,
				'host' . $i . '_login' => $host_login,
				'host' . $i . '_password' => $host_password,
				'host' . $i . '_authtype' => $host_authtype,
				'host' . $i . '_vlanid' => $host_vlanid,
			));
		}
		$i++;
	}

	$i = 1;
	foreach (array($sip1, $sip2) as $sip) {
		if (!empty($sip)) {
			list ($sip_login, $sip_password, $sip_phone) =
				explode('/', $sip);
			$SMARTY->assign(array(
				'sip' . $i . '_login' => $sip_login,
				'sip' . $i . '_password' => $sip_password,
				'sip' . $i . '_phone' => $sip_phone,
			));
		}
		$i++;
	}

	$SMARTY->assign('customerid', intval($customerid));

	$contents = $SMARTY->fetch('string:' . $models[$modelid]['xmltemplate']);

	$fh = fopen($filename, "w+");
	if (empty($fh)) {
		if (!$quiet)
			echo "Unable to create xml file ${filename}!" . PHP_EOL;
		continue;
	}
	fwrite($fh, $contents);
	fclose($fh);
/*
	$contents .= sprintf("\tDasan-Gpon-Onu-Profile = \"%s\"," . PHP_EOL . "\tDasan-Gpon-Onu-Description += \"%s\"",
		$onu['profile'], preg_replace("/(\r)?" . PHP_EOL . "/", ', ', $onu['description']));
	$contents .= PHP_EOL . PHP_EOL;
*/
}

$DB->Destroy();

?>
