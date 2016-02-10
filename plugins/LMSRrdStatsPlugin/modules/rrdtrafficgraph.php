<?php

/*
 *  LMS version 1.11-git
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

define('GRAPH_HEIGHT', 320);
define('GRAPH_WIDTH', 750);

function RRDGraph($nodeid, $type, $from, $to) {
	$db = LMSDB::getInstance();

	if (intval($nodeid)) {
		$ip = $db->GetOne('SELECT INET_NTOA(ipaddr)
			FROM nodes WHERE id = ?', array($nodeid));
		if (!$ip)
			die;
	}

	$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . $nodeid . '.rrd';
	if (!file_exists($rrd_file))
		die;

	switch ($type) {
		case 'traffic':
		case 'auto':
			$title = ($type == 'auto' ? $ip . ' - ' : '') . trans('<!rrdstats>Traffic');
			$lower_limit = 0;
			//$upper_limit = 1000;
			$vertical_label = 'bit/s';
			//$comment = "         Aktualnie    Minimalnie      Srednio      Maksymalnie\n";
			$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq',
				intval(ConfigHelper::getConfig('phpui.stat_freq', 12))
			));
			$cdefs = array(
				'downbits=down,' . str_replace(',', '.', 8.0 / $stat_freq) . ',*',
				'upbits=up,' . str_replace(',', '.', 8.0 / $stat_freq) . ',*',
			);
			$data = array(
				array(
					'ds' => 'down',
					'dscdef' => 'downbits',
					'color' => '00CF00FF',
					'label' => 'Download',
					'type' => 'AREA',
				),
				array(
					'ds' => 'up',
					'dscdef' => 'upbits',
					'color' => '002A97FF',
					'label' => 'Upload  ',
					'type' => 'LINE1',
				),
			);
			break;
		case 'online':
			$title = trans('Online computers');
			$lower_limit = 0;
			$vertical_label = trans('computers');
			$cdefs = array();
			$data = array(
				array(
					'ds' => 'online',
					'dscdef' => 'online',
					'color' => '0000FF',
					'label' => trans('Online computers'),
					'type' => 'AREA',
				),
			);
			break;
	}

	$title .= ': ' . strftime('%Y/%m/%d %H:%M', $from) . ' - ' . strftime('%Y/%m/%d %H:%M', $to);

	//	--rigid \
	//--start=-86400
	$cmd = RRDTOOL_BINARY . " graph - --imgformat PNG --start $from --end $to --title '$title' "
		. "--base 1024 --height 240 --width 750 "
		. (isset($lower_limit) ? "--lower-limit $lower_limit " : '')
		. (isset($upper_limit) ? "--upper-limit $upper_limit " : '')
		. "--vertical-label '$vertical_label' --slope-mode --font TITLE:10: --font AXIS:7: "
		. "--font LEGEND:8: --font UNIT:8: ";
	foreach ($data as $param)
		$cmd .= "DEF:{$param['ds']}=${rrd_file}:{$param['ds']}:AVERAGE ";
	foreach ($cdefs as $cdef)
		$cmd .= "CDEF:$cdef ";
	if (isset($comment))
		$cmd .= "COMMENT:\"$comment\" ";
	foreach ($data as $param)
		$cmd .= "{$param['type']}:{$param['dscdef']}#{$param['color']}:'{$param['label']}' "
			."GPRINT:{$param['dscdef']}:LAST:'Current\:%8.2lf %s' "
			."GPRINT:{$param['dscdef']}:MIN:'Minimum\:%8.2lf %s' "
			."GPRINT:{$param['dscdef']}:AVERAGE:'Average\:%8.2lf %s' "
			."GPRINT:{$param['dscdef']}:MAX:'Maximum\:%8.2lf %s\\n' ";
	system($cmd);
}

$nodeid = isset($_GET['nodeid']) ? $_GET['nodeid'] : 0;
$from = isset($_GET['from']) ? $_GET['from'] : NULL;
$to = isset($_GET['to']) ? $_GET['to'] : NULL;
$add = !empty($_GET['add']) ? $_GET['add'] : NULL;

$type = (isset($_GET['type']) ? $_GET['type'] : 'auto');
if (!in_array($type, array('auto', 'online', 'traffic')))
	$type = 'auto';

if (isset($_GET['bar']))
	$bar = $_GET['bar'];
else
	$SESSION->restore('rrdstatsbar_' . $type, $bar);
$SESSION->save('rrdstatsbar_' . $type, $bar);
$SESSION->close();

if (empty($bar))
	$bar = 'day';

if (empty($_GET['popup'])) {
	$todate = intval($to);
	$fromdate = intval($from);

	$tto = ($todate ? $todate : time());
	$tfrom = $fromdate;

	if ($tfrom > $tto) {
		$temp = $tfrom;
		$tfrom = $tto;
		$tto = $temp;
	}

	switch ($bar) {
		case 'hour':
			$tfrom = $tto - 60 * 60;
			break;
		case '3hours':
			$tfrom = $tto - 3 * 60 * 60;
			break;
		case '6hours':
			$tfrom = $tto - 6 * 60 * 60;
			break;
		case 'halfday':
			$tfrom = $tto - 60 * 60 * 12;
			break;
		case 'day':
			$tfrom = $tto - 60 * 60 * 24;
			break;
		case 'week':
			$tfrom = $tto - 60 * 60 * 24 * 7;
			break;
		case 'month':
			$tfrom = $tto - 60 * 60 * 24 * 30;
			break;
		case 'year':
			$tfrom = $tto - 60 * 60 * 24 * 365;
			break;
		default:
			$tfrom = $tfrom ? $tfrom : $tto - 60 * 60 * 24 * 30;
			break;
	}

	if ($add) {
		$tfrom += $add;
		$tto += $add;
	}

	header('Content-type: image/png');

	RRDGraph($nodeid, $type, $tfrom, $tto);

	die;
}

$SMARTY->assign('type', $type);
$SMARTY->assign('nodeid', $nodeid);
$SMARTY->assign('bar', $bar);
$SMARTY->assign('to', $to);
$SMARTY->assign('from', $from);
$SMARTY->assign('add', $add);
$SMARTY->display('rrdtrafficgraph.html');

?>
