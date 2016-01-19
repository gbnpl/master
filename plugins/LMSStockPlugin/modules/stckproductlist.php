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

$layout['pagetitle'] = trans('Product list');

if(!isset($_GET['o']))
	$SESSION->restore('sglo', $o);
else
	$o = $_GET['o'];
$order = $o;
$SESSION->save('sglo', $o);

//$productlist = $LMSST->ProductGetList($o);

               list($order,$direction) = sscanf($order, '%[^,],%s');
               ($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
               switch($order) {
                       case 'id':
                               $sqlord = ' ORDER BY p.id';
                               break;
                       case 'name':
                               $sqlord = ' ORDER BY p.name';
                               break;
                       default:
                               $sqlord = ' ORDER BY p.name';
                               break;
               }

$productlist = $DB->GetAll('SELECT p.id AS gid, p.name AS gname, p.comment AS gcomment, 
	COALESCE(SUM(s.pricebuynet), 0) as valuenet,  COALESCE(SUM(s.pricebuygross), 0) as valuegross, COUNT(s.id) as count
        FROM stck_products p
	LEFT JOIN stck_stock s ON s.productid = p.id
        WHERE p.deleted = 0 AND s.pricesell IS NULL
        GROUP BY p.id, p.name, p.comment'
        .($sqlord != '' ? $sqlord.' '.$direction : ''
        ));

$productlist['total'] = sizeof($productlist);
$productlist['order'] = $order;
$productlist['direction'] = $direction;

$listdata['total'] = $productlist['total'];
$listdata['direction'] = $productlist['direction'];
$listdata['order'] = $productlist['order'];
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);

if(!isset($_GET['page']))
        $SESSION->restore('sglp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('stock.productlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('swlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('productlist', $productlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('stckproductlist.html');

?>
