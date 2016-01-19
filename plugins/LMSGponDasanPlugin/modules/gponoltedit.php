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

if(! $LMS->NetDevExists($_GET['id']))
{
	$SESSION->redirect('?m=gponoltlist');
}		

$action = !empty($_GET['action']) ? $_GET['action'] : '';
$edit = '';
$subtitle = '';

switch($action)
{

case 'writememory':

	$GPON->snmp->clear_options();
	$netdevdata=$LMS->GetNetDev($_GET['id']);
	if($netdevdata['gponoltid']>0)
	{
		$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
		$GPON->snmp->set_options($options_snmp);
		$GPON->snmp->OLT_write_config();
		$GPON->Log(4, 'gponolt', $netdevdata['gponoltid'], 'SNMP: write memory');
	}
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);

case 'disconnectnode':

	$LMS->NetDevLinkNode($_GET['nodeid'],0);
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);

case 'chkmac':

        $DB->Execute('UPDATE nodes SET chkmac=? WHERE id=?', array($_GET['chkmac'], $_GET['ip']));
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);

case 'duplex':

        $DB->Execute('UPDATE nodes SET halfduplex=? WHERE id=?', array($_GET['duplex'], $_GET['ip']));
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);

case 'nas':
	$DB->Execute('UPDATE nodes SET nas=? WHERE id=?', array($_GET['nas'], $_GET['ip']));
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id'].'&ip='.$_GET['ip']);
	
case 'disconnect':
	$GPON->snmp->clear_options();
	$netdevdata=$LMS->GetNetDev($_GET['id']);
	$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
	if($netdevdata['gponoltid']>0)
	{
		$GPON->snmp->set_options($options_snmp);
		$gpon_onu=$GPON->GetGponOnu($_GET['devid']);
		$snmp_result=$GPON->snmp->ONU_delete($_GET['numport'],$gpon_onu['onuid']);
		$snmp_error=$GPON->snmp->parse_result_error($snmp_result);
		if(strlen($snmp_error)>0)
		{
			$dev['linkolt'] = 'Nie można usunąć przypisania tego ONU - Błąd SNMP. '.$snmp_error;
			$SMARTY->assign('connect', $dev);
		}
		else 
		{
			$GPON->GponOnuUnLink($_GET['id'],$_GET['numport'],$_GET['devid']);
			$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
		}
	}
	break;
