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

function ONU_UpdateProperties($xmlprovisioning, $modelid) {
	global $SMARTY;
	$GPON = LMSGponDasanPlugin::getGponInstance();

	// xajax response
	$objResponse = new xajaxResponse();

	$passwords = 'none';
	$lansettings = 'none';
	$wifisettings = 'none';
	if ($xmlprovisioning) {
		$passwords = '';
		$lansettings = '';
		$ports = $GPON->GetGponOnuModelPorts($modelid);
		if (isset($ports['wifi']))
			$wifisettings = '';
	}
	$objResponse->assign("passwords", "style.display", $passwords);
	$objResponse->assign("lansettings", "style.display", $lansettings);
	$objResponse->assign("wifisettings", "style.display", $wifisettings);

	if ($xmlprovisioning || ConfigHelper::checkConfig('gpon-dasan.use_radius')) {
		$modelports = $GPON->GetGponOnuModelPorts($modelid);
		$onuports = $GPON->GetGponOnuPorts($_GET['id']);
		$netdevinfo['portsettings'] = $GPON->GetGponOnuAllPorts($modelports, $onuports);
		if (isset($_GET['id']))
			$netdevinfo['properties'] = $GPON->GetGponOnuProperties($_GET['id']);
		else
			$netdevinfo['properties'] = array();
		$netdevinfo['xmlprovisioning'] = $xmlprovisioning;
		$SMARTY->assign('netdevinfo', $netdevinfo);
		$SMARTY->assign('vlans', parse_vlans());
		$contents = $SMARTY->fetch('gpondasanonu/gpondasanonuporttable.html');
		$objResponse->assign("portsettingstable", "innerHTML", $contents);
		$portsettings = '';
	} else
		$portsettings = 'none';
	$objResponse->assign("portsettings", "style.display", $portsettings);

	return $objResponse;
}

function ONU_GeneratePasswords() {
	// xajax response
	$objResponse = new xajaxResponse();

	$admin_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_admin_password', '', true);
	$telnet_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_telnet_password', '', true);
	$user_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_user_password', '', true);
	$passwords = compact('admin_password', 'telnet_password', 'user_password');

	$password_characters = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_password_characters',
		'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

	foreach (array_keys($passwords) as $password_type) {
		$password = $passwords[$password_type];
		if ($password) {
			if (preg_match('/%(?<chars>[0-9]+)?random%/', $password, $m)) {
				$chars = isset($m['chars']) ? intval($m['chars']) : 12;
				$password = preg_replace('/%[0-9]*random%/',
					generate_random_string($chars, $password_characters), $password);
			}
			$objResponse->assign($password_type, "value", $password);
		}
	}

	return $objResponse;
}

function ONU_GenerateWifiSettings($onudata) {
	$GPON = LMSGponDasanPlugin::getGponInstance();

	if (isset($onudata['id'])) {
		$onu = $GPON->GetGponOnu($onudata['id']);
		$customers = $GPON->GetGponOnu2Customers($onudata['id']);
	} else {
		$onu['name'] = $onudata['sn'];
		$customers[0]['customersid'] = $onudata['customerid'];
	}

	// xajax response
	$objResponse = new xajaxResponse();

	$wifi_ssid = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_default_wifi_ssid', '');
	$wifi_ssid = str_replace('%sn%', $onu['name'], $wifi_ssid);
	$wifi_ssid = str_replace('%customerid%', empty($customers) ? 0 : intval($customers[0]['customersid']), $wifi_ssid);
	$objResponse->assign("wifi_ssid", "value", $wifi_ssid);

	$password_characters = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_password_characters',
		'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

	$wifi_password = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_default_wifi_password', '');
	if (preg_match('/%(?<chars>[0-9]+)?random%/', $wifi_password, $m)) {
		$chars = isset($m['chars']) ? intval($m['chars']) : 12;
		$wifi_password = preg_replace('/%[0-9]*random%/',
			generate_random_string($chars, $password_characters), $wifi_password);
	}
	$objResponse->assign("wifi_password", "value", $wifi_password);

	return $objResponse;
}

function ONU_LoadNetworkSettings($netname) {
	// xajax response
	$objResponse = new xajaxResponse();

	if (empty($netname))
		$lan_networks[0] = array(
			'net' => '',
			'mask' => '',
			'gateway' => '',
			'first_dhcp_ip' => '',
			'last_dhcp_ip' => '',
		);
	else {
		$lan_networks = parse_lan_networks($netname);
		if (empty($lan_networks))
			return $objResponse;
	}

	$network = $lan_networks[0];
	$objResponse->assign('lan_netaddress', "value", $network['net']);
	$objResponse->assign('lan_netmask', "value", $network['mask']);
	$objResponse->assign('lan_gateway', "value", $network['gateway']);
	$objResponse->assign('lan_firstdhcpip', "value", $network['first_dhcp_ip']);
	$objResponse->assign('lan_lastdhcpip', "value", $network['last_dhcp_ip']);

	return $objResponse;
}

function ONU_AutoFillNetworkSettings($netaddress, $netmask) {
	// xajax response
	$objResponse = new xajaxResponse();

	$netaddress_long = ip_long($netaddress);
	$braddress_long = ip_long(getbraddr($netaddress, $netmask));
	$objResponse->assign('lan_gateway', "value", long2ip($netaddress_long + 1));
	$objResponse->assign('lan_firstdhcpip', "value", long2ip($netaddress_long + 2));
	$objResponse->assign('lan_lastdhcpip', "value", long2ip($braddress_long - 1));

	return $objResponse;
}

$LMS->RegisterXajaxFunction(array('ONU_UpdateProperties', 'ONU_GeneratePasswords',
	'ONU_GenerateWifiSettings', 'ONU_LoadNetworkSettings', 'ONU_AutoFillNetworkSettings'));

?>
