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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	's:' => 'startdate:',
	'e:' => 'enddate:',
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
tvbillingimport.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
tvbillingimport.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-s, --start-date=YYYY/MM/DD     start date for imported billing events
-e, --end-date=YYYY/MM/DD       end date for imported billing events

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
tvbillingimport.php
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
	// can't work without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

if (array_key_exists('start-date', $options)) {
	$date = explode('/', $options['start-date']);
	$start_date = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
} else
	$start_date = date("Y-m-d", mktime(0, 0, 0, intval(strftime("%m")) - 1, 1, strftime("%Y")));

if (array_key_exists('end-date', $options)) {
	$date = explode('/', $options['end-date']);
	$end_date = mktime(23, 59, 59, $date[1], $date[2], $date[0]);
} else
	$end_date = time();

$AUTH = null;
$SYSLOG = null;
$LMSTV = new LMSTV($DB, $AUTH, $SYSLOG);

$res = $LMSTV->GetBillingEvents($start_date, $end_date);

if (!empty($res)) {
	foreach ($res as $key => $r) {
		try {
			if ($DB->GetOne('SELECT beid FROM tv_billingevent WHERE beid = ?', array($r['id'])))
				continue;
			$DB->Execute('INSERT INTO tv_billingevent (customerid, account_id, be_selling_date, be_desc, be_vat, be_gross, be_b2b_netto, group_id, cust_number, package_id, hash, beid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				array(
					empty($r['cust_external_id']) ? 0 : $r['cust_external_id'],
					$r['account_id'],
					$r['be_selling_date'],
					$r['be_desc'],
					$r['be_vat'],
					$r['be_gross'],
					$r['be_b2b_netto'],
					empty($r['group_id']) ? 0 : $r['group_id'],
					$r['cust_number'],
					$r['package_id'],
					md5($r['id']),
					$r['id'],
				)
			);
		} catch (Exception $e) {
			print_r($e);
		}
	}
}

$DB->Destroy();

?>
