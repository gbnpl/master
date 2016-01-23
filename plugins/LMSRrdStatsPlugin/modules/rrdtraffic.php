<?php

/* LMS version 1.11-git
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

function float_key_sort($key1, $key2) {
	$diff = floatval($key1) - floatval($key2);
	if ($diff > 0)
		return -1;
	elseif ($diff < 0)
		return 1;
	return 0;
}

function Traffic($from = 0, $to = 0, $order = '') {
	global $LMS;

	$db = LMSDB::getInstance();
	$nodes = $db->GetAllByKey('SELECT id, name, ipaddr FROM vnodes WHERE ownerid > 0', 'id');
	if (empty($nodes))
		return null;

	// period
	$fromdate = intval($from);
	$todate = intval($to);
	$delta = ($todate - $fromdate) ? ($todate - $fromdate) : 1;

	switch ($order) {
		case 'nodeid':
			$order = 'id';
			break;
		case 'download':
			$order = 'download';
			break;
		case 'upload':
			$order = 'upload';
			break;
		case 'name':
			$order = 'name';
			break;
		case 'ip':
			$order = 'ipaddr';
			break;
	}

	$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq', ConfigHelper::getConfig('phpui.stat_freq', 12)));

	$orderednodes = array();
	$total_download = $total_upload = 0;
	foreach ($nodes as $nodeid => &$node) {
		$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . $nodeid . '.rrd';
		if (!is_readable($rrd_file))
			continue;

		$out = array();
		$ret = 0;
		exec(RRDTOOL_BINARY . ' fetch ' . RRD_DIR . DIRECTORY_SEPARATOR . $nodeid . ".rrd AVERAGE -s $fromdate -e $todate", $out, $ret);
		if ($ret)
			continue;

		$lines = preg_grep('/^[0-9]+:\s+/', $out);
		if (empty($lines))
			continue;

		sscanf(reset($lines), "%d: %s %s\n", $date1, $download, $upload);
		sscanf(next($lines), "%d: %s %s\n", $date2, $download, $upload);
		$multiplier = ($date2 - $date1) / $stat_freq;

		$lines = preg_grep('/^[0-9]+:\s+[0-9]/', $out);
		if (empty($lines))
			continue;

		$date = $download = $upload = $node['download'] = $node['upload'] = 0;
		foreach ($lines as $line) {
			sscanf($line, "%d: %f %f\n", $date, $download, $upload);
			$node['download'] += $download * $multiplier;
			$node['upload'] += $upload * $multiplier;
		}

		$total_download += $node['download'];
		$total_upload += $node['upload'];

		if (in_array($order, array('download', 'upload')))
			$orderednodes[strval($node[$order])] = $node;
		else
			$orderednodes[$node[$order]] = $node;
	}
	unset($nodes);
	if (in_array($order, array('download', 'upload')))
		uksort($orderednodes, 'float_key_sort');
	else
		ksort($orderednodes);

	$traffic = array(
		'upload' => array(
			'data' => array(),
			'avg' => array(),
			'name' => array(),
			'ipaddr' => array(),
			'nodeid' => array(),
			'bar' => array(),
			'unit' => array(),
			'sum' => array(),
		),
		'download' => array(
			'data' => array(),
			'avg' => array(),
			'name' => array(),
			'ipaddr' => array(),
			'nodeid' => array(),
			'bar' => array(),
			'unit' => array(),
			'sum' => array(),
		),
	);
	foreach ($orderednodes as $node) {
		$traffic['upload']['data'][] = $node['upload'];
		$traffic['download']['data'][] = $node['download'];
		$traffic['upload']['avg'][] = $node['upload'] * 8 / ($delta * 1000);
		$traffic['download']['avg'][] = $node['download'] * 8 / ($delta * 1000);
		$traffic['upload']['name'][] = ($node['name'] ? $node['name'] : trans('unknown') . ' (ID: ' . $node['id'] . ')');
		$traffic['download']['name'][] = ($node['name'] ? $node['name'] : trans('unknown') . ' (ID: ' . $node['id'] . ')');
		$traffic['upload']['ipaddr'][] = $node['ipaddr'];
		$traffic['download']['ipaddr'][] = $node['ipaddr'];
		$traffic['upload']['nodeid'][] = $node['id'];
		$traffic['download']['nodeid'][] = $node['id'];
	}

	$traffic['upload']['sum']['data'] = $total_upload;
	$traffic['download']['sum']['data'] = $total_download;
	$traffic['upload']['avgsum'] = $total_upload * 8 / ($delta * 1000);
	$traffic['download']['avgsum'] = $total_download * 8 / ($delta * 1000);

	// get maximum data from array
	$maximum = max($traffic['download']['data']);
	if($maximum < max($traffic['upload']['data']))
		$maximum = max($traffic['upload']['data']);

	if ($maximum == 0)		// do not need divide by zero
		$maximum = 1;

	// make data for bars drawing
	$x = 0;
	foreach ($traffic['download']['data'] as $data) {
		$traffic['download']['bar'][] = round($data * 150 / $maximum);
		list ($traffic['download']['data'][$x], $traffic['download']['unit'][$x]) = setunits($data);
		$x++;
	}

	$x = 0;
	foreach ($traffic['upload']['data'] as $data) {
		$traffic['upload']['bar'][] = round($data * 150 / $maximum);
		list ($traffic['upload']['data'][$x], $traffic['upload']['unit'][$x]) = setunits($data);
		$x++;
	}

	//set units for data
	list ($traffic['download']['sum']['data'], $traffic['download']['sum']['unit']) = setunits($traffic['download']['sum']['data']);
	list ($traffic['upload']['sum']['data'], $traffic['upload']['sum']['unit']) = setunits($traffic['upload']['sum']['data']);

	return $traffic;
}

$layout['pagetitle'] = trans('Network Statistics');

if (isset($_GET['bar'])) {
	if (isset($_POST['order']))
		$SESSION->save('trafficorder', $_POST['order']);
}

$bar = isset($_GET['bar']) ? $_GET['bar'] : '';

switch ($bar) {
	case 'hour':
		$traffic = Traffic(time() - (60 * 60), time(),
			$SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
		break;

	case 'day':
		$traffic = Traffic(time() - (60 * 60 * 24), time(),
			$SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
		break;

	case 'month':
		$traffic = Traffic(time() - (60 * 60 * 24 * 30), time(),
			$SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
		break;

	case 'year':
		$traffic = Traffic(time() - (60 * 60 * 24 * 365), time(),
			$SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
		break;
}

if (isset($traffic)) {
	$SMARTY->assign('download', $traffic['download']);
	$SMARTY->assign('upload', $traffic['upload']);
}

$SMARTY->assign('showips', isset($_POST['showips']));
$SMARTY->assign('bar', $bar);
$SMARTY->assign('trafficorder', $SESSION->is_set('trafficorder') ? $SESSION->get('trafficorder') : 'download');
$SMARTY->display('rrdtraffic.html');

?>
