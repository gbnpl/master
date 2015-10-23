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
	'i'	=>	'full-import',
	'c'	=>	'customers',
	't'	=>	'terminals',
	'n'	=>	'numbers',
	'e'	=>	'end-users',
	'p'	=>	'price-lists',
	'w'	=>	'wlr',
	's'	=>	'subscriptions',
	'a'	=>	'all',
	'b'	=>	'billing',
	'D:'	=>	'billing-date:',
	'F:'	=>	'billing-from:',
	'T:'	=>	'billing-to:',
	'Y:'	=>	'billing-type:',
	'S:'	=>	'billing-success:',
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
lms_hiperus_c5_import.php
(C) 2012-2013 LMS iNET,
(C) 2015 Tomasz Chiliński <tomasz.chilinski@chilan.com>

-C, --config-file		alternatywny plik konfiguracyjny, -C /etc/lms/lms.ini
-h, --help			pomoc
-q, --quiet			cisza, bez informacji na ekranie
-i, --full-import		najpierw kasuje dane z bazy lms dotyczące VoIPa, 
				a następnie pobiera dane z zew. serwisu

PRZELACZNIKI DO POBRANIA PODSTAWOWYCH DANYCH:

-c, --customers			pobiera dane o kontach VoIP, jeżeli nie pobraliśmy 
				danych o kontach to używanie następnych
				przełączników mija się z celem.
-t, --terminals			informacje o terminalach
-n, --numbers			informacje o przydzielonych pulach PSTN, wielkości puli,
				wykorzystanych numerach
-e, --end-users			informacje o użytkownikach końcowych panelu 
				administracyjnego
-p, --price-lists		informacje o cennikach
-s, --subscriptions		informacje o abonamentach
-w, --wlr			informacje o WLR
-a, --all			pobiera wsztstkie powyższe informacje

BILLINGI - droga cierniowa :)
    zew. serwer dość długo zwraca informacje o bilingach, dlatego proszę uzbroić
    się w cierpliwość podczas pobierania.
    Przełącznik --full-import nie dziala dla bilingow, jezeli chcemy wyczyścić
    sobie bazę musimy to zrobić ręcznie.

-b, --billing			pobranie danych bilingowych
--billing-type=<type>		pobranie danych o określonym typie połączeń, DEF.:outgoing
				dozwolone wartości przełącznika:
				all, incoming, outgoing, disa, forwarded, internal, vpbx
--billing-success=<status>	pobranie danych o statusie połączenia, DEF.: yes
				all (wszystkie), yes (zrealizowane), no (nie zrealizowane)
--billing-from=<date>		data początkowa pobieranych billingow, YYYY/MM/DD
--billing-to=<date>		data końcowa pobieranych billingow, YYYY/MM/DD
--billing-date=<type>		lekkie ułatwienie ;-), użycie tego przełącznika spowoduje 
				zignorowanie --billing-from i --billing-to,
				dozwolone wartości przełącznika:
				currday: pobiera dane z dnia bieżącego
				lastday: pobiera dane z dnia poprzedniego
				currmonth: pobiera dane z bieżącego miesiąca
				lastmonth: pobiera dane z poprzedniego miesiąca
				currweek: pobiera dane z bieżącego tygodnia

EOF;
	die;
}

