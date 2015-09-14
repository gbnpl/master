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

$action = isset($_GET['action']) ? $_GET['action'] : '';
$exists = $LMSST->WarehouseExists($_GET['id']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckwarehouselist');
elseif ($exists < 0 && $action != 'recover')
	$SESSION->redirect('?m=stckwarehouselist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit warehouse');
$error = NULL;

if (isset($_POST['warehouseedit'])) {
	$warehouseedit = $_POST['warehouseedit'];
	$warehouseedit['id'] = $_GET['id'];
//print_r($warehouseedit);
	if ($warehouseedit['name'] == '')
		$error['name'] = trans('Warehouse must have a name!');
	
	if (!$error) {
		$id = $LMSST->WarehouseEdit($warehouseedit);
		$SESSION->redirect('?m=stckwarehouseinfo&id='.$id);
	}
} else {
	$warehouseedit = $LMSST->WarehouseGetInfoById($_GET['id']);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('warehouseedit', $warehouseedit);
$SMARTY->assign('recover',($action == 'recover' ? 1 : 0));
$SMARTY->display('stckwarehouseedit.html');

?>
