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

if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];
	//-GPON-OLT
	//Dane OLTPORTS
	$gponoltportsdata=$_POST['gponoltports'];
	//-GPON-OLT
	if($netdevdata['ports'] == '')
		$netdevdata['ports'] = 0;
	else
		$netdevdata['ports'] = intval($netdevdata['ports']);

	if(empty($netdevdata['clients']))
		$netdevdata['clients'] = 0;
	else
		$netdevdata['clients'] = intval($netdevdata['clients']);
			
	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif(strlen($netdevdata['name'])>32)
		$error['name'] = trans('Device name is too long (max.32 characters)!');

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
		if(strlen(trim($netdevdata['snmp_privacy_passphrase']))==0)
		{
			$error['snmp_privacy_passphrase'] = 'Podaj Privacy passphrase';
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
        if(!$error)
        {
		if($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;
		
		if(!isset($netdevdata['shortname'])) $netdevdata['shortname'] = '';
                if(!isset($netdevdata['secret'])) $netdevdata['secret'] = '';
                if(!isset($netdevdata['community'])) $netdevdata['community'] = '';
                if(!isset($netdevdata['nastype'])) $netdevdata['nastype'] = 0;
		
        //-GPON-OLT
        //Dodawanie danych OLT
		$netdevdata['gponoltid'] = $GPON->GponOltAdd($netdevdata);
		if($netdevdata['gponoltid']>0 && is_array($gponoltportsdata) && count($gponoltportsdata)>0)
		{
			foreach($gponoltportsdata as $k=>$v)
			{
				$gponoltports[$k]['gponoltid']=$netdevdata['gponoltid'];
				$gponoltports[$k]['numport']=$k;
				$gponoltports[$k]['maxonu']=$v;
			}
			$GPON->GponOltPortsAdd($gponoltports);
		}
		//-GPON-OLT
		if (empty($netdevdata['netdevid']))
			$netdevid = $LMS->NetDevAdd($netdevdata);
		else {
			$netdevid = $netdevdata['netdevid'];
			$GPON->NetDevUpdate(array('id' => $netdevid, 'gponoltid' => $netdevdata['gponoltid']));
		}
		$SESSION->redirect('?m=gpondasanoltinfo&id=' . $netdevid);
        }
	
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdev', $netdevdata);
}

$layout['pagetitle'] = trans('New Device').': GPON - OLT';

$SMARTY->assign('nastype', $LMS->GetNAStypes());
$SMARTY->assign('notgponoltdevices', $GPON->GetNotGponOltDevices());

$SMARTY->display('gpondasanoltadd.html');

?>
