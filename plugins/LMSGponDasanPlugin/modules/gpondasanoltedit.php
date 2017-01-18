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

$GPON = LMSGponDasanPlugin::getGponInstance();

if (!$LMS->NetDevExists($_GET['id']))
	$SESSION->redirect('?m=gpondasanoltlist');

/* Using AJAX plugins */
function OLT_ONU_walk_Xj($gponoltid, $port = null, $callback = null) {
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$options_snmp = $GPON->GetGponOlt($gponoltid);
	$GPON->snmp->set_options($options_snmp);
	$OLT_ONU = $GPON->snmp->OLT_ONU_walk_get_param($port);
	if (is_array($OLT_ONU) && !empty($OLT_ONU))
		foreach ($OLT_ONU as $k => $v)
			if (is_array($v) && !empty($v))
				foreach ($v as $k1=>$v1) {
					if ($k == 'RxPower')
						$v1 = '<font color="' . $GPON->snmp->style_gpon_rx_power($v1, 0) . '">'.$v1.'</font>';
					$objResponse->assign($k . "_ONU_" . $k1,"innerHTML", $v1);
				}
	$error_snmp = $GPON->snmp->get_correct_connect_snmp();

	if ($callback === null)
		$objResponse->assign("OLT_ONU_date", "innerHTML", $error_snmp.'Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b>');
	else
		$objResponse->call($callback);

	return $objResponse;
}

function OLT_get_param_Xj($gponoltid, $id) {
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$options_snmp=$GPON->GetGponOlt($gponoltid);
	$GPON->snmp->set_options($options_snmp);
	$error_snmp=$GPON->snmp->get_correct_connect_snmp();
	$table_param=$GPON->snmp->OLT_get_param_table();
	$objResponse->script("document.getElementById('pokaz_dane_OLT_".$id."').value='Odśwież dane SNMP';"); 
	$objResponse->assign("OLT_dane_".$id,"innerHTML",$error_snmp.$table_param);
	return $objResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('OLT_ONU_walk_Xj', 'OLT_get_param_Xj'));
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$action = !empty($_GET['action']) ? $_GET['action'] : '';
$edit = '';
$subtitle = '';

switch ($action) {
	case 'writememory':
		$GPON->snmp->clear_options();
		$netdevdata = $LMS->GetNetDev($_GET['id']);
		$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
		if ($netdevdata['gponoltid']) {
			$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
			$GPON->snmp->set_options($options_snmp);
			$GPON->snmp->OLT_write_config();
			$GPON->Log(4, GPON_DASAN::SQL_TABLE_GPONOLT, $netdevdata['gponoltid'], 'SNMP: write memory');
		}
		$SESSION->redirect('?m=gpondasanoltinfo&id=' . $_GET['id']);
		break;
	case 'disconnect':
		$GPON->snmp->clear_options();
		$netdevdata = $LMS->GetNetDev($_GET['id']);
		$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
		if ($netdevdata['gponoltid']) {
			$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
			$GPON->snmp->set_options($options_snmp);
			$gpon_onu=$GPON->GetGponOnu($_GET['devid']);
			$snmp_result=$GPON->snmp->ONU_delete($_GET['numport'],$gpon_onu['onuid']);
			$snmp_error=$GPON->snmp->parse_result_error($snmp_result);
			if (strlen($snmp_error)) {
				$dev['linkolt'] = 'Nie można usunąć przypisania tego ONU - Błąd SNMP. '.$snmp_error;
				$SMARTY->assign('connect', $dev);
			} else {
				$GPON->GponOnuUnLink($_GET['id'], $_GET['numport'], $_GET['devid']);
				$SESSION->redirect('?m=gpondasanoltinfo&id='.$_GET['id']);
			}
		}
		break;
	case 'connect':
		$portexist = intval($GPON->GetGponOltPortsExists($_GET['id'], $_GET['numport']));
		if (!$portexist)
			$error['numport'] = 'Taki port nie istnieje.';
		else {
			$maxonu = $GPON->GetGponOltPortsMaxOnu($_GET['id'], $_GET['numport']);
			$onucountonport = $GPON->GetGponOnuCountOnPort($_GET['id'],$_GET['numport']);
			if ($onucountonport >= $maxonu)
				$error['numport'] = 'Ten port osiągnął swoje maksimum. Nie można już przypisać ONU.';
			$gponlink = $GPON->IsGponOnuLink2olt($_GET['gpononu']);
			if ($gponlink) {
				$error['linkolt'] = 'Nie można już przypisać wybranego wcześniej ONU - zostało przypisane przed chwilą.';
				$dev['linkolt'] = $error['linkolt'];
			}
		}
		$dev['id'] = !empty($_GET['gpononu']) ? intval($_GET['gpononu']) : '0';
		$dev['numport'] = !empty($_GET['numport']) ? intval($_GET['numport']) : '0';
		if (!$error) {
			$GPON->snmp->clear_options();
			$netdevdata = $LMS->GetNetDev($_GET['id']);
			$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
			if ($netdevdata['gponoltid']) {
				$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
				$error_option = $GPON->snmp->set_options($options_snmp);
				if (strlen($error_option))
					$dev['linkolt'] = 'Nie można przypisać tego ONU - Błąd SNMP. '.$error_option;
				else {
					$gpon_onu = $GPON->GetGponOnu($_GET['gpononu']);
					$snmp_result = $GPON->snmp->ONU_add($_GET['numport'],$gpon_onu['name'],$gpon_onu['password'],$gpon_onu['onu_desc']);
					$snmp_error = $GPON->snmp->parse_result_error($snmp_result);
					if (strlen($snmp_error))
						$dev['linkolt'] = 'Nie można przypisać tego ONU - Błąd SNMP. '.$snmp_error;
					else
						if ($snmp_result['ONU_id']) {
							$GPON->GponOnuUpdateOnuId($_GET['gpononu'], $snmp_result['ONU_id']);
							$GPON->GponOnuLink($_GET['id'],$dev['numport'], $_GET['gpononu']);
							$SESSION->redirect('?m=gpondasanoltinfo&id=' . $_GET['id']);
						} else
							$dev['linkolt'] = 'Nie można przypisać ONU ID.';
				}
			}
		}

		$SMARTY->assign('connect', $dev);
		break;
	case 'switchlinktype':
		$LMS->SetNetDevLinkType($_GET['devid'], $_GET['id'], $_GET['linktype']);
		$SESSION->redirect('?m=gpondasanoltinfo&id=' . $_GET['id']);
	default:
		$edit = 'data';
		break;
}

