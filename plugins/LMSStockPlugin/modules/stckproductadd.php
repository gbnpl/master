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

$layout['pagetitle'] = trans('Add product');
$error = NULL;

$productadd = array();
$productadd['quantitycheck'] = 1;

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['productadd'])) {
	$productadd = $_POST['productadd'];

	if ($productadd['name'] == '')
		$error['name'] = trans('Product must have a name!');
	
	if (!$error) {
		if ($id = $LMSST->ProductAdd($productadd)) {
			$SMARTY->assign('success', 1);
			if(!isset($productadd['reuse']) && !$layout['popup']) {
				$SESSION->redirect('?m=stckproductinfo&id='.$id);
			} 
		} else {
			$error['name'] = trans('Product already exists in database!');
		}

	}
}

$manufacturers =  $LMSST->ManufacturerGetList();
$groups = $LMSST->GroupGetList();
$quantities = $LMSST->QuantityGetList();
$types = $LMSST->TypeGetList();

unset($manufacturers['order']);
unset($manufacturers['direction']);
unset($manufacturers['total']);
unset($groups['order']);
unset($groups['direction']);
unset($groups['total']);
unset($quantities['order']);
unset($quantities['direction']);
unset($quantities['total']);
unset($types['order']);
unset($types['direction']);
unset($types['total']);

$SMARTY->assign('error', $error);
$SMARTY->assign('manufacturerslist', $manufacturers);
$SMARTY->assign('groupslist', $groups);
$SMARTY->assign('quantitieslist', $quantities);
$SMARTY->assign('typeslist', $types);
$SMARTY->assign('taxeslist',$LMS->GetTaxes());
$SMARTY->assign('productadd', $productadd);
$SMARTY->display('stckproductadd.html');

?>