case 'connect':

	$portexist=intval($GPON->GetGponOltPortsExists($_GET['id'],$_GET['numport']));
	if($portexist==0)
	{
		$error['numport'] = 'Taki port nie istnieje.';
	}
	else 
	{
		$maxonu=$GPON->GetGponOltPortsMaxOnu($_GET['id'],$_GET['numport']);
		$onucountonport=$GPON->GetGponOnuCountOnPort($_GET['id'],$_GET['numport']);
		if($onucountonport>=$maxonu)
		{
			$error['numport'] = 'Ten port osiągnął swoje maksimum. Nie można już przypisać ONU.';
		}
		$gponlink=$GPON->IsGponOnuLink2olt($_GET['gpononu']);
		if($gponlink>0)
		{
			$error['linkolt'] = 'Nie można już przypisać wybranego wcześniej ONU - zostało przypisane przed chwilą.';
			$dev['linkolt'] = $error['linkolt'];
		}
	}
	$dev['id'] = !empty($_GET['gpononu']) ? intval($_GET['gpononu']) : '0';
	$dev['numport'] = !empty($_GET['numport']) ? intval($_GET['numport']) : '0';
	if(!$error)
	{
		$GPON->snmp->clear_options();
		$netdevdata=$LMS->GetNetDev($_GET['id']);
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
				$gpon_onu=$GPON->GetGponOnu($_GET['gpononu']);
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
						$GPON->GponOnuUpdateOnuId($_GET['gpononu'],$snmp_result['ONU_id']);
						$GPON->GponOnuLink($_GET['id'],$dev['numport'],$_GET['gpononu']);
						$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
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
    
case 'connectnode':

	$linktype = !empty($_GET['linktype']) ? intval($_GET['linktype']) : '0';
	$node['port'] = !empty($_GET['port']) ? intval($_GET['port']) : '0';
	$node['id'] = !empty($_GET['nodeid']) ? intval($_GET['nodeid']) : '0';

	$ports = $DB->GetOne('SELECT ports FROM netdevices WHERE id = ?', array($_GET['id']));
	$takenports = $LMS->CountNetDevLinks($_GET['id']);

	if($ports <= $takenports)
		$error['linknode'] = trans('No free ports on device!');
	elseif($node['port'])
	{
		if(!preg_match('/^[0-9]+$/', $node['port']) || $node['port'] > $ports)
		{
			$error['port'] = trans('Incorrect port number!');	
		}
		elseif($DB->GetOne('SELECT id FROM nodes WHERE netdev=? AND port=? AND ownerid>0', 
				array($_GET['id'], $node['port']))
			|| $DB->GetOne('SELECT 1 FROM netlinks WHERE (src = ? OR dst = ?)
				AND (CASE src WHEN ? THEN srcport ELSE dstport END) = ?',
				array($_GET['id'], $_GET['id'], $_GET['id'], $node['port'])))
		{
			$error['port'] = trans('Selected port number is taken by other device or node!');
		}
	}

	$SESSION->save('nodelinktype', $linktype);
	
	if(!$error) 
	{
		$LMS->NetDevLinkNode($node['id'], $_GET['id'], $linktype, $node['port']);
		$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
	}

	$SMARTY->assign('connectnode', $node);

	break;

case 'addip':

	$subtitle = trans('New IP address');
	$nodeipdata['access'] = 1;
	$nodeipdata['mac'] = '';
	$SMARTY->assign('nodeipdata', $nodeipdata);
	$edit = 'addip';
	break;

case 'editip':

	$nodeipdata = $LMS->GetNode($_GET['ip']);
	$subtitle = trans('IP address edit');
	$nodeipdata['ipaddr'] = $nodeipdata['ip'];
	$nodeipdata['mac'] = $nodeipdata['macs'][0]['mac'];
	$SMARTY->assign('nodeipdata',$nodeipdata);
	$edit = 'ip';
	break;

case 'switchlinktype':

	$LMS->SetNetDevLinkType($_GET['devid'], $_GET['id'], $_GET['linktype']);
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);

case 'switchnodelinktype':

	$LMS->SetNodeLinkType($_GET['nodeid'], $_GET['linktype']);
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);

case 'ipdel':

	if($_GET['is_sure']=='1' && !empty($_GET['ip']))
	{
		$DB->Execute('DELETE FROM nodes WHERE id = ? AND ownerid = 0', array($_GET['ip']));
		$GPON->Log(4, 'gponolt', $_GET['id'], 'ip deleted '.$_GET['ip']);
	}
	
	$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);

