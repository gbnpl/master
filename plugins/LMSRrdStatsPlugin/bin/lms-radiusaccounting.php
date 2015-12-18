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
	'f:' => 'accounting-file:',
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
lms-radiusaccounting.php
(C) 2001-2015 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-radiusaccounting.php
(C) 2001-2015 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors;
-f, --accounting-file=<file-name> radius accouting file to analyze (default: stdin);

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-radiusaccounting.php
(C) 2001-2015 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

if (!$quiet)
	echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;

if (!is_readable($CONFIG_FILE)) {
	print "Unable to read configuration file [$CONFIG_FILE]!" . PHP_EOL;
	exit(1);
}

$accounting_file = array_key_exists('accounting-file', $options) ? $options['accounting-file'] : 'php://stdin';

if (!is_readable($accounting_file)) {
	print "Cannot open accounting file: $accounting_file!" . PHP_EOL;
	exit(2);
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

define('EVENT_CONNECT', 1);
define('EVENT_UPDATE', 2);
define('EVENT_DISCONNECT', 4);
define('EVENT_STATS', EVENT_UPDATE | EVENT_DISCONNECT);

$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq',
	intval(ConfigHelper::getConfig('phpui.stat_freq', 12))
));
$online_update = ConfigHelper::checkValue(ConfigHelper::getConfig('rrdstats.online_update', false, true));

$connect_pattern = ConfigHelper::getConfig('rrdstats.connect_pattern', '^(?<date>[^|]+)\|C\|(?<sessiontag>[^|]+)'
	. '\|(?<ip>[^|]+)\|(?<mac>[^|]+)\|(?<nasip>[^|]+)\|(?<nasid>[^|]+)\|(?<username>[^|]+)$');
$update_pattern = ConfigHelper::getConfig('rrdstats.update_pattern', '^(?<date>[^|]+)\|U\|(?<sessiontag>[^|]+)'
	. '\|(?<ip>[^|]+)\|(?<mac>[^|]+)\|(?<nasip>[^|]+)\|(?<nasid>[^|]+)\|(?<gigadownload>[^|]+)\|(?<download>[^|]+)'
	. '\|(?<gigaupload>[^|]+)\|(?<upload>[^|]+)\|(?<username>[^|]+)$');
$disconnect_pattern = ConfigHelper::getConfig('rrdstats.disconnect_pattern', '^(?<date>[^|]+)\|D\|(?<sessiontag>[^|]+)'
	. '\|(?<ip>[^|]+)\|(?<mac>[^|]+)\|(?<nasip>[^|]+)\|(?<nasid>[^|]+)\|(?<gigadownload>[^|]+)\|(?<download>[^|]+)'
	. '\|(?<gigaupload>[^|]+)\|(?<upload>[^|]+)\|(?<username>[^|]+)$');

$full32bit = pow(2, 32);
$sessions = array();
$total_download = $total_upload = 0.0;

