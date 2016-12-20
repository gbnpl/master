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

$GPON = LMSGponDasanPlugin::getGponInstance();

if (!$GPON->GponOnuExists($_GET['id']))
	$SESSION->redirect('?m=gpondasanonulist');

$onu_customerlimit = ConfigHelper::getConfig('gpon-dasan.onu_customerlimit', 1);
$action = !empty($_GET['action']) ? $_GET['action'] : '';
$edit = '';
$subtitle = '';

switch ($action) {


case 'disconnectnode':
	$LMS->NetDevLinkNode($_GET['nodeid'],0);
	$SESSION->redirect('m=gpondasanonuinfo&id='.$_GET['id']);

case 'nas':
	$DB->Execute('UPDATE nodes SET nas=? WHERE id=?', array($_GET['nas'], $_GET['ip']));
	$SESSION->redirect('m=gpondasanonuinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);
case 'disconnect':
	$GPON->snmp->clear_options();
	$netdevdata = $LMS->GetNetDev($_GET['devid']);
	$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($_GET['devid']);
	$options_snmp = $GPON->GetGponOlt($netdevdata['gponoltid']);
	if (!empty($netdevdata['gponoltid'])) {
		$GPON->snmp->set_options($options_snmp);
		$gpon_onu = $GPON->GetGponOnu($_GET['id']);
		$snmp_result = $GPON->snmp->ONU_delete($_GET['numport'],$gpon_onu['onuid']);
		$snmp_error = $GPON->snmp->parse_result_error($snmp_result);
		if (strlen($snmp_error)) {
			$dev['linkolt'] = 'Nie można usunąć przypisania tego ONU - Błąd SNMP. '.$snmp_error;
			$SMARTY->assign('connect', $dev);
		} else {
			$GPON->GponOnuUnLink($_GET['devid'],$_GET['numport'],$_GET['id']);
			$SESSION->redirect('?m=gpondasanonuinfo&id='.$_GET['id']);
		}
	}
	break;
case 'connect':
	$portexist=intval($GPON->GetGponOltPortsExists($_GET['netdevicesid'],$_GET['numport']));
	if($portexist==0)
	{
		$error['numport'] = 'Taki port nie istnieje.';
	}
	else 
	{
		$maxonu=$GPON->GetGponOltPortsMaxOnu($_GET['netdevicesid'],$_GET['numport']);
		$onucountonport=$GPON->GetGponOnuCountOnPort($_GET['netdevicesid'],$_GET['numport']);
		if($onucountonport>=$maxonu)
		{
			$error['numport'] = 'Ten port osiągnął swoje maksimum. Nie można już przypisać ONU.';
		}
		$gponlink=$GPON->IsGponOnuLink2olt($_GET['id']);
		if($gponlink>0)
		{
			$error['linkolt'] = 'Nie można już przypisać tego ONU - zostało przypisane przed chwilą.';
			$dev['linkolt'] = $error['linkolt'];
		}
	}
	$dev['id'] = !empty($_GET['id']) ? intval($_GET['id']) : '0';
	$dev['numport'] = !empty($_GET['numport']) ? intval($_GET['numport']) : '0';
	if(!$error)
	{
		$GPON->snmp->clear_options();
		$netdevdata=$LMS->GetNetDev($_GET['netdevicesid']);
		$netdevdata['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($_GET['netdevicesid']);
		$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
		if($netdevdata['gponoltid']>0)
		{
			$error_option=$GPON->snmp->set_options($options_snmp);
			if(strlen($error_option)>0)
			{
				$dev['linkolt'] = 'Nie można przypisać tego ONU - Błąd SNMP. '.$error_option;
			}
			else 
			{
				$gpon_onu=$GPON->GetGponOnu($_GET['id']);
				$snmp_result=$GPON->snmp->ONU_add($_GET['numport'],$gpon_onu['name'],$gpon_onu['password'],$gpon_onu['onu_desc']);
				$snmp_error=$GPON->snmp->parse_result_error($snmp_result);
				if(strlen($snmp_error)>0)
				{
					$dev['linkolt'] = 'Nie można przypisać tego ONU - Błąd SNMP. '.$snmp_error;
				}
				else 
				{
					if($snmp_result['ONU_id']>0)
					{
						$GPON->GponOnuUpdateOnuId($_GET['id'],$snmp_result['ONU_id']);
						$GPON->GponOnuLink($_GET['netdevicesid'],$dev['numport'],$_GET['id']);
						$SESSION->redirect('?m=gpondasanonuinfo&id='.$_GET['id']);
					}
					else 
					{
						$dev['linkolt'] = 'Nie można przypisać ONU ID.';
					}
				}
			}
		}
	}
	$SMARTY->assign('connect', $dev);
	break;

default:
	$edit = 'data';
	break;
}

if (isset($_POST['netdev']) && (!isset($_POST['snmpsend']) || empty($_POST['snmpsend']))) {
	$netdevdata_old = $GPON->GetGponOnu($_GET['id']);
	$netdevdata = $_POST['netdev'];

	if (!isset($netdevdata['autoprovisioning']))
		$netdevdata['autoprovisioning'] = 0;

	if (!isset($netdevdata['xmlprovisioning']))
		$netdevdata['xmlprovisioning'] = 0;

	$netdevdata=array_merge($netdevdata_old,$netdevdata);
	$netdevdata['id'] = $_GET['id'];
	if(isset($_POST['voipaccountsid1']))
	{
		$netdevdata['voipaccountsid1']=$_POST['voipaccountsid1'];
	}
	if(isset($_POST['voipaccountsid2']))
	{
		$netdevdata['voipaccountsid2']=$_POST['voipaccountsid2'];
	}

	if(isset($_POST['host_id1']))
	{
		$netdevdata['host_id1']=$_POST['host_id1'];
	}
	if(isset($_POST['host_id2']))
	{
		$netdevdata['host_id2']=$_POST['host_id2'];
	}
	if(isset($_POST['devhost1']))
	{
		$netdevdata['host_id1']=$_POST['devhost_id1'];
	}
	if(isset($_POST['devhost2']))
	{
		$netdevdata['host_id2']=$_POST['devhost_id2'];
	}

	if(isset($netdevdata['autoprovisioning']) && intval($netdevdata['autoprovisioning'])==1)
	{
		//customer
		$cust_list_num = array();
		if (is_array($netdevdata) && count($netdevdata))
			foreach ($netdevdata['cid'] as $k5)
				$cust_list_num[] = intval($k5);
		$customer_test = 0;
		if (!empty($cust_list_num))
			for ($ii = 0; $ii < intval(max($cust_list_num)) + 1; $ii++)
				if (intval($netdevdata['cid'][$ii])) {
					$customer_test = 1;
					break;
				}
		if (!$customer_test)
			$error['customer_test'] = trans('Należy przypisać conajmniej do jednego klienta, jeżeli zaznaczono Wydany do klienta');
		//profil
		if (!isset($netdevdata['gponoltprofilesid']) || !intval($netdevdata['gponoltprofilesid']))
			$error['gponoltprofiles'] = trans('Wybierz profil, jeżeli zaznaczono Wydany do klienta');
	}

	if ($netdevdata['xmlprovisioning']) {
		$ports = $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelsid']);

		foreach (array('admin', 'telnet', 'user') as $password_type)
			if (!empty($netdevdata['properties'][$password_type . '_password'])) {
				if (strlen($netdevdata['properties'][$password_type . '_password']) < 8)
					$error[$password_type . '_password'] = trans('Password should contain at least 8 characters!');
			} else
				unset($netdevdata['properties'][$password_type . '_password']);

		if (isset($ports['wifi'])) {
			if (!empty($netdevdata['properties']['wifi_ssid']) && strlen($netdevdata['properties']['wifi_ssid']) < 8)
				$error['wifi_ssid'] = trans('WiFi SSID should contain at least 8 characters!');
			if (!empty($netdevdata['properties']['wifi_password']) && strlen($netdevdata['properties']['wifi_password']) < 8)
				$error['wifi_password'] = trans('WiFi password should contain at least 8 characters!');
		} else
			unset($netdevdata['properties']['wifi_ssid'], $netdevdata['properties']['wifi_password']);

		validate_lan_network($netdevdata['properties'], $error);
	}

	if (!$error) {
		if (isset($netdevdata['portsettings']))
			$GPON->UpdateGponOnuPorts($netdevdata['id'], $netdevdata['portsettings']);

		$netdevdata['properties'] = serialize($netdevdata['properties']);

		$GPON->GponOnuClearCustomers($_GET['id']);
		if (is_array($netdevdata) && count($netdevdata)) {
			$cust_list_num = array();
			foreach ($netdevdata['cid'] as $k5 => $v5)
				$cust_list_num[] = intval($k5);
			if (!empty($cust_list_num))
				for($ii = 0; $ii < intval(max($cust_list_num)) + 1; $ii++)
					$GPON->GponOnuAddCustomer($_GET['id'], $netdevdata['cid'][$ii]);
		}
		$netdevdata_old = $GPON->GetGponOnu($_GET['id']);
		$GPON->GponOnuUpdate($netdevdata);
		$netdevdata_now = $GPON->GetGponOnu($_GET['id']);
		if($GPON->IsGponOnuLink2olt($_GET['id']) && $netdevdata_old['onu_desc']!=$netdevdata_now['onu_desc'])
		{
			$GPON->snmp->clear_options();
			$netdevdata_tmp=$LMS->GetNetDev($netdevdata_now['gponoltnetdevicesid']);
			$netdevdata_tmp['gponoltid'] = $GPON->GetGponOltIdByNetdeviceId($netdevdata_now['gponoltnetdevicesid']);
			$options_snmp = $GPON->GetGponOlt($netdevdata_tmp['gponoltid']);
			$GPON->snmp->set_options($options_snmp);
			if($netdevdata_tmp['gponoltid']>0)
			{
				$GPON->snmp->ONU_set_description($netdevdata_now['gponoltnumport'],$netdevdata_now['onuid'],$netdevdata_now['onu_desc']);
			}
		}
		$SESSION->redirect('?m=gpondasanonuinfo&id='.$_GET['id']);
		
	}
} elseif (!isset($_POST['xjxfun'])) {
	$netdevdata = $GPON->GetGponOnu($_GET['id']);

	$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
	$GPON->snmp->set_options($options_snmp);
	$GPON->snmp->GPON_get_profiles();

	if (isset($_POST['snmpsend']) && intval($_POST['snmpsend'])) {
		if(isset($_POST['onureset']) && intval($_POST['onureset'])==1)
		{
			$GPON->snmp->ONU_Reset($netdevdata['gponoltnumport'],$netdevdata['onuid']);
		}
		elseif(isset($_POST['clear_mac']) && intval($_POST['clear_mac'])==1)
		{
			$GPON->snmp->ONU_ClearMac($netdevdata['gponoltnumport'],$netdevdata['onuid']);
		}
		elseif(isset($_POST['save']) && intval($_POST['save'])==1)
		{
			$GPON->snmp->ONU_Status($netdevdata['gponoltnumport'],$netdevdata['onuid'],$_POST['onu_status']);
			$GPON->snmp->ONU_set_description($netdevdata['gponoltnumport'],$netdevdata['onuid'],$_POST['onu_description']);
			$GPON->GponOnuDescriptionUpdate($_GET['id'],$_POST['onu_description']);
			$GPON->snmp->ONU_SetProfile($netdevdata['gponoltnumport'],$netdevdata['onuid'],$_POST['onu_profile']);
			$GPON->GponOnuProfileUpdateByName($_GET['id'], $_POST['onu_profile']);
			$GPON->snmp->ONU_SetAccount($netdevdata['gponoltnumport'],$netdevdata['onuid'],$_POST['onuaccount_username'],$_POST['onuaccount_password']);
                       if(check_ip($_POST['onu_xml_host'])
                           && strlen(trim($_POST['onu_xml_file'])) > 0 
                           && strlen(trim($_POST['onu_xml_id'])) > 0 
                           && strlen(trim($_POST['onu_xml_haslo'])) > 0 )
                       {
                               $GPON->snmp->ONU_set_xml_path ($netdevdata['gponoltnumport'], $netdevdata['onuid'], $_POST['onu_xml_id'], $_POST['onu_xml_haslo'], $_POST['onu_xml_host'], $_POST['onu_xml_file']);
                       }
                       else
                       {
                               $GPON->snmp->ONU_clear_xml_path ($netdevdata['gponoltnumport'], $netdevdata['onuid']);
                       }

			foreach($_POST as $k2=>$v2)
			{
				$typ=1; //default type - ethernet (most of ports)
				if(preg_match('/pots/',$k2))
				{
					$typ=2;
				}
				elseif(preg_match('/ces/',$k2))
				{
					$typ=3;
				}
				elseif(preg_match('/video/',$k2))
				{
					$typ=4;
				}
				elseif(preg_match('/virtualEthernet/',$k2))
				{
					$typ=5;
				}
				elseif(preg_match('/wifi/',$k2))
				{
					$typ=6;
				}
				if(preg_match('/onuport/',$k2))
				{
					$port=intval(preg_replace('/onuport\_.*\_/','',$k2));
					$GPON->snmp->ONU_SetPortStatus($netdevdata['gponoltnumport'],$netdevdata['onuid'],$typ,$port,$v2);
				}
				if(preg_match('/onuportautonego/',$k2))
				{
					$port=intval(preg_replace('/onuportautonego\_.*\_/','',$k2));
					$GPON->snmp->ONU_SetAutoNego($netdevdata['gponoltnumport'],$netdevdata['onuid'],$typ,$port,$v2);
				}
				if(preg_match('/onuportmediummode/',$k2))
				{
					$port=intval(preg_replace('/onuportmediummode\_.*\_/','',$k2));
					$GPON->snmp->ONU_SetMediumMode($netdevdata['gponoltnumport'],$netdevdata['onuid'],$typ,$port,$v2);
				}
				if(preg_match('/onuportspeed/',$k2))
				{
					$port=intval(preg_replace('/onuportspeed\_.*\_/','',$k2));
					$duplex=intval($_POST['onuportduplex'.preg_replace('/onuportspeed/','',$k2)]);
					$GPON->snmp->ONU_SetPortSpeed($netdevdata['gponoltnumport'],$netdevdata['onuid'],$typ,$port,$v2,$duplex);
				}
				if(preg_match('/phonesvoip/',$k2))
				{
					$port=intval(preg_replace('/phonesvoip\_.*\_/','',$k2));
					$phone_data=$GPON->GetPhoneVoip($v2);
					$GPON->snmp->ONU_SetPhoneVoip($netdevdata['gponoltnumport'],$netdevdata['onuid'],$typ,$port,$phone_data);
					$GPON->GponOnuVoipUpdate($_GET['id'], $port, $v2);
				}
				if(preg_match('/hostip/',$k2))
				{
					$hostid=intval(preg_replace('/hostip\_/','',$k2));
					$gw=$_POST['hostgw_'.$hostid];
					$GPON->snmp->ONU_SetHostIp($netdevdata['gponoltnumport'],$netdevdata['onuid'],$hostid,$v2,$gw);
				}
			}
		}
		$SESSION->redirect('?m=gpondasanonuedit&id='.$_GET['id']);
	}
	$phonesvoip=$GPON->GetGponOnuPhoneVoip($_GET['id']);
	$snmponudata=$GPON->snmp->ONU_get_param_table_edit($netdevdata['gponoltnumport'],$netdevdata['onuid'],$_GET['id'],$phonesvoip,$netdevdata['name']);
		
}

if (!isset($_POST['xjxfun'])) {
	$netdevconnected = $GPON->GetGponOltConnectedNames($_GET['id']);
	$netdevlist = $GPON->GetNotConnectedOlt($netdevconnected['gponoltid']);
	if (is_array($netdevlist) && !empty($netdevlist))
		$numports = $GPON->GetFreeOltPort($netdevlist[0]['id']);

	$SMARTY->assign('snmponudata', $snmponudata);
	$gponoltprofiles = $GPON->GetGponOltProfiles(empty($netdevconnected) ? null : $netdevconnected[0]['gponoltid']);
	$SMARTY->assign('gponoltprofiles', $gponoltprofiles);

	$gpononumodels = $GPON->GetGponOnuModelsList();
	unset($gpononumodels['total'], $gpononumodels['order'], $gpononumodels['direction']);

	$customerid = intval($netdevdata['ownerid']);
	if ($customerid)
		include(MODULES_DIR . DIRECTORY_SEPARATOR .'customer.inc.php');
}

/* Using AJAX plugins */
function GetFreeOltPort_Xj($netdevicesid)
{
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$freeports=$GPON->GetFreeOltPort($netdevicesid);
	if(is_array($freeports) && count($freeports)>0)
	{
		$objResponse->script("document.getElementById('numport').options.length=0;"); 
		$i=0;
		foreach($freeports as $value)
		{
			$objResponse->script('xajax.$("numport").options['.$i.'] = new Option("'.$value['numport'].'","'.$value['numport'].'");');
			$i++;
		}
	}
	$objResponse->call("GetFreeOltPort_Xj");
	return $objResponse;
}
function ONU_get_param_Xj($gponoltid,$OLT_id,$ONU_id,$id,$ONU_name='')
{
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$phonesvoip=$GPON->GetGponOnuPhoneVoip($id);
	$options_snmp=$GPON->GetGponOlt($gponoltid);
	$GPON->snmp->set_options($options_snmp);
	$table_param=$GPON->snmp->ONU_get_param_table_edit($OLT_id,$ONU_id,$id,$phonesvoip,$ONU_name);
	$objResponse->script("document.getElementById('pokaz_parametry_".$id."').value='Ukryj parametry';"); 
	$objResponse->script("document.getElementById('pokaz_parametry_".$id."').onclick=function(){document.getElementById('ONU_param_".$id."').innerHTML='';document.getElementById('pokaz_parametry_".$id."').value='Pokaż parametry';document.getElementById('pokaz_parametry_".$id."').onclick=function(){xajax_ONU_get_param_Xj(".$gponoltid.",".$OLT_id.",".$ONU_id.",".$id.",'".$ONU_name."');}};");
	$objResponse->assign("ONU_param_".$id,"innerHTML",$table_param);
	return $objResponse;
}
function ONU_Voip_Phone_Xj($id_clients,$pot1_id,$pot2_id,$disable=0)
{
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$clients=explode(';',$id_clients);
	if(is_array($clients) && count($clients)>0)
	{
		foreach($clients as $k=>$v)
		{
			$temp=array();
			$v=intval($v);
			if($v>0)
			{
				$temp=$GPON->GetPhoneVoipForCustomer($v);
				if(is_array($temp) && count($temp)>0)
				{
					foreach($temp as $k=>$v)
					{
						$phonesvoip[$v['id']]=$v['phone'];
					}
				}
			}
		}
	}
	$select_disabled='';
	if($disable==1)
	{
		$select_disabled=' disabled="disabled" ';
	}
	$table='<table border="0">
			<tr><td align="right">1.</td><td>
			<select id="tmp_pots_1_phone" name="tmp_pots_1_phone" onchange="document.getElementById(\'pots_1_phone\').value=this.value;"'.$select_disabled.'>
			<option value="">wybierz</option>';
			if(is_array($phonesvoip) && count($phonesvoip)>0)
			{
				foreach($phonesvoip as $k=>$v)
				{
					$table.='<option';
					if($pot1_id==$k)
					{
						$table.=' selected="selected"';
					}
					$table.=' value="'.$k.'">'.$v.'</option>';
				}
			}
			$table.='</select>
			</td></tr>
			<tr><td align="right">2.</td><td>
			<select id="tmp_pots_2_phone" name="tmp_pots_2_phone" onchange="document.getElementById(\'pots_2_phone\').value=this.value;"'.$select_disabled.'>
			<option value="">wybierz</option>';
			if(is_array($phonesvoip) && count($phonesvoip)>0)
			{
				foreach($phonesvoip as $k=>$v)
				{
					$table.='<option';
					if($pot2_id==$k)
					{
						$table.=' selected="selected"';
					}
					$table.=' value="'.$k.'">'.$v.'</option>';
				}
			}
			$table.='</select>
			</td></tr>
			</table>';
	$objResponse->script("document.getElementById('show_voip').style.display='block';"); 
	$objResponse->assign("ONU_Voip_Phone","innerHTML",$table);
	return $objResponse;
}

function ONU_Host_hosts_Xj($id_clients,$host1_id,$host2_id,$disable=0)
{
	// xajax response
	$GPON = LMSGponDasanPlugin::getGponInstance();
	$objResponse = new xajaxResponse();
	$clients=explode(';',$id_clients);
	if(is_array($clients) && count($clients)>0)
	{
		foreach($clients as $k=>$v)
		{
			$temp=array();
			$v=intval($v);
			if($v>0)
			{
				$temp=$GPON->GetHostNameForCustomer($v);
				if(is_array($temp) && count($temp)>0)
				{
					foreach($temp as $k=>$v)
					{
						$hostid[$v['id']]=$v['host'];
					}
				}
			}
		}
	}
	$select_disabled='';
	if($disable==1)
	{
		$select_disabled=' disabled="disabled" ';
	}
	$table='<table border="0">
			<tr><td align="right">1.</td><td>
			<select id="tmp_hostid_1" name="tmp_hostid_1" onchange="document.getElementById(\'hostid_1\').value=this.value;"'.$select_disabled.'>
			<option value="">wybierz</option>';
			if(is_array($hostid) && count($hostid)>0)
			{
				foreach($hostid as $k=>$v)
				{
					$table.='<option';
					if($host1_id==$k)
					{
						$table.=' selected="selected"';
					}
					$table.=' value="'.$k.'">'.$v.'</option>';
				}
			}
			$table.='</select>
			</td></tr>
			<tr><td align="right">2.</td><td>
			<select id="tmp_hostid_2" name="tmp_hostid_2" onchange="document.getElementById(\'hostid_2\').value=this.value;">
			<option value="">wybierz</option>';
			if(is_array($hostid) && count($hostid)>0)
			{
				foreach($hostid as $k=>$v)
				{
					$table.='<option';
					if($host2_id==$k)
					{
						$table.=' selected="selected"';
					}
					$table.=' value="'.$k.'">'.$v.'</option>';
				}
			}
			$table.='</select>
			</td></tr>
			</table>';
	$objResponse->assign("ONU_Host_hosts","innerHTML",$table);
	return $objResponse;
}

$LMS->InitXajax();

include('gpondasanonuxajax.inc.php');

$LMS->RegisterXajaxFunction(array('GetFreeOltPort_Xj', 'ONU_get_param_Xj', 'ONU_Voip_Phone_Xj', 'ONU_Host_hosts_Xj'));
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$netdevips = $LMS->GetNetDevIPs($_GET['id']);
$nodelist = $LMS->GetUnlinkedNodes();

$netcomplist = $LMS->GetNetDevLinkedNodes($_GET['id']);


unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);


$layout['pagetitle'] = 'GPON-ONU: '.trans('$a ($b/$c)', $netdevdata['name'], $netdevdata['producer'], $netdevdata['model']);

if($subtitle) $layout['pagetitle'] .= ' - '.$subtitle;

$gpononu2customers=$GPON->GetGponOnu2Customers($_GET['id']);
if (is_array($gpononu2customers) && count($gpononu2customers) && (!isset($_POST) || count($_POST)==0)) {
	$i = 0;
	foreach($gpononu2customers as $k => $v) {
		$netdevdata['cid'][$i] = $v['customersid'];
		$i++;
	}
}
if(count($gpononu2customers)>$onu_customerlimit)
{
	$onu_customerlimit=count($gpononu2customers);
}
if($GPON->IsNodeIdNetDevice($netdevdata['host_id1']))
    $netdevdata['host_id1_dev']=1;
if($GPON->IsNodeIdNetDevice($netdevdata['host_id2']))
    $netdevdata['host_id2_dev']=1;

if(ConfigHelper::checkConfig('gpon-dasan.use_radius'))
    $netdevdata['autoscript'] =0;

$SMARTY->assign('vlans', parse_vlans());

if (!isset($netdevdata['portsettings'])) {
	$modelports = $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelsid']);
	$onuports = $GPON->GetGponOnuPorts($_GET['id']);
	$netdevdata['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, $onuports);
	foreach ($netdevdata['portsettings'] as &$portsettings)
		$portsettings['vlan'] = array('id' => '', 'name' => '');
}

$SMARTY->assign('error',$error);
$SMARTY->assign('onu_customerlimit',$onu_customerlimit);
$SMARTY->assign('netdevhosts', $GPON->GetHostForNetdevices());
$SMARTY->assign('modelports', $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelsid']));
$SMARTY->assign('notgpononudevices', $GPON->GetNotGponOnuDevices($_GET['id']));
$SMARTY->assign('netdevinfo',$netdevdata);
$SMARTY->assign('numports',$numports);
$SMARTY->assign('gpononumodels',$gpononumodels);
if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('gpononu2customers', $gpononu2customers);
$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('replacelist',$replacelist);
$SMARTY->assign('replacelisttotal',$replacelisttotal);
$SMARTY->assign('devlinktype',$SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype',$SESSION->get('nodelinktype'));
$SMARTY->assign('nastype', $LMS->GetNAStypes());
$SMARTY->assign('lannetworks', parse_lan_networks());

switch ($edit) {
	case 'data':
		$SMARTY->display('gpondasanonuedit.html');
		break;
	default:
		$SMARTY->display('gpondasanonuinfo.html');
		break;
}

?>
