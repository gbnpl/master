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

$onu_customerlimit = ConfigHelper::getConfig('gpon-dasan.onu_customerlimit', 1);
$onu_check_add=isset($_GET['onu_check_add'])?intval($_GET['onu_check_add']):0;
if(isset($_POST['onucheck']))
{
	$onu_check_add=1;
}
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
			
	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif(strlen($netdevdata['name'])>8)
		$error['name'] = 'Nazwa zbyt długa (powinna mieć dokładnie 8 znaków)';
	elseif(strlen($netdevdata['name'])<8)
		$error['name'] = 'Nazwa zbyt krótka (powinna mieć dokładnie 8 znaków)';
	elseif(!preg_match('/^[a-fA-F0-9]{8}$/D',$netdevdata['name']))
	$error['name'] = 'Nazwa musi składać się z 8 znaków i może zawierać jedynie cyfry od 0-9 i litery od a-f';
	else 
	{
		$netdevdata['name']='DSNW'.$netdevdata['name'];
	}
	
	if($GPON->GponOnuNameExists($netdevdata['name']))
	{
		$error['name'] = 'Nazwa musi być unikalna. Taka nazwa już istnieje.';
	}
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

	if($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasetime'] == NULL)
	{
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}
	if(isset($netdevdata['autoprovisioning']) && intval($netdevdata['autoprovisioning'])==1)
	{
		//customer
		if(is_array($netdevdata) && count($netdevdata)>0)
		{
			foreach($netdevdata as $k5=>$v5)
			{
				if(preg_match('/customersid_/',$k5))
				{
					$cust_list_num[]=intval(str_replace('customersid_','',$k5));
				}
			}
		}
		$customer_test=0;
		for($ii=0;$ii<intval(max($cust_list_num))+1;$ii++)	
		{
			if(intval($netdevdata['customersid_'.$ii])>0)
			{
				$customer_test=1;
				break;
			}
		}
		if($customer_test==0)
		{
			$error['customer_test'] = trans('Należy przypisać conajmniej do jednego klienta, jeżeli zaznaczono Wydany do klienta');
		}
		//profil
		if(!isset($netdevdata['gponoltprofilesid']) || intval($netdevdata['gponoltprofilesid'])==0)
		{
			$error['gponoltprofiles'] = trans('Wybierz profil, jeżeli zaznaczono Wydany do klienta');
		}
	}
/*	if($onu_check_add==1)
	{
		if(!isset($netdevdata['gponoltprofilesid']) || intval($netdevdata['gponoltprofilesid'])==0)
		{
			$error['gponoltprofiles'] = trans('Wybierz profil');
		}
	} */
        if(!$error)
        {
			if($netdevdata['guaranteeperiod'] == -1)
				$netdevdata['guaranteeperiod'] = NULL;
				

			$netdevid = $GPON->GponOnuAdd($netdevdata);
			if(is_array($netdevdata) && count($netdevdata)>0)
			{
				foreach($netdevdata as $k5=>$v5)
				{
					if(preg_match('/customersid_/',$k5))
					{
						$cust_list_num[]=intval(str_replace('customersid_','',$k5));
					}
				}
			}
			for($ii=0;$ii<intval(max($cust_list_num))+1;$ii++)	
			{
				$GPON->GponOnuAddCustomer($netdevid,$netdevdata['customersid_'.$ii]);
			}
			if($onu_check_add==1 && $netdevid>0 && isset($_POST['netdevicesid']) && $_POST['netdevicesid']>0 && isset($_POST['olt_port']) && $_POST['olt_port']>0 && isset($_POST['onu_id']) && $_POST['onu_id']>0)
			{
				$GPON->GponOnuUpdateOnuId($netdevid,$_POST['onu_id']);
				$GPON->GponOnuLink($_POST['netdevicesid'],$_POST['olt_port'],$netdevid);
				//if(isset($_POST['onu_description_old']) && $netdevdata['onu_description']!=$_POST['onu_description_old'])
				//{
					$options_snmp=$GPON->GetGponOlt($_POST['gponoltid']);
					$GPON->snmp->set_options($options_snmp);
					$GPON->snmp->ONU_set_description($_POST['olt_port'],$_POST['onu_id'],$netdevdata['onu_description']);
					$gponoltprofiles_temp=$GPON->FlatArrayFromDB($GPON->GetGponOltProfiles(),'id','name');
					$GPON->snmp->ONU_SetProfile($_POST['olt_port'],$_POST['onu_id'],$gponoltprofiles_temp[$netdevdata['gponoltprofilesid']]);
				//}
				$SESSION->redirect('?m=gpononucheck&id='.$_POST['netdevicesid']);
			}
			else 
			{
				$SESSION->redirect('?m=gpononuinfo&id='.$netdevid);
			}

        }
	
	$SMARTY->assign('error', $error);
}


/* Using AJAX plugins */

function ONU_Voip_Phone_Xj($id_clients,$pot1_id,$pot2_id)
{
	// xajax response
	global $GPON;
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
	global $GPON;
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
$LMS->RegisterXajaxFunction(array('ONU_Voip_Phone_Xj', 'ONU_Host_hosts_Xj'));
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$gponoltprofiles=$GPON->FlatArrayFromDB($GPON->GetGponOltProfiles(),'id','name');
$SMARTY->assign('gponoltprofiles',$gponoltprofiles);


$layout['pagetitle'] = trans('New Device').': GPON-ONU';
$SMARTY->assign('onu_customerlimit',$onu_customerlimit);
$gpononumodels=$GPON->FlatArrayFromDB($GPON->GetGponOnuModelsList(),'id','name');
$SMARTY->assign('onu_check_add',$onu_check_add);
$SMARTY->assign('gpononumodels',$gpononumodels);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
if($onu_check_add==1)
{
	if(isset($_POST['onucheck']) && is_array($_POST['onucheck']) && count($_POST['onucheck'])>0)
	{
		foreach($_POST['onucheck'] as $k=>$v)
		{
			$netdev[$k]=$v;
			$onucheck[$k]=$v;
		}
	}
	$netdev['olt_data']='<A href="?m=gponoltinfo&id='.$_POST['onucheck']['netdevicesid'].'">'.$_POST['onucheck']['olt_name'].'</A>  Port: <b>'.$_POST['onucheck']['olt_port'].'</b>';
	$netdev['name']=str_replace('DSNW','',$_POST['onucheck']['onu_serial']);
	$netdev['onu_description_old']=$_POST['onucheck']['onu_description'];
	
	$netdev['onu_passwordResult']=$netdev['onu_password'];
	if($_POST['onucheck']['onu_passwordMode']=='enable(1)' || strlen($_POST['onucheck']['onu_passwordMode'])==0)
	{
		$netdev['onu_password']='';
		$netdev['onu_passwordResult']='auto-learning';
	}
	$SMARTY->assign('netdevicesid', $_GET['netdevicesid']);
	
}
$netdev_temp=is_array($netdev)?$netdev:array();
if(isset($_POST['netdev']))
{
	$netdev_temp=array_merge($netdevdata,$netdev_temp);
	$netdev_temp['name']=isset($netdev['name'])?$netdev['name']:$netdev_temp['name'];
}

$netdev_temp['name']=str_replace('DSNW','',$netdev_temp['name']);
$SMARTY->assign('netdev', $netdev_temp);
$SMARTY->assign('netdevhosts', $GPON->GetHostForNetdevices());
$SMARTY->assign('onucheck', $onucheck);
$SMARTY->display('gpononuadd.html');

?>