$fh = fopen($accounting_file, 'r');
//$DB->BeginTrans();
while (!feof($fh)) {
	$line = trim(fgets($fh));
	$connect = $update = $disconnect = false;
	$connect = preg_match("/${connect_pattern}/", $line, $m);
	if ($connect)
		$type = EVENT_CONNECT;
	else {
		$update = preg_match("/${update_pattern}/", $line, $m);
		if ($update)
			$type = EVENT_UPDATE;
		else {
			$disconnect = preg_match("/${disconnect_pattern}/", $line, $m);
			if ($disconnect)
				$type = EVENT_DISCONNECT;
			else
				$type = null;
		}
	}
	if ($type == null) {
		if (!$quiet)
			print "Invalid line: %line" . PHP_EOL;
		continue;
	}

	$datetokens = sscanf($m['date'], '%d-%d-%d-%d.%d.%d.%d');
	if (count($datetokens) != 7)
		continue;

	$dt = mktime($datetokens[3], $datetokens[4], $datetokens[5],
		$datetokens[1], $datetokens[2], $datetokens[0]);

	if ($type & EVENT_STATS) {
		$download = $m['gigadownload'] * $full32bit + $m['download'];
		$upload = $m['gigaupload'] * $full32bit + $m['upload'];
	} else
		$download = $upload = 0;

	if (isset($sessions[$m['sessiontag']]))
		$session = $sessions[$m['sessiontag']];
	else
		$session = $DB->GetRow('SELECT * FROM nodesessions WHERE tag = ?',
			array($m['sessiontag']));
	if ($session) {
		$prev_download = $session['download'];
		$prev_upload = $session['upload'];
		$session['stop'] = $dt;
		$session['mac'] = $m['mac'];
		$session['download'] = $download;
		$session['upload'] = $upload;
	} else {
		$prev_download = 0;
		$prev_upload = 0;
		$session = $DB->GetRow('SELECT ownerid AS customerid, n.id AS nodeid,
				ipaddr, mac FROM vmacs n
			WHERE n.ipaddr = ?', array(ip_long($m['ip'])));
		$session['start'] = $session['stop'] = $dt;
		$session['mac'] = $m['mac'];
		$session['download'] = $download;
		$session['upload'] = $upload;
		$session['tag'] = $m['sessiontag'];

		$DB->BeginTrans();
		$DB->Execute('INSERT INTO nodesessions (customerid, nodeid, ipaddr,
			mac, start, stop, download, upload, tag) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array_values($session));
		$session['id'] = $DB->GetLastInsertID('nodesessions');
		$DB->CommitTrans();

	}
	//$session['nasip'] = $m['nasip'];
	//$session['nasid'] = $m['nasid'];
	$sessions[$tag] = $session;

	$delta_download = $download - $prev_download;
	$delta_upload = $upload - $prev_upload;
	if ($delta_download < 0)
		$delta_download = 0;
	if ($delta_upload < 0)
		$delta_upload = 0;

	if ($delta_download > 0 || $delta_upload > 0) {
		$total_download += $delta_download;
		$total_upload += $delta_upload;

		$delta_download = sprintf("%.0f", $delta_download);
		$delta_upload = sprintf("%.0f", $delta_upload);

		$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . $session['nodeid'] . '.rrd';
		if (!file_exists($rrd_file)) {
			$cmd = RRDTOOL_BINARY . " create ${rrd_file} --step ${stat_freq}"
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
			system($cmd);
		}
		$cmd = RRDTOOL_BINARY . " update ${rrd_file} N:${delta_download}:${delta_upload}";
		system($cmd);
/*
		$DB->Execute('INSERT INTO stats (nodeid, dt, upload, download, nodesessionid)
			VALUES(?, ?, ?, ?, ?)',
			array($session['nodeid'], $dt, $delta_upload,
				$delta_download, $session['id']));
*/
	}
}
fclose($fh);

$total_download = sprintf("%.0f", $total_download);
$total_upload = sprintf("%.0f", $total_upload);
if ($total_download > 0 || $total_upload > 0) {
	$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . 'traffic.rrd';
	if (!file_exists($rrd_file)) {
		$cmd = RRDTOOL_BINARY . " create ${rrd_file} --step ${stat_freq}"
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
		system($cmd);
	}
	$cmd = RRDTOOL_BINARY . " update ${rrd_file} N:${total_download}:${total_upload}";
		system($cmd);
}

if (!empty($sessions))
	foreach ($sessions as $session) {
		$DB->Execute('UPDATE nodesessions SET stop = ?, mac = ?, download = ?, upload = ? WHERE id = ?',
			array($session['stop'], $session['mac'], $session['download'], $session['upload'], $session['id']));
		if ($online_update)
			$DB->Execute('UPDATE nodes SET lastonline = ? WHERE id = ?',
				array($session['stop'], $session['nodeid']));
	}

//$DB->RollbackTrans();
//$DB->CommitTrans();
$DB->Destroy();

exit(0);

?>
