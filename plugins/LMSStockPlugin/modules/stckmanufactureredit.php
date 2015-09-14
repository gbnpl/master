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

if (!isset($_GET['id']) || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckmanufacturerlist');
elseif (! $LMSST->ManufacturerExists($_GET['id']))
	$SESSION->redirect('?m=stckmanufacturerlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit manufacturer');
$error = NULL;

if (isset($_POST['manufactureredit'])) {
	$manufactureredit = $_POST['manufactureredit'];
	$manufactureredit['id'] = $_GET['id'];

	if ($manufactureredit['name'] == '')
		$error['name'] = trans('Manufacturer must have a name!');
	
    if (!$error) {
		$id = $LMSST->ManufacturerEdit($manufactureredit);

		$SESSION->redirect('?m=stckmanufacturerinfo&id='.$id);
	}
} else {
	$manufactureredit = $LMSST->ManufacturerGetInfoById($_GET['id']);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('manufactureredit', $manufactureredit);
$SMARTY->display('stckmanufactureredit.html');

?>
