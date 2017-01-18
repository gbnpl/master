#!/usr/bin/env php
<?php

$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if (is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

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
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

define('RRD_DIR', LMSGponDasanPlugin::getRrdDirectory());
define('RRDTOOL_BINARY', ConfigHelper::getConfig('gpon-dasan.rrdtool_binary', '/usr/bin/rrdtool'));

if (!file_exists(RRDTOOL_BINARY))
	die("No rrdtool binary found on path " . RRDTOOL_BINARY . "!" . PHP_EOL);

$AUTH = null;
$GPON = new GPON_DASAN();

function update_signal_onu_rrd($onuid, $signal, $oltrx) {
	if ((strlen($onuid) == 0) || (strlen($signal) == 0))
		return;

	$fname = RRD_DIR . DIRECTORY_SEPARATOR . 'signal_onu_' . $onuid . '.rrd';
	if (!file_exists($fname)) {
		//create rrd
		$cmd  = RRDTOOL_BINARY . " create $fname --step 3600 ";
		$cmd .= "DS:Signal:GAUGE:7200:-50:10 ";
		$cmd .= "DS:oltrx:GAUGE:7200:-50:10 ";
		$cmd .= "RRA:AVERAGE:0.5:1:288 "; //12 dni co godzine
		$cmd .= "RRA:AVERAGE:0.7:6:268 "; //cwierc dnia ~ 2mce
		$cmd .= "RRA:AVERAGE:0.8:24:1095 "; //3year - 1day
		$cmd .= "RRA:MIN:0.5:1:288 "; 
		$cmd .= "RRA:MIN:0.7:6:268 "; 
		$cmd .= "RRA:MIN:0.8:24:1095 ";
		$cmd .= "RRA:MAX:0.5:1:288 "; 
		$cmd .= "RRA:MAX:0.7:6:268 "; 
		$cmd .= "RRA:MAX:0.8:24:1095 ";
		exec($cmd);
	}
	//update rrd file
	$cmd  = RRDTOOL_BINARY . " update $fname N:$signal:$oltrx";
	//update via rrdcached deamon, tylko ze jesli to dziala raz na godzine to nie ma to sensu ;)
	//$cmd  = "/usr/bin/rrdupdate $fname --daemon /var/run/rrdcached.sock N:$signal:$oltrx";
	exec($cmd);
}

$minute = intval(strftime("%M"));

$olts = $DB->GetAll("SELECT g.*, nd.id AS netdevid, nd.name FROM " . GPON_DASAN::SQL_TABLE_GPONOLT . " g
	JOIN netdevices nd ON nd.id = g.netdeviceid");
if (!empty($olts))
	foreach ($olts as $olt) {
		$GPON->snmp->clear_options();
		if (is_array($olt) && count($olt)) {
			$GPON->snmp->set_options($olt);
			$olt_name = $olt['name'];
		}

		$signals = $GPON->snmp->OLT_ONU_walk_signal();
		if (empty($signals))
			continue;

		foreach ($signals as $snmpid => $signal) {
			if (!preg_match('/sleGponOnuRxPower\.([0-9]+)\.([0-9]+)/', $snmpid, $matchids))
				continue;
			$onuid = $DB->GetOne("SELECT o.id FROM " . GPON_DASAN::SQL_TABLE_GPONONU . " o
				JOIN " . GPON_DASAN::SQL_TABLE_GPONONU2OLT . " p ON p.gpononuid=o.id
				WHERE netdevicesid = ? AND numport =? AND onuid = ?", array($olt['netdevid'], $matchids[1], $matchids[2]));
			if (!$onuid)
				continue;

			$signal = $GPON->snmp->clean_snmp_value($signal);
			$signal = str_replace('dBm', '', $signal);

			//olx rx signal
			$OLT_id = $matchids[1];
			$ONU_id = $matchids[2];
			$GPON->snmp->set('sleGponOnuControlRequest', 'i', 20); //updateOltRxPower(20)
			$GPON->snmp->set('sleGponOnuControlOltId','i', $OLT_id);
			$GPON->snmp->set('sleGponOnuControlId', 'i', $ONU_id);
			$GPON->snmp->set('sleGponOnuControlTimer', 'u', 0);
			$oltrx = $GPON->snmp->get('sleGponOnuOltRxPower.' . $OLT_id . '.' . $ONU_id);
			//drugi przebieg - jakis problem z odczytywaniem olt rx-power
			$GPON->snmp->set('sleGponOnuControlRequest', 'i', 20); //updateOltRxPower(20)
			$GPON->snmp->set('sleGponOnuControlOltId', 'i', $OLT_id);
			$GPON->snmp->set('sleGponOnuControlId', 'i', $ONU_id);
			$GPON->snmp->set('sleGponOnuControlTimer', 'u', 0);
			$oltrx = $GPON->snmp->get('sleGponOnuOltRxPower.' . $OLT_id . '.' . $ONU_id);

			$oltrx = str_replace('dBm', '', $oltrx);

			//zdarza sie ze ktos ma inne locale ???  , -> .
			$signal = str_replace(',','.',$signal);
			$oltrx = str_replace(',','.',$oltrx);

			update_signal_onu_rrd($onuid, $signal, $oltrx);
		}
	}

$DB->Destroy();

?>