if (isset($_GET['prof']) && strlen($_GET['prof'])) {
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
	$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
	$GPON->snmp->set_options($options_snmp);
	$temp_post = $GPON->snmp->OLT_GetProfilesData($_GET['prof']);//var_dump($temp_post);
	$temp_post['profile_edit']=1;
	$SMARTY->assign('temp_post', $temp_post);
}

if (isset($_POST['snmpsend']) && $_POST['snmpsend'] == 1) {
	$GPON->snmp->clear_options();
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
	if ($netdevdata['gponoltid']) {
		$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
		$GPON->snmp->set_options($options_snmp);

		$GPON->snmp->OLT_set_defaultServiceProfile($_POST['serviceProfile']);
		$GPON->snmp->OLT_set_radiususernametype($_POST['olt_radiususernametype']);

		if (check_ip($_POST['olt_radiusAddress']) && strlen(trim($_POST['olt_radiusKey'])))
			$GPON->snmp->OLT_add_radius($_POST['olt_radiusAddress'], $_POST['olt_radiusKey'], intval($_POST['olt_radiusPort']));

		if (strlen(trim($_POST['new_autotime_ModelName'])) && $_POST['new_autotime_Start']>-1 && $_POST['new_autotime_Stop']>-1)
			$GPON->snmp->OLT_set_autoupgrade_time($_POST['new_autotime_ModelName'], $_POST['new_autotime_Start'], $_POST['new_autotime_Stop'], $_POST['new_autotime_Reboot']);

		if (check_ip($_POST['new_autoupgrade_address']) && $_POST['new_autoupgrade_ModelName'] && $_POST['new_autoupgrade_FW'])
			$GPON->snmp->OLT_set_autoupgrade_model($_POST['new_autoupgrade_ModelName'], $_POST['new_autoupgrade_FW'],
				$_POST['new_autoupgrade_address'], $_POST['new_autoupgrade_method'], $_POST['new_autoupgrade_user'],
				$_POST['new_autoupgrade_passwd'], $_POST['new_autoupgrade_version'], $_POST['new_autoupgrade_exclude']);

		foreach ($_POST as $k => $v) {
			if (preg_match('/radiusid\_/', $k)) {
				preg_match('/radiusid\_(.+)/', $k, $match);
				$num = intval($match[1]);

				$GPON->snmp->OLT_del_radius($num);
			}

			if (preg_match('/aging\_/',$k)) {
				preg_match('/aging\_(.+)/', $k, $match);
				$port = intval($match[1]);

				$GPON->snmp->OLT_set_AgingTime($port, intval($v));
			}

			if (preg_match('/authmode\_/', $k)) {
				preg_match('/authmode\_(.+)/', $k, $match);
				$port = intval($match[1]);

				$GPON->snmp->OLT_set_AuthMode($port, intval($v));
			}

			if (preg_match('/modelProfile\_/', $k)) {
				preg_match('/modelProfile_(.+)/', $k, $match);
				$model = $match[1];

				$GPON->snmp->OLT_set_ModelServiceProfile($model, $v);
			}

			if (preg_match('/^autoupgrade\_/', $k)) {
				preg_match('/^autoupgrade\_(.+)/', $k, $match);
				$port = intval($match[1]);

				$GPON->snmp->OLT_set_FWAutoUpgrade($port, intval($v));
			}

			if (preg_match('/^autoupgrademodel\_/', $k)) {
				preg_match('/^autoupgrademodel\_(.+)/', $k, $match);
				$model = $match[1];

				$GPON->snmp->OLT_del_autoupgrade_model($model);
			}

			if (preg_match('/^autotime\_/', $k)) {
				preg_match('/^autotime\_(.+)/', $k, $match);
				$model = $match[1];

				$GPON->snmp->OLT_del_autoupgrade_time($model);
			}
		}
		$dump = var_export($_POST, true);
		$GPON->Log(4, GPON_DASAN::SQL_TABLE_GPONOLT, $netdevdata['gponoltid'], 'SNMP set', $dump);
	}
}

