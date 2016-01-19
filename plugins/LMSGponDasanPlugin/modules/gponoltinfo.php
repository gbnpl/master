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

$netdevinfo = $LMS->GetNetDev($_GET['id']);
//-GPON-OLT
//Dane OLT
if($netdevinfo['gponoltid'] > 0)
{
	$gponoltdata=$GPON->GetGponOlt($netdevinfo['gponoltid']);
	$netdevinfo=array_merge($gponoltdata,$netdevinfo);
	$gponoltportsdata=$GPON->GetGponOltPorts($netdevinfo['gponoltid']);
}
else
{
	$SESSION->redirect('?m=netdevinfo&id='.$_GET['id']);
}
//-GPON-OLT

if(!isset($_GET['o']))
        $SESSION->restore('goltio', $o);
else
        $o = $_GET['o'];
$SESSION->save('goltio', $o);

if(!isset($_GET['f']))
        $SESSION->restore('goltif', $f);
else
        $f = $_GET['f'];
$SESSION->save('goltif', $f);

$netdevconnected = $GPON->GetGponOnuConnectedNames($_GET['id'], $o, $f);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);
$netdevlist = $GPON->GetNotConnectedOnu();

$nodelist = $LMS->GetUnlinkedNodes();
$netdevips = $LMS->GetNetDevIPs($_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = 'GPON - OLT -'.trans('Device Info: $a $b $c', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

$netdevinfo['id'] = $_GET['id'];

list($order,$d) = sscanf($o, '%[^,],%s');
($d=='desc') ? $d = 'desc' : $d = 'asc';

$listdata['order'] = $order;
$listdata['direction'] = $d;
$listdata['filtr'] = $f;

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

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('OLT_ONU_walk_Xj');
$SMARTY->assign('xajax', $LMS->RunXajax());

/* end AJAX plugin stuff */

$GPON->snmp->set_options($gponoltdata);
$error_snmp=$GPON->snmp->get_correct_connect_snmp();
$table_OLT_param=$GPON->snmp->OLT_get_param_table($netdevinfo['gponoltid']);
$SMARTY->assign('table_OLT_param',$error_snmp.$table_OLT_param);

//nie wszyscy maja wezly
if($DB->GetOne("SELECT count(*) FROM information_schema.tables WHERE table_name = 'netdevnodes'") > 0)
{
    $q="SELECT * from netdevnodes where id=?";
    $lok=$DB->GetAll($q,array($netdevinfo['netdevnodeid']));
    $netdevinfo['lokalizacja']=$lok[0];
}

$SMARTY->assign('netdevinfo',$netdevinfo);
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

if(method_exists('LMS','GetDev2Nagios')) //nie wszyscy maja nagiosa
{
    $dev2nagios=$LMS->GetDev2Nagios($_GET['id']);
    $dev2nagiosAll=$LMS->GetDev2NagiosAll($_GET['id'],'nagios');
    $nagios_types=$LMS->GetNagiosTypes($dev2nagiosAll['id']);
    if (is_array($nagios_types))
	foreach($nagios_types as $k=>$v)
	{
	    $nagios_types[$k]['nmon']=$LMS->GetNagiosMon($nagios_types[$k]['id']);
	    $nagios_types[$k]['npar']=$LMS->GetNagiosParam($nagios_types[$k]['id']);
	}
    $SMARTY->assign('nagios_ifaces',$nagios_ifaces);
    $SMARTY->assign('nagios_types',$nagios_types);
    $SMARTY->assign('nagios_mon',$nagios_mon);
    $SMARTY->assign('dev2nagios',$dev2nagios);
    $SMARTY->assign('dev2nagiosAll',$dev2nagiosAll);
    $SMARTY->assign("nagiosON", 1); // tell template nagios is avaliable
}
$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('devlinktype',$SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype',$SESSION->get('nodelinktype'));
$SMARTY->assign('listdata',$listdata);

if(isset($_GET['ip']))
{
	$nodeipdata = $LMS->GetNode($_GET['ip']);
	$nodeipdata['mac'] = $nodeipdata['macs'][0]['mac'];
	if (isset($nodeipdata['macs'][0]['producer']))
		$nodeipdata['producer'] = $nodeipdata['macs'][0]['producer'];
	$SMARTY->assign('nodeipdata', $nodeipdata);
	$SMARTY->display('gponoltipinfo.html');
}
else
{
	$SMARTY->display('gponoltinfo.html');
}

?>
