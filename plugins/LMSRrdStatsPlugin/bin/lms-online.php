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
lms-online.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-online.php
(C) 2001-2015 LMS Developers

-f, --traffic-log-file=/var/log/traffic.log  traffic log file (default: /var/log/traffic.log);
-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-online.php
(C) 2001-2015 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [" . $CONFIG_FILE."]!" . PHP_EOL);

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

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

define('RRD_DIR', ConfigHelper::getConfig('rrdstats.directory',
	dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'rrd'));
define('RRDTOOL_BINARY', ConfigHelper::getConfig('rrdstats.rrdtool_binary', '/usr/bin/rrdtool'));

// Include required files (including sequence is important)

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
//include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$online_freq = intval(ConfigHelper::getConfig('rrdstats.online_freq',
	intval(ConfigHelper::getConfig('rrdstats.stat_freq',
		intval(ConfigHelper::getConfig('phpui.stat_freq', 12))
	))
));

$online = $DB->GetOne('SELECT COUNT(*) FROM nodes WHERE ?NOW? - lastonline <= ?',
	array($online_freq * 2));
$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . 'online.rrd';
if (!file_exists($rrd_file)) {
	$cmd = RRDTOOL_BINARY . " create ${rrd_file} --step ${online_freq}"
		. ' DS:online:GAUGE:' . ($online_freq * 2) . ':0:U'
		. ' RRA:AVERAGE:0.5:1:' . (183 * 86400 / $online_freq)
		. ' RRA:MAX:0.5:1:' . (183 * 86400 / $online_freq)
		. ' RRA:MIN:0.5:1:' . (183 * 86400 / $online_freq)
		. ' RRA:LAST:0.5:1:' . (183 * 86400 / $online_freq);
	system($cmd);
}
$cmd = RRDTOOL_BINARY . " update ${rrd_file} N:$online";
system($cmd);

$DB->Destroy();

?>
