#!/usr/bin/php
<?php

/*
 *
 *  (C) Copyright 2012 LMS iNET Developers
 *  (C) Copyright 2015 LMS Developers
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

empty($_SERVER['SHELL']) && die('<br><Br>Sorry Winnetou, tylko powloka shell ;-)');

$parameters = array(
	'C:'	=>	'config-file:',
	'q'	=>	'quiet',
	'h'	=>	'help',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = array();
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

$quiet = array_key_exists('quiet', $options);

if (array_key_exists('help', $options)) {
	print <<<EOF
lms_hiperus_c5_test.php
(C) 2012-2013 LMS iNET,
(C) 2015 Tomasz Chiliński <tomasz.chilinski@chilan.com>

-C, --config-file		alternatywny plik konfiguracyjny, -C /etc/lms/lms.ini
-h, --help			pomoc
-q, --quiet			cisza, bez informacji na ekranie

EOF;
	die;
}

if (!$quiet) {
	print <<<EOF
lms_hiperus_c5_test.php
(C) 2012-2013 LMS iNET,
(C) 2015 Tomasz Chiliński <tomasz.chilinski@chilan.com>

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config.\n\n";

if (!is_readable($CONFIG_FILE))
	die("Nie można odczytać pliku konfiguracyjnego [".$CONFIG_FILE."]!\n");

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

function dberr() {
	global $DB;
	if ($DB->GetErrors()) {
		fprintf(STDERR, "\n\nDatabase error:");
		foreach ($DB->GetErrors() as $item)
			fprintf(STDERR,"\nQuery: %s\n\nError: %s\n\n", $item['query'], $item['error']);
		die;
	}
}

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

define('H_SESSION_FILE', PLUGINS_DIR . DIRECTORY_SEPARATOR . 'LMSHiperusPlugin' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'session');
define('H_LOCK_FILE', PLUGINS_DIR . DIRECTORY_SEPARATOR . 'LMSHiperusPlugin' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'lock');
define('DEBUG_API', 1);

for ($i = 0; $i < 50; $i++) {
	echo "HiperusActions call number: $i" . PHP_EOL;
	$subscriptions = HiperusActions::GetSubscriptionlist();
}

?>