if (isset($_POST['gponprofileadd']) && intval($_POST['gponprofileadd'])) {
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);

	$temp_post=$_POST;
	if ($temp_post['profile_name'] == '')
		$error['profile_name'] = 'Nazwa profilu jest wymagana';
	elseif (strlen($temp_post['name']) > 63)
		$error['profile_name'] =  trans('Specified name is too long (max.$a characters)!','63');
	for ($s = 1; $s < 5; $s++) {
		if ($temp_post['downstream_' . $s] % 64)
			$error['downstream_' . $s] =  'Downstream musi się dzielić przez 64';

		$vlanid = intval($temp_post['vlan_id_' . $s]);
		if ($vlanid && ($vlanid < 1 || $vlanid > 4095))
			$error['vlan_id_' . $s] =  'VLAN ID musi być z zakresu od 1 do 4095';

		if ($vlanid) {
			$cos = intval($temp_post['cos_' . $s]);
			if ($cos && ($cos < 0 || $cos > 7))
				$error['cos_' . $s] =  'COS musi być z zakresu od 0 do 7';
		}
	}

	if (!$error) {
		$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
		$GPON->snmp->set_options($options_snmp);
		if (intval($temp_post['profile_edit']) == 0)
			$GPON->snmp->OLT_AddProfile($temp_post['profile_name'], $temp_post['trafficprofiles']);
		$GPON->AddGponOltProfile($temp_post['profile_name'], $netdevdata['gponoltid']);
		for ($eth = 1; $eth < 5; $eth++)
			if (!empty($temp_post['vlan_id_' . $eth]))
				$GPON->snmp->OLT_ModifyProfile($temp_post['profile_name'],$eth,
					$temp_post['downstream_' . $eth], $temp_post['vlan_id_' . $eth],
					$temp_post['cos_' . $eth], $temp_post['status_' . $eth]);
		$temp_post = array();
	}
	$SMARTY->assign('temp_post',$temp_post);
} elseif (isset($_POST['netdev'])) {
	$netdevdata = $_POST['netdev'];
	$netdevdata['oldid'] = $_GET['id'];
	$netdevdata['id'] = $netdevdata['netdevid'];

	if ($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif (strlen($netdevdata['name']) > 32)
		$error['name'] =  trans('Specified name is too long (max.$a characters)!','32');

	$netdevdata['ports'] = intval($netdevdata['ports']);

	//---PORTS
	if (!intval($netdevdata['ports']))
		$error['ports'] = 'Podaj liczbę portów';
	//----PORTS

	//-GPON-OLT
	//walidacja parametrów SNMP
	if(intval($netdevdata['snmp_version'])>0 && strlen(trim($netdevdata['snmp_host']))==0)
	{
		$error['snmp_host'] = 'Podaj adres IP hosta';
	}
	if(intval($netdevdata['snmp_version'])>2)
	{
		if(strlen(trim($netdevdata['snmp_username']))==0)
		{
			$error['snmp_username'] = 'Podaj Username(login)';
		}
		if(strlen(trim($netdevdata['snmp_password']))==0)
		{
			$error['snmp_password'] = 'Podaj Password(hasło)';
		}
	}
	elseif(intval($netdevdata['snmp_version'])>0)
	{
		if(strlen(trim($netdevdata['snmp_community']))==0)
		{
			$error['snmp_community'] = 'Podaj Community';
		}
	}
	//-GPON-OLT

	if (!$error) {
		if ($_POST['dev2nagios'] && method_exists('LMS', 'SaveDev2Nagios'))
			$LMS->SaveDev2Nagios($_POST['dev2nagios'],$_GET['id']);

		//-GPON-OLT
		//Update OLT
		$GPON->GponOltUpdate($netdevdata);
		$gponoltportsdata = $_POST['gponoltports'];
		if ($netdevdata['gponoltid'] && is_array($gponoltportsdata) && !empty($gponoltportsdata)) {
			foreach ($gponoltportsdata as $k => $v) {
				$gponoltports[$k]['gponoltid'] = $netdevdata['gponoltid'];
				$gponoltports[$k]['numport'] = $k;
				$gponoltports[$k]['maxonu'] = $v;
			}
			$GPON->GponOltPortsUpdate($gponoltports);
		}
		//-GPON-OLT
		$SESSION->redirect('?m=gpondasanoltinfo&id=' . ($netdevdata['oldid'] != $netdevdata['id'] ? $netdevdata['id'] : $_GET['id']));
	}
} else {
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata['id']);
}

