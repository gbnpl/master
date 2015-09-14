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

$layout['pagetitle'] = trans('Add product group');
$error = NULL;

$groupadd = array();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['groupadd'])) {
	$groupadd = $_POST['groupadd'];
	
	if ($groupadd['name'] == '')
		$error['name'] = trans('Product group must have a name!');
	
	if(!isset($groupadd['taxid']))
		$groupadd['taxid'] = 0;
	
	if (!$error) {
		if ($id = $LMSST->GroupAdd($groupadd)) {
			if(!isset($groupadd['reuse'])) {
				$SESSION->redirect('?m=stckgroupinfo&id='.$id);
			}
		} else {
			$error['name'] = trans('Group with this name already exists!');
		}

	}
}
$groupadd['quantitycheck'] = 1;
$quantities = $LMSST->QuantityGetList();

unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);

$SMARTY->assign('error', $error);
$SMARTY->assign('quantitieslist', $quantities);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('groupadd', $groupadd);
$SMARTY->display('stckgroupadd.html');

?>
