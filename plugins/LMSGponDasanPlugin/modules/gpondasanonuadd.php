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

$onu_customerlimit = ConfigHelper::getConfig('gpon-dasan.onu_customerlimit', 1);
$onu_check_add = isset($_GET['onu_check_add']) ? intval($_GET['onu_check_add']) : 0;
if (isset($_POST['onucheck']))
	$onu_check_add = 1;

$gpononumodels = $GPON->GetGponOnuModelsList();
unset($gpononumodels['total'], $gpononumodels['order'], $gpononumodels['direction']);
$SMARTY->assign('gpononumodels', $gpononumodels);

$netdevdata['voipaccountsid1'] = 0;
$netdevdata['voipaccountsid2'] = 0;
$netdevdata['host_id1'] = 0;
$netdevdata['host_id2'] = 0;
if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];
	$netdevdata['voipaccountsid1'] = $_POST['pots_1_phone'];
	$netdevdata['voipaccountsid2'] = $_POST['pots_2_phone'];
	if($_POST['devhost1'])
		$netdevdata['host_id1'] = $_POST['devhost_id1'];
	else
		$netdevdata['host_id1'] = $_POST['hostid_1'];
	if($_POST['devhost2'])
		$netdevdata['host_id2'] = $_POST['devhost_id2'];
	else
		$netdevdata['host_id2'] = $_POST['hostid_2'];

	if ($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif (strlen($netdevdata['name']) < 8)
		$error['name'] = 'Nazwa zbyt krótka (powinna mieć co najmniej 8 znaków)';
	elseif (!preg_match('/^.+[a-fA-F0-9]{8}$/D', $netdevdata['name']))
		$error['name'] = 'Nazwa musi składać się z co najmniej 8 znaków i kończyć się ośmioma cyframi lub literami a-f';
	else
		$netdevdata['name'] = $netdevdata['name'];

	if($GPON->GponOnuNameExists($netdevdata['name']))
	{
		$error['name'] = 'Nazwa musi być unikalna. Taka nazwa już istnieje.';
	}

	if (isset($netdevdata['autoprovisioning']) && intval($netdevdata['autoprovisioning'])) {
		//customer
		$cust_list_num = array();
		if (is_array($netdevdata) && count($netdevdata))
			foreach ($netdevdata['cid'] as $k5 => $v5)
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
/*	if($onu_check_add==1)
	{
		if(!isset($netdevdata['gponoltprofilesid']) || intval($netdevdata['gponoltprofilesid'])==0)
		{
			$error['gponoltprofiles'] = trans('Wybierz profil');
		}
	} */

	if ($netdevdata['xmlprovisioning']) {
		$ports = $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelid']);

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
	}

	if (!$error) {
		$netdevdata['properties'] = serialize($netdevdata['properties']);

		$netdevid = $GPON->GponOnuAdd($netdevdata);
		if (!empty($netdevid))
			$GPON->UpdateGponOnuPorts($netdevid, $netdevdata['portsettings']);

			$cust_list_num = array();
			if (is_array($netdevdata) && count($netdevdata))
				foreach ($netdevdata['cid'] as $k5 => $v5)
					$cust_list_num[] = intval($k5);
			if (!empty($cust_list_num))
				for ($ii=0; $ii < intval(max($cust_list_num)) + 1; $ii++)
					$GPON->GponOnuAddCustomer($netdevid,$netdevdata['cid'][$ii]);

			if ($onu_check_add==1 && intval($netdevid) && isset($_POST['netdevicesid']) && intval($_POST['netdevicesid'])
				&& isset($_POST['olt_port']) && intval($_POST['olt_port']) && isset($_POST['onu_id']) && intval($_POST['onu_id'])) {
				$GPON->GponOnuUpdateOnuId($netdevid,$_POST['onu_id']);
				$GPON->GponOnuLink($_POST['netdevicesid'],$_POST['olt_port'],$netdevid);

				$res = 0;
				if ($netdevdata['xmlprovisioning'])
					$res = $GPON->GponOnuXmlProvisioning(array('id' => $netdevid));

				//if (isset($_POST['onu_description_old'])
				//	&& $netdevdata['onu_description'] != $_POST['onu_description_old']) {
				if (!$res) {
					$options_snmp=$GPON->GetGponOlt($_POST['gponoltid']);
					$GPON->snmp->set_options($options_snmp);
					$GPON->snmp->ONU_set_description($_POST['olt_port'],$_POST['onu_id'],$netdevdata['onu_description']);
					$gponoltprofiles_temp = $GPON->GetGponOltProfiles($_POST['gponoltid']);
					$GPON->snmp->ONU_SetProfile($_POST['olt_port'], $_POST['onu_id'],
						$gponoltprofiles_temp[$netdevdata['gponoltprofilesid']]['name']);
				}
			}
			$SESSION->redirect('?m=gpondasanonuinfo&id=' . $netdevid);

        }

	$SMARTY->assign('error', $error);

	$onu_keys = array('onu_description', 'gpononumodelid', 'cid');
} else
	$netdevdata['xmlprovisioning'] = ConfigHelper::checkConfig('gpon-dasan.xml_provisioning_default_enabled') ? 1 : 0;


/* Using AJAX plugins */

function ONU_Voip_Phone_Xj($id_clients,$pot1_id,$pot2_id)
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
	$table='<table border="0">
			<tr><td align="right">1.</td><td>
			<select id="tmp_pots_1_phone" name="tmp_pots_1_phone" onchange="document.getElementById(\'pots_1_phone\').value=this.value;">
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
			<select id="tmp_pots_2_phone" name="tmp_pots_2_phone" onchange="document.getElementById(\'pots_2_phone\').value=this.value;">
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

function ONU_Host_hosts_Xj($id_clients,$host1_id,$host2_id)
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
	$table='<table border="0">
			<tr><td align="right">1.</td><td>
			<select id="tmp_hostid_1" name="tmp_hostid_1" onchange="document.getElementById(\'hostid_1\').value=this.value;">
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

$LMS->RegisterXajaxFunction(array('ONU_Voip_Phone_Xj', 'ONU_Host_hosts_Xj'));
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$layout['pagetitle'] = trans('New Device').': GPON-ONU';
$SMARTY->assign('onu_customerlimit',$onu_customerlimit);

$SMARTY->assign('vlans', parse_vlans());

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('onu_check_add', $onu_check_add);
if ($onu_check_add) {
	if (isset($_POST['onucheck']) && is_array($_POST['onucheck']) && count($_POST['onucheck']))
		foreach ($_POST['onucheck'] as $k => $v) {
			$netdev[$k] = $v;
			$onucheck[$k] = $v;
		}
	$netdev['olt_data'] = '<A href="?m=gpondasanoltinfo&id='.$onucheck['netdevicesid'].'">'.$onucheck['olt_name'].'</A>  Port: <b>'.$onucheck['olt_port'].'</b>';
	$netdev['name'] = $onucheck['onu_serial'];
	$netdev['onu_description_old'] = $onucheck['onu_description'];
	$netdev['onu_passwordResult'] = $netdev['onu_password'];
	if ($onucheck['onu_passwordMode'] == 'enable(1)' || !strlen($onucheck['onu_passwordMode'])) {
		$netdev['onu_password'] = '';
		$netdev['onu_passwordResult'] = 'auto-learning';
	}
	$SMARTY->assign('netdevicesid', $_GET['netdevicesid']);

	if (isset($_POST['netdev'])) {
		foreach ($onu_keys as $key)
			$netdev[$key] = $netdevdata[$key];
		$modelports = $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelid']);
		$netdev['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, $netdevdata['portsettings']);
	} elseif (!isset($netdev['gpononumodelid'])) {
		foreach ($gpononumodels as $model)
			if ($model['name'] == $netdev['onu_model']) {
				$gpononumodelid = $model['id'];
				break;
			}
		$modelports = $GPON->GetGponOnuModelPorts($gpononumodelid);
		$netdev['gpononumodelid'] = $gpononumodelid;
		$netdev['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, array());
	}
} else {
	if (isset($_POST['netdev'])) {
		foreach ($onu_keys as $key)
			$netdev[$key] = $netdevdata[$key];
		$modelports = $GPON->GetGponOnuModelPorts($netdevdata['gpononumodelid']);
		$netdev['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, $netdevdata['portsettings']);
	} else {
		$onumodel = reset($gpononumodels);
		$modelports = $GPON->GetGponOnuModelPorts($onumodel['id']);
		$netdev['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, array());
	}
}

$gponoltprofiles = $GPON->GetGponOltProfiles(is_array($netdev) && array_key_exists('gponoltid', $netdev) ? $netdev['gponoltid'] : null);
$SMARTY->assign('gponoltprofiles', $gponoltprofiles);

$netdev_temp = is_array($netdev) ? $netdev : array();
if (isset($_POST['netdev'])) {
	$netdev_temp = array_merge($netdevdata, $netdev_temp);
	$netdev_temp['name'] = isset($netdev['name']) ? $netdev['name'] : $netdev_temp['name'];
} else
	$netdev_temp['xmlprovisioning'] = ConfigHelper::checkConfig('gpon-dasan.xml_provisioning_default_enabled') ? 1 : 0;

$netdev_temp['name'] = $netdev_temp['name'];
$SMARTY->assign('netdevinfo', $netdev_temp);
$SMARTY->assign('netdevhosts', $GPON->GetHostForNetdevices());
$SMARTY->assign('onucheck', $onucheck);
$SMARTY->assign('notgpononudevices', $GPON->GetNotGponOnuDevices());
$SMARTY->assign('lannetworks', parse_lan_networks());
$SMARTY->display('gpondasanonuadd.html');

?>
