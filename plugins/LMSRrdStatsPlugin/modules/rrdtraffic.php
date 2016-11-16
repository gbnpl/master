<?php

/* LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$layout['pagetitle'] = trans('Network Statistics');

$bars = 1;

if (isset($_GET['bar'])) {
	if (isset($_POST['order']))
		$SESSION->save('trafficorder', $_POST['order']);
	if (isset($_POST['net']))
		$SESSION->save('trafficnet', $_POST['net']);
}

$bar = isset($_GET['bar']) ? $_GET['bar'] : '';

switch ($bar) {
	case 'hour':
		$traffic = RRDStats::Traffic(array(
			'from' => time() - (60 * 60),
			'to' => time(),
			'net' => $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
			'order' => $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download',
		));
		break;

	case 'day':
		$traffic = RRDStats::Traffic(array(
			'from' => time() - (60 * 60 * 24),
			'to' => time(),
			'net' => $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
			'order' => $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download',
		));
		break;

	case 'month':
		$traffic = RRDStats::Traffic(array(
			'from' => time() - (60 * 60 * 24 * 30),
			'to' => time(),
			'net' => $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
			'order' => $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download',
		));
		break;

	case 'year':
		$traffic = RRDStats::Traffic(array(
			'from' => time() - (60 * 60 * 24 * 365),
			'to' => time(),
			'net' => $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0,
			'order' => $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download',
		));
		break;

	case 'user':
		$from = !empty($_POST['from']) ? $_POST['from'] : time() - (60 * 60 * 24);
		$to = !empty($_POST['to']) ? $_POST['to'] : time();
		$net = !empty($_POST['net']) ? $_POST['net'] : 0;

		if (is_array($from))
			$from = mktime($from['Hour'], $from['Minute'], 0, $from['Month'], $from['Day'], $from['Year']);
		if (is_array($to))
			$to = mktime($to['Hour'], $to['Minute'], 0, $to['Month'], $to['Day'], $to['Year']);

		$SMARTY->assign('datefrom', $from);
		$SMARTY->assign('dateto', $to);
		$SMARTY->assign('net', $net);

		$traffic = RRDStats::Traffic(array(
			'from' => $from,
			'to' => $to,
			'net' => $net,
			'order' => isset($_POST['order']) ? $_POST['order'] : '',
		));
		break;

	default:
		$SMARTY->assign('netlist', $LMS->GetNetworks());
		$bars = 0;
}

if (isset($traffic)) {
	$SMARTY->assign('download', $traffic['download']);
	$SMARTY->assign('upload', $traffic['upload']);
}

$starttime = time();
$endtime = time();
$startyear = date('Y', $starttime);
$endyear = date('Y', $endtime);

$SMARTY->assign('starttime',$starttime);
$SMARTY->assign('startyear',$startyear);
$SMARTY->assign('endtime',$endtime);
$SMARTY->assign('endyear',$endyear);
$SMARTY->assign('showips', isset($_POST['showips']));
$SMARTY->assign('bars', $bars);
$SMARTY->assign('bar', $bar);
$SMARTY->assign('trafficorder', $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
$SMARTY->assign('trafficnet', $SESSION->is_set('trafficnet') ? $SESSION->get('trafficnet') : 0);
$SMARTY->display('rrdtraffic.html');

?>
