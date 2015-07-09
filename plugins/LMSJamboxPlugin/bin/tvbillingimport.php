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
 
 *  Modyfikacja: Aplikacja IPTV versja 1.2
 *  2011 ITMSOFT
 *  1.2.1 23/08/2011 19:00:00
 
 *  Modyfikacja: Aplikacja IPTV versja 1.2
 *  2014 SGT
 *  1.2.1 23/08/2011 19:00:00  
 
 */

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE

$CONFIG_FILE = '/home/lms/www/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('START_TIME', microtime(true));
define('LMS-UI', true);
ini_set('error_reporting', E_ALL&~E_NOTICE);

// find alternative config files:
if(is_readable('lms.ini'))
	$CONFIG_FILE = 'lms.ini';
elseif(is_readable('/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini'))
	$CONFIG_FILE = '/etc/lms/lms-'.$_SERVER['HTTP_HOST'].'.ini';
elseif(!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['doc_dir'] = (!isset($CONFIG['directories']['doc_dir']) ? $CONFIG['directories']['sys_dir'].'/documents' : $CONFIG['directories']['doc_dir']);
$CONFIG['directories']['modules_dir'] = (!isset($CONFIG['directories']['modules_dir']) ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);
$CONFIG['directories']['backup_dir'] = (!isset($CONFIG['directories']['backup_dir']) ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['config_templates_dir'] = (!isset($CONFIG['directories']['config_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/config_templates' : $CONFIG['directories']['config_templates_dir']);
$CONFIG['directories']['smarty_compile_dir'] = (!isset($CONFIG['directories']['smarty_compile_dir']) ? $CONFIG['directories']['sys_dir'].'/templates_c' : $CONFIG['directories']['smarty_compile_dir']);
$CONFIG['directories']['smarty_templates_dir'] = (!isset($CONFIG['directories']['smarty_templates_dir']) ? $CONFIG['directories']['sys_dir'].'/templates' : $CONFIG['directories']['smarty_templates_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('DOC_DIR', $CONFIG['directories']['doc_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);
define('SMARTY_COMPILE_DIR', $CONFIG['directories']['smarty_compile_dir']);
define('SMARTY_TEMPLATES_DIR', $CONFIG['directories']['smarty_templates_dir']);

// Do some checks and load config defaults
require_once(LIB_DIR.'/checkdirs.php');
require_once(LIB_DIR.'/config.php');

// Init database

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

print_r($CONFIG);

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME, true);

if(!$DB)
{
	// can't working without database
	die();
}

// Enable/disable data encoding conversion
// Call any of upgrade process before anything else

//require_once(LIB_DIR.'/dbencoding.php');
require_once(LIB_DIR.'/upgradedb.php');

// Initialize templates engine (must be before locale settings)

require_once(LIB_DIR.'/Smarty/Smarty.class.php');
$SMARTY = new Smarty;

// test for proper version of Smarty
if (defined('Smarty::SMARTY_VERSION'))
	$ver_chunks = preg_split('/[- ]/', Smarty::SMARTY_VERSION);
else
	$ver_chunks = NULL;
if (count($ver_chunks) != 2 || version_compare('3.0', $ver_chunks[1]) > 0)
	die('<B>Wrong version of Smarty engine! We support only Smarty-3.x greater than 3.0.</B>');

define('SMARTY_VERSION', $ver_chunks[1]);

// uncomment this line if you're not gonna change template files no more
//$SMARTY->compile_check = false;

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];

// Redirect to SSL

// Include required files (including sequence is important)

require_once(LIB_DIR.'/language.php');
require_once(LIB_DIR.'/unstrip.php');
require_once(LIB_DIR.'/definitions.php');
require_once(LIB_DIR.'/common.php');
require_once(LIB_DIR.'/checkip.php');
require_once(LIB_DIR.'/LMS.class.php');
require_once(LIB_DIR.'/LMS.tv.class.php');
require_once(LIB_DIR.'/Auth.class.php');
require_once(LIB_DIR.'/accesstable.php');
require_once(LIB_DIR.'/Session.class.php');
//require_once(LIB_DIR . '/SYSLOG.class.php');



if (check_conf('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes

$SESSION = new Session($DB, $CONFIG['phpui']['timeout']);
$AUTH = new Auth($DB, $SESSION, $SYSLOG);
if ($SYSLOG)
	$SYSLOG->SetAuth($AUTH);
$LMS = new LMS($DB, $AUTH, $CONFIG, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$LMSTV = new LMSTV($DB, $AUTH, $CONFIG, $SYSLOG);
//$LMSTV->ui_lang = $_ui_language;
//$LMSTV->lang = $_language;

// Initialize Swekey class

if (chkconfig($CONFIG['phpui']['use_swekey'])) {
	require_once(LIB_DIR . '/swekey/lms_integration.php');
	$LMS_SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
	$SMARTY->assign('lms_swekey', $LMS_SWEKEY->GetIntegrationScript($AUTH->id));
}

// Set some template and layout variables

$SMARTY->setTemplateDir(null);
$custom_templates_dir = get_conf('phpui.custom_templates_dir');
if (!empty($custom_templates_dir) && file_exists(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir)
	&& !is_file(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir))
	$SMARTY->AddTemplateDir(SMARTY_TEMPLATES_DIR . '/' . $custom_templates_dir);
$SMARTY->AddTemplateDir(
	array(
		SMARTY_TEMPLATES_DIR . '/default',
		SMARTY_TEMPLATES_DIR,
	)
);
$SMARTY->setCompileDir(SMARTY_COMPILE_DIR);
$SMARTY->debugging = check_conf('phpui.smarty_debug');

$layout['logname'] = $AUTH->logname;
$layout['logid'] = $AUTH->id;
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = SMARTY_VERSION;
$layout['hostname'] = hostname();
$layout['lmsv'] = '1.11-git';
//$layout['lmsvr'] = $LMS->_revision.'/'.$AUTH->_revision;
$layout['lmsvr'] = '';
$layout['dberrors'] =& $DB->errors;
$layout['dbdebug'] = $_DBDEBUG;
$layout['popup'] = isset($_GET['popup']) ? true : false;

$SMARTY->assignByRef('layout', $layout);
$SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
$SMARTY->assignByRef('_ui_language', $LMS->ui_lang);
$SMARTY->assignByRef('_language', $LMS->lang);

$error = NULL; // initialize error variable needed for (almost) all modules

// Load menu
if(!$layout['popup']) {
	require_once(LIB_DIR.'/menu.php');
	$SMARTY->assign('newmenu', $menu);
}

header('X-Powered-By: LMS/'.$layout['lmsv']);

$end = cal_days_in_month(CAL_GREGORIAN, Date("m"), Date("Y"));

$start_date = Date("Y-m-02", strtotime("last month")); 
$end_date = Date("Y-m-".$end); 
//$end_date = Date("Y-m-d"); 
echo $start_date;
echo $end_date;

$res = $LMSTV->GetBillingEvents($start_date, $end_date);

print_r($res);

if (count($res)) {

	foreach ($res as $key => $r){
		try{
		
				/*$sql = "INSERT INTO tv_billingevent (customerid, account_id, be_selling_date, be_desc, be_vat, be_gross, be_b2b_netto, group_id, cust_number, package_id, hash, beid)
				values (".(empty($r['cust_external_id']) ? 0 : $r['cust_external_id']).",
				".$r['account_id'].",
				'".$r['be_selling_date']."',
				'".$r['be_desc']."',
				".$r['be_vat'].",
				".$r['be_gross'].",
				".$r['be_b2b_netto'].",
				".(empty($r['group_id']) ? 0 : $r['group_id']).",
				'".$r['cust_number']."',
				".$r['package_id'].",
				'".md5($r['id'])."',
				".$r['id']."); ";
		
				print_r($r);
				
				 $DB->Execute($sql); */
		
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
				$r['id'], ));
				
		} catch (Exception $e){
			print_r($e);
		}
	}
}

$SESSION->close();
$DB->Destroy();

?>
