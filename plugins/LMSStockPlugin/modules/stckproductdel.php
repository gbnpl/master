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

if (!$LMSST->ProductExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Deletion of product with ID: $a', sprintf('%04d', $_GET['id']));
$SMARTY->assign('productid', $_GET['id']);

if ($LMSST->ProductStockCount($_GET['id']))
	$body = '<P>'.trans('Product on stock can\'t be deleted.').'</P>';
elseif ($_GET['is_sure'] != 1) {
	$body = '<P>'.trans('Are you sure, you want to delete this product?').'</P>'; 
	$body .= '<P><A HREF="?m=stckproductdel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';
} else {
	$LMSST->ProductDel($_GET['id']);
	$SESSION->redirect('?m=stckproductlist');
	//$body = '<P>'.trans('Manufacturer has been deleted.').'</P>';
}

$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
