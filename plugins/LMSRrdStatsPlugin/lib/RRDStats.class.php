<?php

/*
 * LMS version 1.11-git
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

class RRDStats {
	static private function float_key_sort($key1, $key2) {
		$diff = floatval($key1) - floatval($key2);
		if ($diff > 0)
			return -1;
		elseif ($diff < 0)
			return 1;
		return 0;
	}

	static public function Traffic($params) {
		extract($params);

		if (isset($params['from']))
			$from = intval($params['from']);
		else
			$from = 0;

		if (isset($params['to']))
			$to = intval($params['to']);
		else
			$to = 0;

		if (isset($params['net']))
			$net = intval($params['net']);
		else
			$net = 0;

		if (isset($params['customer']))
			$customer = intval($params['customer']);
		else
			$customer = 0;

		if (isset($params['order']))
			$order = $params['order'];
		else
			$order = '';

		$db = LMSDB::getInstance();
		$nodes = $db->GetAllByKey('SELECT id, name, INET_NTOA(ipaddr) AS ip FROM vnodes WHERE ownerid > 0'
			. (!empty($net) ? ' AND netid = ' . intval($net) : '')
			. (!empty($customer) ? ' AND ownerid = ' . $customer : ''), 'id');
		if (empty($nodes))
			return null;

		// period
		$fromdate = $from;
		$todate = $to;
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
		$rrd_dir = LMSRrdStatsPlugin::getRrdDirectory();
		foreach ($nodes as $nodeid => &$node) {
			$rrd_file =  $rrd_dir . DIRECTORY_SEPARATOR . $nodeid . '.rrd';
			if (!is_readable($rrd_file))
				continue;

			$out = array();
			$ret = 0;
			exec(RRDTOOL_BINARY . ' fetch ' . $rrd_dir . DIRECTORY_SEPARATOR . $nodeid . ".rrd AVERAGE -s $fromdate -e $todate", $out, $ret);
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
			uksort($orderednodes, array($this, 'float_key_sort'));
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
			$traffic['upload']['ipaddr'][] = $node['ip'];
			$traffic['download']['ipaddr'][] = $node['ip'];
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
}

?>
