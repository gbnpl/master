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

if(! $GPON->GponOnuExists($_GET['id']))
{
	$SESSION->redirect('?m=gpononulist');
}
$netdevinfo = $GPON->GetGponOnu($_GET['id']);


$netdevconnected = $GPON->GetGponOltConnectedNames($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOlt();
if(is_array($netdevlist) && count($netdevlist)>0)
{
	$numports=$GPON->GetFreeOltPort($netdevlist[0]['id']);
}

/* Using AJAX plugins */
function GetFreeOltPort_Xj($netdevicesid)
{
	// xajax response
	global $GPON;
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
	global $GPON;
	$objResponse = new xajaxResponse();
	$options_snmp=$GPON->GetGponOlt($gponoltid);
	$GPON->snmp->set_options($options_snmp);
	$error_snmp=$GPON->snmp->get_correct_connect_snmp();
	$table_param=$GPON->snmp->ONU_get_param_table($OLT_id,$ONU_id,$ONU_name);
	$objResponse->script("document.getElementById('pokaz_parametry_".$id."').value='Ukryj parametry';"); 
	$objResponse->script("document.getElementById('odswiez_parametry_".$id."').style.display='block';"); 
	$objResponse->script("document.getElementById('pokaz_parametry_".$id."').onclick=function(){document.getElementById('ONU_param_".$id."').innerHTML='';document.getElementById('pokaz_parametry_".$id."').value='Pokaż parametry';document.getElementById('odswiez_parametry_".$id."').style.display='none';document.getElementById('pokaz_parametry_".$id."').onclick=function(){xajax_ONU_get_param_Xj(".$gponoltid.",".$OLT_id.",".$ONU_id.",".$id.",'".$ONU_name."');}};");
	$objResponse->assign("ONU_param_".$id,"innerHTML",$error_snmp.$table_param);
	return $objResponse;
}
$LMS->InitXajax();                                                           
$LMS->RegisterXajaxFunction(array('GetFreeOltPort_Xj','ONU_get_param_Xj'));  
$SMARTY->assign('xajax', $LMS->RunXajax());                                  

/* end AJAX plugin stuff */

$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);

$nodelist = $LMS->GetUnlinkedNodes();
$netdevips = $LMS->GetNetDevIPs($_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = 'GPON-ONU: '.trans('$a ($b/$c)', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

$netdevinfo['id'] = $_GET['id'];
$netdevinfo['rrd'] = $_GET['rrd'];

$gpononu2customers=$GPON->GetGponOnu2Customers($_GET['id']);
/*
//tak nie działa na wielu klientów - trzeba bardziej inwazyjnie - a miało być jak najmniej inwazyjnie
if(count($gpononu2customers)>0)
{
	foreach($gpononu2customers as $k=>$v)
	{
		$customerid = $v['customersid'];
		include(MODULES_DIR.'/customer.inc.php');
	}
}
$SMARTY->assign('gpononu2customerscount', count($gpononu2customers));
*/

$onuports = $GPON->GetGponOnuPorts($_GET['id'], 1);
if(sizeof($onuports) > 0)
{
	$SMARTY->assign('onuports', $onuports);
}
$onulastauth = $GPON->GetGponOnuLastAuth($_GET['id']);
if(sizeof($onulastauth) > 0)
{
	$SMARTY->assign('onulastauth', $onulastauth);
}
$SMARTY->assign('gpononu2customerscount', 0);
$SMARTY->assign('gpononu2customers', $gpononu2customers);

$SMARTY->assign('netdevinfo',$netdevinfo);
$SMARTY->assign('numports',$numports);

$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('devlinktype',$SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype',$SESSION->get('nodelinktype'));

$SMARTY->assign('portstype',$portstype);
$SMARTY->assign('customername', $LMS->GetCustomerName($netdevinfo['ownerid']));

if(isset($_GET['ip']))
{
	$SMARTY->assign('nodeipdata',$LMS->GetNode($_GET['ip']));
	$SMARTY->display('gpononuipinfo.html');
}
else
{
	$SMARTY->display('gpononuinfo.html');
}

?>
