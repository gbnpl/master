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

include(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name .
	DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'gponoffline.inc.php');

if(! $LMS->NetDevExists($_GET['id']))
{
	$SESSION->redirect('?m=gponoltlist');
}		

$layout['pagetitle'] = 'GPON-OLT-'.trans('Deletion of Device with ID: $a',sprintf('%04d',$_GET['id']));
$SMARTY->assign('netdevid',$_GET['id']);

if(count($GPON->GetGponOnuConnectedNames($_GET['id']))>0)
{
	$body = '<P>Do OLT podłączone są ONU. Nie można usunąć OLT.</P>';
}else{
    if($_GET['is_sure']!=1)
    {
	    $body = '<P>'.trans('Are you sure, you want to delete that device?').'</P>'; 
	    $body .= '<P><A HREF="?m=gponoltlist&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';
    }else{
	    header('Location: ?m=gponoltlist');
	    $body = '<P>'.trans('Device has been deleted.').'</P>';
	    $GPON->DeleteGponOlt($_GET['id']);
	    $LMS->DeleteNetDev($_GET['id']);
	   
    }
}
	
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
