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

$layout['pagetitle'] = trans('Manufacturers');

if(!isset($_GET['o']))
	$SESSION->restore('smlo', $o);
else
	$o = $_GET['o'];

$SESSION->save('smlo', $o);

$manufacturerlist = $LMSST->ManufacturerGetList($o);
$listdata['total'] = $manufacturerlist['total'];
$listdata['direction'] = $manufacturerlist['direction'];
$listdata['order'] = $manufacturerlist['order'];
unset($manufacturerlist['total']);
unset($manufacturerlist['direction']);
unset($manufacturerlist['order']);

if(!isset($_GET['page']))
        $SESSION->restore('smlp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('stock.manufacturerlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('swlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('manufacturerlist', $manufacturerlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('stckmanufacturerlist.html');

?>
