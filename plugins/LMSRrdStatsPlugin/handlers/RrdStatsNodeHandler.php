<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2015 LMS Developers
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

/**
 * NodeHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class RrdStatsNodeHandler {
	private function getLastStats($nodeid, $period) {
		$rrd_dir = LMSRrdStatsPlugin::getRrdDirectory();

		$out = array();
		$ret = 0;

		$todate = time();
		switch ($period) {
			case '1hour': $fromdate = $todate - 3600; break;
			case '1day': $fromdate = $todate -  24 * 3600; break;
			case '1month': $fromdate = $todate - 30 * 24 * 3600; break;
		}
		$delta = $todate - $fromdate;

		exec(RRDTOOL_BINARY . ' fetch ' . $rrd_dir . DIRECTORY_SEPARATOR . $nodeid . ".rrd AVERAGE -s $fromdate -e $todate", $out, $ret);
		if ($ret)
			return null;

		$lines = preg_grep('/^[0-9]+:\s+/', $out);
		if (empty($lines))
			return null;

		$stat_freq = intval(ConfigHelper::getConfig('rrdstats.stat_freq', ConfigHelper::getConfig('phpui.stat_freq', 12)));

		sscanf(reset($lines), "%d: %s %s\n", $date1, $download, $upload);
		sscanf(next($lines), "%d: %s %s\n", $date2, $download, $upload);
		$multiplier = ($date2 - $date1) / $stat_freq;

		$lines = preg_grep('/^[0-9]+:\s+[0-9]/', $out);
		if (empty($lines))
			return null;

		$date = $download = $upload = $total_download = $total_upload = 0;
		foreach ($lines as $line) {
			sscanf($line, "%d: %f %f\n", $date, $download, $upload);
			$total_download += $download * $multiplier;
			$total_upload += $upload * $multiplier;
		}
		$result = array(
			'downavg' => sprintf("%.0f", $total_download * 8 / ($delta * 1000)),
			'upavg' => sprintf("%.0f", $total_upload * 8 / ($delta * 1000)),
		);
		list ($result['download']['data'], $result['download']['units']) = setunits($total_download);
		list ($result['upload']['data'], $result['upload']['units']) = setunits($total_upload);
		return $result;
	}

	private function getStats($SMARTY, $nodeid) {
		$rrdstats = array();
		$SMARTY->assignByRef('rrdstats', $rrdstats);

		$rrd_file = $rrd_dir . DIRECTORY_SEPARATOR . $nodeid . '.rrd';
		if (!is_readable($rrd_file))
			return $rrdstats;

		$lasthour = $this->getLastStats($nodeid, '1hour');
		$lastday = $this->getLastStats($nodeid, '1day');
		$lastmonth = $this->getLastStats($nodeid, '1month');

		$rrdstats = array(
			'hour' => $lasthour,
			'day' => $lastday,
			'month' => $lastmonth,
		);

		return $rrdstats;
	}

	public function nodeInfoBeforeDisplay(array $hook_data) {
		$rrdstats = $this->getStats($hook_data['smarty'], $_GET['id']);
		return $hook_data;
	}
}

?>