$netdevdata['id'] = $_GET['id'];

$netdevips = $LMS->GetNetDevIPs($_GET['id']);
$netdevconnected = $GPON->GetGponOnuConnectedNames($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOnu();

//-GPON-OLT
//Dane OLT
$gponoltdata = $GPON->GetGponOlt($netdevdata['gponoltid']);
$netdevdata = array_merge($netdevdata, $gponoltdata);
$gponoltportsdata = $GPON->GetGponOltPorts($netdevdata['gponoltid']);
//-GPON-OLT

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);




$layout['pagetitle'] = 'GPON - OLT - '.trans('Device Edit: $a ($b)', $netdevdata['name'], $netdevdata['producer']);

if($subtitle) $layout['pagetitle'] .= ' - '.$subtitle;

if($DB->GetOne("SELECT count(*) FROM information_schema.tables WHERE table_name = 'netdevnodes'") > 0)
{
    $q="SELECT * from netdevnodes order by symbol";
    $nd[]=array("id"=>0,"nazwa"=>"---wybierz---", "symbol"=>"---");
    $nd=array_merge($nd,$DB->GetAll($q));
    $netdevdata['lok']=$nd;
}

$SMARTY->assign('error',$error);
$SMARTY->assign('netdevinfo',$netdevdata);
//-GPON-OLT
//Dane OLTPORTS
$SMARTY->assign('gponoltportsinfo',$gponoltportsdata);
//-GPON-OLT
if(is_array($netdevconnected) && count($netdevconnected)>0)
{
	foreach($netdevconnected as $k=>$v)
	{
		$netdevconnected[$k]['gpononu2customers']=$GPON->GetGponOnu2Customers($v['id']);
	}
}
$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
$GPON->snmp->set_options($options_snmp);
$error_snmp=$GPON->snmp->get_correct_connect_snmp();
if (empty($error_snmp)) {
	$trafficprofiles=$GPON->snmp->OLT_GetTrafficProfiles();
	$gponoltprofiles=$GPON->snmp->OLT_GetProfiles();
	$snmpoltdata=$GPON->snmp->OLT_get_param_edit($netdevdata['gponoltid']);
	$SMARTY->assign('trafficprofiles',$trafficprofiles);
	$SMARTY->assign('gponoltprofiles',$gponoltprofiles);
	$SMARTY->assign('snmpoltdata',$snmpoltdata);
}
$SMARTY->assign('error_snmp', $error_snmp);

if(method_exists('LMS', 'GetDev2Nagios')) //nie wszyscy maja naszego nagiosa
{
    $dev2nagios=$LMS->GetDev2Nagios($_GET['id']);
    $SMARTY->assign("dev2nagios",$dev2nagios);
    $SMARTY->assign("nagiosON", 1);
}

$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('nastype', $LMS->GetNAStypes());
$SMARTY->assign('notgponoltdevices', $GPON->GetNotGponOltDevices($netdevdata['gponoltid']));

switch ($edit) {
	case 'data':
		$SMARTY->display('gpondasanoltedit.html');
		break;
	default:
		$SMARTY->display('gpondasanoltinfo.html');
		break;
}

?>
