#!/usr/bin/php
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
	's' => 'section:',
	'f:' => 'from:',
	't:' => 'to:',
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
lms-sql2rrd.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-sql2rrd.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors
-s, --section=<section-name>    section name from lms configuration where settings
                                are stored
-f, --from=YYYY/MM/DD           starting date of migration interval
-t, --to=YYYY/MM/DD             ending date of migration interval

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-sql2rrd.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE)) {
	print "Unable to read configuration file [" . $CONFIG_FILE."]!" . PHP_EOL;
	exit(1);
}

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
	print "Fatal error: cannot connect to database!" . PHP_EOL;
	exit(3);
}

define('RRD_DIR', ConfigHelper::getConfig('rrdstats.directory',
	dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'rrd'));
define('RRDTOOL_BINARY', ConfigHelper::getConfig('rrdstats.rrdtool_binary', '/usr/bin/rrdtool'));

// Include required files (including sequence is important)

//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
//include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');

$phpui_stat_freq = intval(ConfigHelper::getConfig('phpui.stat_freq', 12));
$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq', $phpui_stat_freq));
$rrd2sql = round($phpui_stat_freq / $stat_freq);

foreach (array('from', 'to') as $datetype)
	if (array_key_exists($datetype, $options)) {
		$date = $options[$datetype];
		if (check_date($date)) {
			list ($year, $month, $day) = explode('/', $date);
			if (checkdate($month, $day, $year)) {
				$variable = array($datetype => mktime(0, 0, 0, $month, $day, $year));
				extract($variable);
			}
		}
	}

if (!isset($from))
	$from = 0;
if (!isset($to))
	$to = time();
else
	$to--;

if ($from > $to)
	die("Starting date should be less than or equal to ending date!" . PHP_EOL);

$mintime = mktime(0, 0, 0, 1, 1, 2013);

$nodes = $DB->GetCol('SELECT DISTINCT nodeid FROM stats
	WHERE dt >= ? AND dt <= ?', array($from, $to));
if (empty($nodes))
	die("No node stats found in selected time interval!" . PHP_EOL);

$rrd_files = array();

$rrdtool_process = proc_open(RRDTOOL_BINARY . ' -',
	array(
		0 => array('pipe', 'r'),
		1 => array('file', '/dev/null', 'w'),
		2 => array('file', '/dev/null', 'w'),
	),
	$rrdtool_pipes
);
if (!is_resource($rrdtool_process))
	die("Couldn't open " . RRDTOOL_BINARY . "!" . PHP_EOL);

$total = count($nodes);
$nodeidx = 0;
foreach ($nodes as $nodeid) {
	printf("Progress: %.2f%%   ", ($nodeidx * 100) / $total);
	$nodeidx++;

	$records = $DB->GetAll('SELECT * FROM stats
		WHERE nodeid = ? AND dt >= ? AND dt <= ?
		ORDER BY dt', array($nodeid, $from, $to));
	if (empty($records))
		continue;

	$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . "$nodeid.rrd";

	if (!array_key_exists($rrd_file, $rrd_files) && !file_exists($rrd_file)) {
		$cmd = "create ${rrd_file} --start ${mintime} --step ${stat_freq}"
			. ' DS:down:GAUGE:' . ($stat_freq * 2) . ':0:U'
			. ' DS:up:GAUGE:' . ($stat_freq * 2) . ':0:U'
			. ' RRA:AVERAGE:0.5:1:' . (7 * 86400 / $stat_freq) // przez 7 dni bez agregacji
			. ' RRA:AVERAGE:0.5:3:' . ((21 * 86400) / ($stat_freq * 3)) // przez 21 dni z agregacją 3 próbek
			. ' RRA:AVERAGE:0.5:6:' . ((31 * 86400) / ($stat_freq * 6)) // przez 31 dni z agregacją 6 próbek
			. ' RRA:AVERAGE:0.5:12:' .  ((61 * 86400) / ($stat_freq * 12)) // przez 2 miesiące z agregacją 12 próbek
			. ' RRA:AVERAGE:0.5:72:' . ((275 * 86400) / ($stat_freq * 72)) // przez 9 miesięcy z agregacją 72 próbek
			. ' RRA:MAX:0.5:1:' . ((7 * 86400) / $stat_freq)
			. ' RRA:MAX:0.5:3:' . ((21 * 86400) / ($stat_freq * 3))
			. ' RRA:MAX:0.5:6:' . ((31 * 86400) / ($stat_freq * 6))
			. ' RRA:MAX:0.5:12:' .  ((61 * 86400) / ($stat_freq * 12))
			. ' RRA:MAX:0.5:72:' . ((275 * 86400) / ($stat_freq * 72));
		fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);
		$rrd_files[$rrd_file] = true;
	}

	foreach ($records as $record) {
		$cmd = "update ${rrd_file}";
		$download = intval($record['download'] / $rrd2sql);
		$upload = intval($record['upload'] / $rrd2sql);
		for ($i = 0, $dt = $record['dt'] - $phpui_stat_freq; $i < $rrd2sql; $i++, $dt += $stat_freq)
			$cmd .= " $dt:$download:$upload";
		fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);
	}

	printf("\r");
}

echo PHP_EOL;

proc_close($rrdtool_process);

$DB->Destroy();

?>