case 'ipset':

	if (!empty($_GET['ip']))
		$DB->Execute('UPDATE nodes SET access = (CASE access WHEN 1 THEN 0 ELSE 1 END)
			WHERE id = ? AND ownerid = 0', array($_GET['ip']));
	else
    		$LMS->IPSetU($_GET['id'], $_GET['access']);

	header('Location: ?'.$SESSION->get('backto'));
	break;							

case 'formaddip':

	$subtitle = trans('New IP address');
	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid'] = 0;
	$nodeipdata['mac'] = str_replace('-',':',$nodeipdata['mac']);

	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=='' && $nodeipdata['mac']=='' && $nodeipdata['name']=='' && $nodeipdata['passwd']=='')
	{
		$SESSION->redirect('m=gponoltedit&action=addip&id='.$_GET['id']);
        }
	
	if($nodeipdata['name']=='')
		$error['ipname'] = trans('Address field is required!');
	elseif(strlen($nodeipdata['name']) > 32)
		$error['ipname'] = trans('Specified name is too long (max.$a characters)!','32');
	elseif($LMS->GetNodeIDByName($nodeipdata['name']))
		$error['ipname'] = trans('Specified name is in use!');
	elseif(!preg_match('/^[_a-z0-9-]+$/i', $nodeipdata['name']))
		$error['ipname'] = trans('Name contains forbidden characters!');

	if($nodeipdata['ipaddr']=='')
		$error['ipaddr'] = trans('IP address is required!');
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect IP address!');
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Specified address does not belongs to any network!');
	elseif(!$LMS->IsIPFree($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is in use!');
	
	if($nodeipdata['ipaddr_pub']!='0.0.0.0' && $nodeipdata['ipaddr_pub']!='')
	{
		if(!check_ip($nodeipdata['ipaddr_pub']))
	            	$error['ipaddr_pub'] = trans('Incorrect IP address!');
	    	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr_pub']))
	            	$error['ipaddr_pub'] = trans('Specified address does not belongs to any network!');
		elseif(!$LMS->IsIPFree($nodeipdata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is in use!');
	}
	else
		$nodeipdata['ipaddr_pub'] = '0.0.0.0';

	if (check_mac($nodeipdata['mac'])) {
		if ($nodeipdata['mac'] != '00:00:00:00:00:00' && !ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.allow_mac_sharing', false))
			&& ($nodeid = $LMS->GetNodeIDByMAC($nodeipdata['mac'])) != null && $nodeid != $_GET['ip'])
			$error['mac'] = trans('MAC address is in use!');
	} elseif ($value != '')
		$error['mac'] = trans('Incorrect MAC address!');

	if(strlen($nodeipdata['passwd']) > 32)
                $error['passwd'] = trans('Password is too long (max.32 characters)!');

	if(!isset($nodeipdata['chkmac'])) $nodeipdata['chkmac'] = 0;
	if(!isset($nodeipdata['halfduplex'])) $nodeipdata['halfduplex'] = 0;
	if(!isset($nodeipdata['nas'])) $nodeipdata['nas'] = 0;

	if(!$error)
	{
		$nodeipdata['warning'] = 0;
		$nodeipdata['location'] = '';
		$nodeipdata['netdev'] = $_GET['id'];
		$nodeipdata['macs'][0] = $nodeipdata['mac'];
		$nodeipdata['netid'] = $DB->GetOne('SELECT id FROM networks WHERE address = INET_ATON(?) & INET_ATON(mask) LIMIT 1',
			array($nodeipdata['ipaddr']));
		$nodeipdata['authtype'] = 0;

		$LMS->NodeAdd($nodeipdata);
		$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
	}
	
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='addip';
	break;
		
case 'formeditip':

	$subtitle = trans('IP address edit');
	$nodeipdata = $_POST['ipadd'];
	$nodeipdata['ownerid']=0;
	$nodeipdata['mac'] = str_replace('-',':',$nodeipdata['mac']);

	foreach($nodeipdata as $key => $value)
		$nodeipdata[$key] = trim($value);
	
	if($nodeipdata['ipaddr']=='' && $nodeipdata['mac']=='' && $nodeipdata['name']=='' && $nodeipdata['passwd']=='')
	{
		$SESSION->redirect('m=gponoltedit&action=editip&id='.$_GET['id'].'&ip='.$_GET['ip']);
        }
	
	if($nodeipdata['name']=='')
		$error['ipname'] = trans('Address field is required!');
	elseif(strlen($nodeipdata['name']) > 32)
		$error['ipname'] = trans('Specified name is too long (max.$a characters)!','32');
	elseif(
		$LMS->GetNodeIDByName($nodeipdata['name']) &&
		$LMS->GetNodeName($_GET['ip'])!=$nodeipdata['name']
		)
		$error['ipname'] = trans('Specified name is in use!');
	elseif(!preg_match('/^[_a-z0-9-]+$/i', $nodeipdata['name']))
		$error['ipname'] = trans('Name contains forbidden characters!');	

	if($nodeipdata['ipaddr']=='')
		$error['ipaddr'] = trans('IP address is required!');
	elseif(!check_ip($nodeipdata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect IP address!');
	elseif(!$LMS->IsIPValid($nodeipdata['ipaddr']))
		$error['ipaddr'] =  trans('Specified address does not belongs to any network!');
	elseif(
		!$LMS->IsIPFree($nodeipdata['ipaddr']) &&
		$LMS->GetNodeIPByID($_GET['ip'])!=$nodeipdata['ipaddr']
		)
		$error['ipaddr'] = trans('IP address is in use!');

	if($nodeipdata['ipaddr_pub']!='0.0.0.0' && $nodeipdata['ipaddr_pub']!='')
	{
		if(check_ip($nodeipdata['ipaddr_pub']))
		{
		        if($LMS->IsIPValid($nodeipdata['ipaddr_pub']))
		        {
		                $ip = $LMS->GetNodePubIPByID($nodeipdata['id']);
		                if($ip!=$nodeipdata['ipaddr_pub'] && !$LMS->IsIPFree($nodeipdata['ipaddr_pub']))
		                        $error['ipaddr_pub'] = trans('Specified IP address is in use!');
		        }
		        else
		                $error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
		}
		else
	    		$error['ipaddr_pub'] = trans('Incorrect IP address!');
	}
	else
		$nodeipdata['ipaddr_pub'] = '0.0.0.0';

	if (check_mac($nodeipdata['mac'])) {
		if ($nodeipdata['mac'] != '00:00:00:00:00:00' && !ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.allow_mac_sharing', false))
			&& $LMS->GetNodeIDByMAC($nodeipdata['mac']))
			$error['mac'] = trans('MAC address is in use!');
	} elseif ($value != '')
		$error['mac'] = trans('Incorrect MAC address!');

	if(strlen($nodeipdata['passwd']) > 32)
                $error['passwd'] = trans('Password is too long (max.32 characters)!');
		
	if(!isset($nodeipdata['chkmac'])) $nodeipdata['chkmac'] = 0;
	if(!isset($nodeipdata['halfduplex'])) $nodeipdata['halfduplex'] = 0;
	if(!isset($nodeipdata['nas'])) $nodeipdata['nas'] = 0;
	
	if(!$error)
	{
		$nodeipdata['warning'] = 0;
		$nodeipdata['location'] = '';
		$nodeipdata['netdev'] = $_GET['id'];
		$nodeipdata['macs'][0] = $nodeipdata['mac'];
		$nodeipdata['netid'] = $DB->GetOne('SELECT id FROM networks WHERE address = INET_ATON(?) & INET_ATON(mask) LIMIT 1',
			array($nodeipdata['ipaddr']));
		$nodeipdata['authtype'] = 0;

		$LMS->NodeUpdate($nodeipdata);
		$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
	}

	$nodeipdata['ip_pub'] = $nodeipdata['ipaddr_pub'];
	$SMARTY->assign('nodeipdata',$nodeipdata); 
	$edit='ip';
	break;

default:
	$edit = 'data';
	break;
}
if(isset($_GET['prof']) && strlen($_GET['prof'])>0)
{
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
	$GPON->snmp->set_options($options_snmp);
	$temp_post=$GPON->snmp->OLT_GetProfilesData($_GET['prof']);//var_dump($temp_post);
	$temp_post['profile_edit']=1;
	$SMARTY->assign('temp_post',$temp_post);
}
if(isset($_POST['snmpsend']) && $_POST['snmpsend'] == 1)
{	
	$GPON->snmp->clear_options();
	$netdevdata=$LMS->GetNetDev($_GET['id']);
	$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
	if($netdevdata['gponoltid']>0)
	{
		$GPON->snmp->set_options($options_snmp);
	
		$GPON->snmp->OLT_set_defaultServiceProfile($_POST['serviceProfile']);
		$GPON->snmp->OLT_set_radiususernametype($_POST['olt_radiususernametype']);

		if(check_ip($_POST['olt_radiusAddress']) && strlen(trim($_POST['olt_radiusKey'])) > 0)
		{
		    $GPON->snmp->OLT_add_radius($_POST['olt_radiusAddress'], $_POST['olt_radiusKey'], intval($_POST['olt_radiusPort']));
		}

		if(strlen(trim($_POST['new_autotime_ModelName'])) > 0 && $_POST['new_autotime_Start']>-1 && $_POST['new_autotime_Stop']>-1)
		{
		    $GPON->snmp->OLT_set_autoupgrade_time($_POST['new_autotime_ModelName'], $_POST['new_autotime_Start'], $_POST['new_autotime_Stop'], $_POST['new_autotime_Reboot']);
		}

		if(check_ip($_POST['new_autoupgrade_address']) && $_POST['new_autoupgrade_ModelName'] && $_POST['new_autoupgrade_FW'])
		{
		    $GPON->snmp->OLT_set_autoupgrade_model($_POST['new_autoupgrade_ModelName'], $_POST['new_autoupgrade_FW'],
					$_POST['new_autoupgrade_address'], $_POST['new_autoupgrade_user'], $_POST['new_autoupgrade_passwd'],
					$_POST['new_autoupgrade_version'], $_POST['new_autoupgrade_exclude']);
		}

		foreach($_POST as $k => $v)
		{
		    if(preg_match('/radiusid\_/', $k)) 
		    {
			preg_match('/radiusid\_(.+)/', $k, $match);
			$num = intval($match[1]);

			$GPON->snmp->OLT_del_radius($num);
		    }

		    if(preg_match('/aging\_/',$k))
		    {
			preg_match('/aging\_(.+)/', $k, $match);
			$port = intval($match[1]);

			$GPON->snmp->OLT_set_AgingTime($port, intval($v));
		    }

		    if(preg_match('/authmode\_/', $k))
		    {
			preg_match('/authmode\_(.+)/', $k, $match);
			$port = intval($match[1]);

			$GPON->snmp->OLT_set_AuthMode($port, intval($v));
		    }

		    if(preg_match('/modelProfile\_/', $k))
		    {
			preg_match('/modelProfile_(.+)/', $k, $match);
			$model = $match[1];

			$GPON->snmp->OLT_set_ModelServiceProfile($model, $v);
		    }

		    if(preg_match('/^autoupgrade\_/', $k))
		    {
			preg_match('/^autoupgrade\_(.+)/', $k, $match);
			$port = intval($match[1]);

			$GPON->snmp->OLT_set_FWAutoUpgrade($port, intval($v));
		    }

		    if(preg_match('/^autoupgrademodel\_/', $k))
		    {
			preg_match('/^autoupgrademodel\_(.+)/', $k, $match);
			$model = $match[1];

			$GPON->snmp->OLT_del_autoupgrade_model($model);
		    }

		    if(preg_match('/^autotime\_/', $k))
		    {
			preg_match('/^autotime\_(.+)/', $k, $match);
			$model = $match[1];

			$GPON->snmp->OLT_del_autoupgrade_time($model);
		    }

		}
		$dump = var_export($_POST, true);
		$GPON->Log(4, 'gponolt', $netdevdata['gponoltid'], 'SNMP set', $dump);
	}
}

if(isset($_POST['gponprofileadd']) && intval($_POST['gponprofileadd'])>0)
{
	//var_dump($_POST);
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	
	if($netdevdata['purchasetime'])
		$netdevdata['purchasedate'] = date('Y/m/d', $netdevdata['purchasetime']);
		
	$temp_post=$_POST;
	if($temp_post['profile_name'] == '')
		$error['profile_name'] = 'Nazwa profilu jest wymagana';
	elseif(strlen($temp_post['name']) > 63)
		$error['profile_name'] =  trans('Specified name is too long (max.$a characters)!','63');	
	for($s=1;$s<5;$s++)
	{
		/*if(strlen(trim($temp_post['downstream_'.$s])) == 0)
		$error['downstream_'.$s] = 'Downstream jest wymagany';
		else*/
		if($temp_post['downstream_'.$s]%64>0)
		$error['downstream_'.$s] =  'Downstream musi się dzielić przez 64';
		
		if(strlen(trim($temp_post['vlan_id_'.$s])) == 0)
		$error['vlan_id_'.$s] = 'VLAN ID jest wymagany';
		elseif($temp_post['vlan_id_'.$s]<1 || $temp_post['vlan_id_'.$s]>4096)
		$error['vlan_id_'.$s] =  'VLAN ID musi być z zakresu od 1 do 4096';
		
		if(strlen(trim($temp_post['cos_'.$s])) == 0)
		$error['cos_'.$s] = 'VLAN ID jest wymagany';
		elseif($temp_post['cos_'.$s]<0 || $temp_post['cos_'.$s]>7)
		$error['cos_'.$s] =  'COS musi być z zakresu od 0 do 7';
	}
	
	if(!$error)
	{
		$options_snmp=$GPON->GetGponOlt($netdevdata['gponoltid']);
		$GPON->snmp->set_options($options_snmp);
		if(intval($temp_post['profile_edit']) == 0)
		{
			$GPON->snmp->OLT_AddProfile($temp_post['profile_name'],$temp_post['trafficprofiles']);
			$GPON->AddGponOltProfiles($temp_post['profile_name']);
		}
		for($eth=1;$eth<5;$eth++)
		{
			$GPON->snmp->OLT_ModifyProfile($temp_post['profile_name'],$eth,$temp_post['downstream_'.$eth],$temp_post['vlan_id_'.$eth],$temp_post['cos_'.$eth],$temp_post['status_'.$eth]);
		}
		$temp_post=array();
	}
	$SMARTY->assign('temp_post',$temp_post);
	
}
elseif(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];
	$netdevdata['id'] = $_GET['id'];

	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif(strlen($netdevdata['name']) > 32)
		$error['name'] =  trans('Specified name is too long (max.$a characters)!','32');

	$netdevdata['ports'] = intval($netdevdata['ports']);
	
	if($netdevdata['ports'] < $LMS->CountNetDevLinks($_GET['id']))
		$error['ports'] = trans('Connected devices number exceeds number of ports!');

	if(empty($netdevdata['clients']))
                $netdevdata['clients'] = 0;
	else
	        $netdevdata['clients'] = intval($netdevdata['clients']);
						
	$netdevdata['purchasetime'] = 0;
	if($netdevdata['purchasedate'] != '')
	{
		// date format 'yyyy/mm/dd'
		if(!preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $netdevdata['purchasedate'])) 
		{
			$error['purchasedate'] = trans('Invalid date format!');
		}
		else
		{
			$date = explode('/', $netdevdata['purchasedate']);
			if(checkdate($date[1], $date[2], (int)$date[0]))
			{
				$tmpdate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
				if(mktime(0,0,0) < $tmpdate)
					$error['purchasedate'] = trans('Date from the future not allowed!');
				else
					$netdevdata['purchasetime'] = $tmpdate;
			}
			else
				$error['purchasedate'] = trans('Invalid date format!');
		}
	}

	if($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasedate'] == '')
	{
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}
	//---PORTKI
	if(intval($netdevdata['ports'])==0)
	{
		$error['ports'] = 'Podaj liczbę portów';
	}
	//----PORTKI
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

	if(!$error)
	{
		if($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;

		if(!isset($netdevdata['shortname'])) $netdevdata['shortname'] = '';
	        if(!isset($netdevdata['secret'])) $netdevdata['secret'] = '';
	        if(!isset($netdevdata['community'])) $netdevdata['community'] = '';
	        if(!isset($netdevdata['nastype'])) $netdevdata['nastype'] = 0;

		$LMS->NetDevUpdate($netdevdata);
		if ($_POST['dev2nagios'] && method_exists('LMS', 'SaveDev2Nagios'))
		    $LMS->SaveDev2Nagios($_POST['dev2nagios'],$_GET['id']);  

		//-GPON-OLT
		//Update OLT
		$GPON->GponOltUpdate($netdevdata);
		$gponoltportsdata=$_POST['gponoltports'];
		if($netdevdata['gponoltid']>0 && is_array($gponoltportsdata) && count($gponoltportsdata)>0)
		{
			foreach($gponoltportsdata as $k=>$v)
			{
				$gponoltports[$k]['gponoltid']=$netdevdata['gponoltid'];
				$gponoltports[$k]['numport']=$k;
				$gponoltports[$k]['maxonu']=$v;
			}
			$GPON->GponOltPortsUpdate($gponoltports);
		}
		//-GPON-OLT
		$SESSION->redirect('?m=gponoltinfo&id='.$_GET['id']);
	}
}
else 
{
	$netdevdata = $LMS->GetNetDev($_GET['id']);
	
	if($netdevdata['purchasetime'])
		$netdevdata['purchasedate'] = date('Y/m/d', $netdevdata['purchasetime']);
}


$netdevdata['id'] = $_GET['id'];

$netdevips = $LMS->GetNetDevIPs($_GET['id']);
$nodelist = $LMS->GetUnlinkedNodes();
$netdevconnected = $GPON->GetGponOnuConnectedNames($_GET['id']);
$netcomplist = $LMS->GetNetDevLinkedNodes($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOnu();

//-GPON-OLT
//Dane OLT
$gponoltdata=$GPON->GetGponOlt($netdevdata['gponoltid']);
$netdevdata=array_merge($gponoltdata,$netdevdata);
$gponoltportsdata=$GPON->GetGponOltPorts($netdevdata['gponoltid']);
//-GPON-OLT

unset($netdevlist['total']);
unset($netdevlist['order']);
unset($netdevlist['direction']);

$replacelist = $LMS->GetNetDevList();

$replacelisttotal = $replacelist['total'];
unset($replacelist['order']);
unset($replacelist['total']);
unset($replacelist['direction']);


/* Using AJAX plugins */
function OLT_ONU_walk_Xj($gponoltid)
{
	// xajax response
	global $GPON;
	$objResponse = new xajaxResponse();
	$options_snmp=$GPON->GetGponOlt($gponoltid);
	$GPON->snmp->set_options($options_snmp);
	$OLT_ONU=$GPON->snmp->OLT_ONU_walk_get_param();
	if(is_array($OLT_ONU) && count($OLT_ONU)>0)
	{
		foreach($OLT_ONU as $k=>$v)
		{
			if(is_array($v) && count($v)>0)
			{
				foreach($v as $k1=>$v1)
				{
					if($k=='RxPower')
					{
						$v1='<font color="'.$GPON->snmp->style_gpon_tx_output_power_weak($v1,0).'">'.$v1.'</font>';
					}
					$objResponse->assign($k."_ONU_".$k1,"innerHTML",$v1);
				}
			}
		}
	}
	$error_snmp=$GPON->snmp->get_correct_connect_snmp();
	$objResponse->assign("OLT_ONU_date","innerHTML",$error_snmp.'Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b>');
	return $objResponse;
}
function OLT_get_param_Xj($gponoltid,$id)
{
	// xajax response
	global $GPON;
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

//$SMARTY->assign('xajax', $xajax->getJavascript('img/', 'xajax.js'));
/* end AJAX plugin stuff */


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
$trafficprofiles=$GPON->snmp->OLT_GetTrafficProfiles();
$gponoltprofiles=$GPON->snmp->OLT_GetProfiles();
$snmpoltdata=$GPON->snmp->OLT_get_param_edit();
$SMARTY->assign('trafficprofiles',$trafficprofiles);
$SMARTY->assign('gponoltprofiles',$gponoltprofiles);
$SMARTY->assign('snmpoltdata',$snmpoltdata);
$SMARTY->assign('error_snmp',$error_snmp);

if(method_exists('LMS', 'GetDev2Nagios')) //nie wszyscy maja naszego nagiosa
{
    $dev2nagios=$LMS->GetDev2Nagios($_GET['id']);
    $SMARTY->assign("dev2nagios",$dev2nagios);
    $SMARTY->assign("nagiosON", 1);
}

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

switch($edit)
{
    case 'data':
	if (ConfigHelper::checkConfig('phpui.ewx_support'))
    		$SMARTY->assign('channels', $DB->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));
	
	$SMARTY->display('gponoltedit.html');
    break;
    case 'ip':
	$SMARTY->display('gponoltipedit.html');
    break;
    case 'addip':
	$SMARTY->display('gponoltipadd.html');
    break;
    default:
	$SMARTY->display('gponoltinfo.html');
    break;
}

?>
