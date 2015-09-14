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

$layout['pagetitle'] = trans('Add manufacturer');
$error = NULL;

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$manufactureradd = array();

if (isset($_POST['manufactureradd'])) {
	$manufactureradd = $_POST['manufactureradd'];

	if ($manufactureradd['name'] == '')
		$error['name'] = trans('Manufacturer must have a name!');
	
	if (!$error) {
		if ($id = $LMSST->ManufacturerAdd($manufactureradd)) {
			if(!isset($manufactureradd['reuse']) && !$layout['popup']) {
				$SESSION->redirect('?m=stckmanufacturerinfo&id='.$id);
			} elseif ($layout['popup']) {
				$SMARTY->assign('success', 1);
				$SMARTY->assign('reload', 1);
			}
		} else {
			$error['name'] = trans('Manufacturer already exists in database!');
		}

	}
}

$SMARTY->assign('error', $error);
$SMARTY->assign('manufactureradd', $manufactureradd);
$SMARTY->display('stckmanufactureradd.html');

?>
