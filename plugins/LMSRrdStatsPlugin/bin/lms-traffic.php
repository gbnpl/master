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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	'v' => 'version',
	'f:' => 'traffic-log-file:',
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
lms-traffic.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-traffic.php
(C) 2001-2016 LMS Developers

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
lms-traffic.php
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

$log_file = (array_key_exists('traffic-log-file', $options) ? $options['traffic-log-file'] : '/var/log/traffic.log');

if (!is_readable($log_file)) {
	print "Cannot open traffic log file: ${log_file}!" . PHP_EOL;
	exit(2);
}

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
	print "Fatal error: cannot connect to database!" . PHP_EOL;
	exit(3);
}

define('RRD_DIR', LMSRrdStatsPlugin::getRrdDirectory());
define('RRDTOOL_BINARY', ConfigHelper::getConfig('rrdstats.rrdtool_binary', '/usr/bin/rrdtool'));

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
//include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq',
	intval(ConfigHelper::getConfig('phpui.stat_freq', 12))
));
$online_update = ConfigHelper::checkValue(ConfigHelper::getConfig('rrdstats.online_update', false, true));

$nodes = $DB->GetAllByKey('SELECT id, INET_NTOA(ipaddr) AS ipaddr FROM vnodes', 'ipaddr');
if (empty($nodes))
	die("Can't get any nodes!" . PHP_EOL);

$data = array();

$fh = fopen($log_file, "r+");
while (!feof($fh)) {
	$line = trim(fgets($fh));
	if (empty($line))
		continue;
	list ($ip, $upload, $download) = preg_split('/([\t\s]+|;)/', $line);
	if (isset($nodes[$ip])) {
		if (!empty($upload) || !empty($download)) {
			if (!isset($data[$ip]))
				$data[$ip]['download'] = $data[$ip]['upload'] = 0;

			$data[$ip]['download'] += $download;
			$data[$ip]['upload'] += $upload;

			if(!$quiet)
				printf("IP: $ip\tSend: $upload\t Recv: $download" . PHP_EOL);
		} else
			printf("IP: $ip\tSkipped - null data" . PHP_EOL);
	} else
		if(!$quiet)
			printf("IP: $ip\tSkipped - not in database" . PHP_EOL);
}
fclose($fh);

$currtime = time();
if ($online_update) {
	$nodechunks = array_chunk($nodes, 300);
	foreach ($nodechunks as $nodechunk) {
		$nodeids = array();
		foreach ($nodechunk as $node)
			if (isset($data[$node['ipaddr']]))
				$nodeids[] = $node['id'];
		$DB->Execute('UPDATE nodes SET lastonline = ? WHERE id IN (' . implode(',', $nodeids) . ')',
			array($currtime));
	}
}

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

$total_download = $total_upload = 0.0;
foreach ($data as $ip => $record) {
	$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . $nodes[$ip]['id'] . '.rrd';
	if (!file_exists($rrd_file)) {
		$cmd = "create ${rrd_file} --step ${stat_freq}"
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
	}
	$cmd = "update ${rrd_file} N:${record['download']}:${record['upload']}";
	fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);

	$total_download += $record['download'];
	$total_upload += $record['upload'];
}

$total_download = sprintf("%.0f", $total_download);
$total_upload = sprintf("%.0f", $total_upload);
if ($total_download > 0 || $total_upload > 0) {
	$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . 'traffic.rrd';
	if (!file_exists($rrd_file)) {
		$cmd = "create ${rrd_file} --step ${stat_freq}"
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
	}
	$cmd = "update ${rrd_file} N:${total_download}:${total_upload}";
	fwrite($rrdtool_pipes[0], $cmd . PHP_EOL);
}

proc_close($rrdtool_process);

$DB->Destroy();

?>
