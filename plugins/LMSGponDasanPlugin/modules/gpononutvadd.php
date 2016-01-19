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

if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];
	$netdevdata['ipaddr']=trim($netdevdata['ipaddr']);
if(strlen($netdevdata['ipaddr'])==0)
		$error['ipaddr'] = trans('Node IP address is required!');
	elseif(!check_ip($netdevdata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect node IP address!');
	elseif (!$GPON->IsGponOnuTvMulticast($netdevdata['ipaddr']))
		$error['ipaddr'] = 'Wpisany adres nie jest typu multicast';
	elseif ($GPON->GponOnuTvIpExists($netdevdata['ipaddr']))
		$error['ipaddr'] = 'Ten adres jest już używany przez inny kanał TV';
	
	$netdevdata['channel'] = trim($netdevdata['channel']);
	if (!strlen($netdevdata['channel']))
		$error['channel'] = 'Podaj nazwę kanału TV';

        if(!$error)
        {
			$netdevid = $GPON->GponOnuTvAdd($netdevdata);
			$SESSION->redirect('?m=gpononutvinfo&id='.$netdevid);
        }
	
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdev', $netdevdata);
	
}
$layout['pagetitle'] = 'GPON-ONU-TV';
$SMARTY->display('gpononutvadd.html');

?>