if (!$quiet) {
	print <<<EOF
lms_hiperus_c5_import.php
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

$HIPERUS = new LMSHiperus($DB);
$now = strftime("%Y-%m-%d");
$year = strftime("%Y");
$month = strftime("%m");

$full_import = array_key_exists('full-import', $options);
$all = array_key_exists('all', $options);
$customers = array_key_exists('customers', $options) || $all;
$terminals = array_key_exists('terminals', $options) || $all;
$numbers = array_key_exists('numbers', $options) || $all;
$endusers = array_key_exists('end-users', $options) || $all;
$pricelists = array_key_exists('price-lists', $options) || $all;
$wlr = array_key_exists('wlr', $options) || $all;
$subscriptions = array_key_exists('subscriptions', $options) || $all;
$billing = array_key_exists('billing', $options);

if ($customers) {
	if ($full_import) {
		if (!$quiet)
			print "Czyszczę listę klientów w bazie LMS\n";
		$DB->Execute('DELETE FROM hv_customers');
	}
	if (!$quiet)
		print "Pobieram listę klientów z bazy Hiperus\n";
	$HIPERUS->ImportCustomerList();
	dberr();
}

// pobranie listy zaimportowanych kont VoIP
$cus = $DB->GetAll('SELECT id, create_date FROM hv_customers ORDER BY id');
dberr();

// Pobranie listy terminali
if ($terminals && !empty($cus)) {
	if ($full_import)  {
		if (!$quiet)
			print "Czyszczę listę terminali w bazie LMS\n";
		$DB->Execute('DELETE FROM hv_terminal');
	}
	if (!$quiet)
		print "Pobieram listę terminali z bazy Hiperus\n";
	$HIPERUS->ImportTerminalList();
}

// pobranie informacji o numerach PSTN
if ($numbers && !empty($cus)) {
	if ($full_import) {
		if (!$quiet)
			print "Czyszczę informacje o numerach PSTN w bazie LMS\n";

		$DB->Execute('DELETE FROM hv_pstn');
		$DB->Execute('DELETE FROM hv_pstnrange');
		$DB->Execute('DELETE FROM hv_pstnusage');
	}
	if (!$quiet)
		print "Pobieram informacje o numerach PSTN z bazy Hiperus\n";
	$HIPERUS->ImportPSTNList();
	$HIPERUS->ImportPSTNRangeList();
	$HIPERUS->ImportPSTNUsageList();
}

// informacje o użytkownikach koncowych panelu klienta
if ($endusers && !empty($cus)) {
	if ($full_import) {
		if (!$quiet)
			print "Czyszczę listę użytkowników panelu końcowego klienta w bazie LMS\n";
		$DB->Execute('DELETE FROM hv_enduserlist');
	}
	if (!$quiet)
		print "Pobieram listę użytkowników panelu końcowego klienta z bazy Hiperus\n";
	$HIPERUS->ImportEndUserList();
}

// informacje o cennikach
if ($pricelists) {
	if ($full_import) {
		if (!$quiet)
			print "Czyszczę listę cenników dla klienta końcowego w bazie LMS\n";
		$DB->Execute('DELETE FROM hv_pricelist');
	}
	if (!$quiet)
		print "Pobieram listę cenników dla klienta końcowego z bazy Hiperus\n";
	$HIPERUS->ImportPriceList();
}

// info o abonamentach
if ($subscriptions) {
	if ($full_import) {
		if (!$quiet)
			print "Czyszczę listę abonamentów zdefiniowanych przez resllera w bazie LMS\n";
		$DB->Execute('DELETE FROM hv_subscriptionlist');
	}
	if (!$quiet)
		print "Pobieram listę abonamentów zdefiniowanych przez resellera z bazy Hiperus\n";
	$HIPERUS->ImportSubscriptionList();
}

//if ($billing || $option['billing-file'])
if ($billing) {
	if (!$quiet)
		print "Pobieram listę billingów\nProszę o cierpliwość - zew. serwis dość długo odpowiada przy billingach.\n\n";

	if (array_key_exists('billing-date', $options)) {
		$billing_date = strtolower($options['billing-date']);
		if (!in_array($billing_date, array('currday', 'lastday', 'currmonth', 'lastmonth', 'currweek')))
			unset($billing_date);
	}

	if (array_key_exists('billing-type', $options)) {
		$billing_type = strtolower($options['billing-type']);
		if (!in_array($billing_type, array('all', 'incoming', 'outgoing', 'disa', 'forwarded', 'internal', 'vpbx')))
			$billing_type = 'outgoing';
	} else
		$billing_type = 'outgoing';

	if (array_key_exists('billing-success', $options)) {
		$billing_success = strtolower($options['billing-success']);
		if (!in_array($billing_success, array('all', 'yes', 'no')))
			$billing_success = 'yes';
	} else
		$billing_success = 'yes';

	if (array_key_exists('billing-from', $options)) {
		$from = $options['billing-from'];
		if (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $from)) {
			list ($b_year, $b_month, $b_day) = explode('/', $from);
			if (checkdate($b_month, $b_day, $b_year))
				$billing_from = "$b_year-$b_month-$b_day";
		}
	}

	if (array_key_exists('billing-to', $options)) {
		$to = $options['billing-to'];
		if (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $to)) {
			list ($b_year, $b_month, $b_day) = explode('/', $to);
			if (checkdate($b_month, $b_day, $b_year))
				$billing_to = "$b_year-$b_month-$b_day";
		}
	}

	if (isset($billing_date)) {
		unset($billing_from);
		unset($billing_to);
	} else {
		if (isset($billing_from) && isset($billing_to)) {
			$from = $billing_from;
			$to = $billing_to;
		} else
			$billing_date = 'currday';
	}

	if (isset($billing_date))
		switch ($billing_date) {
			case 'currday':
				$from = $to = $now;
				break;
			case 'lastday':
				$from = $to = strftime("%Y-%m-%d", time() - 86400);
				break;
			case 'currmonth':
				$from = "$year-$month-01";
				$to = $now;
				break;
			case 'lastmonth':
				$from = date('Y-m-d', mktime(0, 0, 0, $month - 1, 1, $year));
				$to = date('Y-m-t', mktime(0, 0, 0, $month - 1, 1, $year));
				break;
			case 'currweek':
				$from = date('Y-m-d', time() - 7 * 86400);
				$to = $now;
				break;
		}

	if (!$quiet)
		print "Pobieram billing od dnia $from do $to\n";
	if ($billing_type == 'all')
		$billing_types = array('incoming', 'outgoing', 'disa', 'forwarded', 'internal', 'vpbx');
	else
		$billing_types = array($billing_type);
	foreach ($billing_types as $billing_type)
		$HIPERUS->ImportBilling($from, $to, $billing_success, $billing_type, NULL, $quiet);
}

if ($wlr)
	$HIPERUS->GetConfigHiperus();

?>
