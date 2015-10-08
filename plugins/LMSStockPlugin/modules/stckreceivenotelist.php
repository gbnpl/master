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

$layout['pagetitle'] = trans('Receive notes list');

if(!isset($_GET['o']))
	$SESSION->restore('srnlo', $o);
else
	$o = $_GET['o'];

$SESSION->save('srnlo', $o);

if(!isset($_GET['sprn']))
	$SESSION->restore('srnlsp', $sprn);
else
	$sprn = $_GET['sprn'];

$SESSION->save('srnlsp', $sprn);

switch ($_GET['action']) {
	case 'srna':
		foreach ($_POST['marks'] as $k => $v) {
			$LMSST->ReceiveNoteAccount($k);
			//print_r($rn);
		}
		break;
	default:
		break;
}

$receivenotelist = $LMSST->ReceiveNoteList($o, $sprn);
$listdata['total'] = $receivenotelist['total'];
$listdata['direction'] = $receivenotelist['direction'];
$listdata['order'] = $receivenotelist['order'];
$listdata['totalvu'] = $receivenotelist['totalvu'];
unset($receivenotelist['totalvu']);
unset($receivenotelist['total']);
unset($receivenotelist['direction']);
unset($receivenotelist['order']);

if (!empty($receivenotelist))
	foreach ($receivenotelist as $k => $v)
		$receivenotelist[$k]['sbalance'] = $LMS->GetCustomerBalance($v['sid']);

if(!isset($_GET['page']))
        $SESSION->restore('srnlp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('stock.receivenotelist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('srnlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('sprn', $sprn);
$SMARTY->assign('receivenotelist', $receivenotelist);
$SMARTY->display('stckreceivenotelist.html');

?>
