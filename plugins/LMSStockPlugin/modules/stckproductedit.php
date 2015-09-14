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
	$SESSION->redirect('?m=stckproductlist');
elseif (! $LMSST->ProductExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit product');
$error = NULL;

if (isset($_POST['productedit'])) {
	$productedit = $_POST['productedit'];
	$productedit['id'] = $_GET['id'];
	
	if ($productedit['name'] == '')
		$error['name'] = trans('Product must have a name!');
	
	if ($productedit['quantity'] < 1 || !ctype_digit($productedit['quantity']))
		$error['quantity'] = trans('Incorrect product quantity!');
	
	if (!$productedit['quantitycheck'])
		$productedit['quantitycheck'] = 0;
	
	if (!$error) {
		$id = $LMSST->ProductEdit($productedit);
		$SESSION->redirect('?m=stckproductinfo&id='.$id);
	}
} else {
	$productedit = $LMSST->ProductGetInfoById($_GET['id']);
}
$mlist = $LMSST->ManufacturerGetList();
$glist = $LMSST->GroupGetList();
$tlist = $LMSST->TypeGetList();
$qlist = $LMSST->QuantityGetList();

unset($mlist['total']);
unset($mlist['order']);
unset($mlist['direction']);
unset($glist['total']);
unset($glist['order']);
unset($glist['direction']);
unset($tlist['total']);
unset($tlist['order']);
unset($tlist['direction']);
unset($qlist['total']);
unset($qlist['order']);
unset($qlist['direction']);

$SMARTY->assign('error', $error);
$SMARTY->assign('productedit', $productedit);
$SMARTY->assign('mlist', $mlist);
$SMARTY->assign('glist', $glist);
$SMARTY->assign('tlist', $tlist);
$SMARTY->assign('qlist', $qlist);
$SMARTY->assign('txlist', $LMS->GetTaxes());
$SMARTY->display('stckproductedit.html');

?>
