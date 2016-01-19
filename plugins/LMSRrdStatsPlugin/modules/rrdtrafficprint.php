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

if (!ConfigHelper::checkConfig('privileges.superuser') && !ConfigHelper::checkConfig('privileges.reports'))
	access_denied();

$type = isset($_GET['type']) ? $_GET['type'] : '';

switch ($type) {
	case 'customertraffic': /******************************************/

		$month = isset($_POST['month']) ? $_POST['month'] : date('n');
		$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
		$customer = isset($_POST['customer']) ? intval($_POST['customer']) : intval($_GET['customer']);

		$layout['pagetitle'] = trans('Stats of Customer $a in month $b',
			$LMS->GetCustomerName($customer), strftime('%B %Y', mktime(0, 0, 0, $month, 1, $year)));

		$from = mktime(0, 0, 0, $month, 1, $year);
		$to = mktime(0, 0, 0, $month + 1, 1, $year);

		for ($i = 1; $i <= date('t', $from); $i++) {
			$stats[$i]['date'] = mktime(0, 0, 0, $month, $i, $year);
			$stats[$i]['download'] = 0;
			$stats[$i]['upload'] = 0;
		}

		$nodes = $DB->GetCol('SELECT id FROM nodes WHERE ownerid = ?', array($customer));
		if (!empty($nodes)) {
			foreach ($nodes as $nodeid) {
				$rrd_file = RRD_DIR . DIRECTORY_SEPARATOR . $nodeid . '.rrd';
				if (!is_readable($rrd_file))
					continue;

				$out = array();
				$ret = 0;
				exec(RRDTOOL_BINARY . ' fetch ' . RRD_DIR . DIRECTORY_SEPARATOR . $nodeid . ".rrd AVERAGE -s $from -e $to", $out, $ret);
				if ($ret)
					continue;

				$lines = preg_grep('/^[0-9]+:\s+[0-9]/', $out);
				if (empty($lines))
					continue;

				$date = $download = $upload = 0;
				foreach ($lines as $line) {
					sscanf($line, "%d: %f %f\n", $date, $download, $upload);

					$day = date('j', $date);

					$stats[$day]['download'] += $download;
					$stats[$day]['upload'] += $upload;
				}
			}

			$listdata = array(
				'upload' => 0,
				'download' => 0,
				'upavg' => 0,
				'downavg' => 0,
			);
			for ($i = 1; $i <= date('t', $from); $i++) {
				$stats[$i]['upavg'] = $stats[$i]['upload'] * 8 / 1000 / 86400; //kbit/s
				$stats[$i]['downavg'] = $stats[$i]['download'] * 8 / 1000 / 86400; //kbit/s
				$listdata['upload'] += $stats[$i]['upload'];
				$listdata['download'] += $stats[$i]['download'];
				$listdata['upavg'] += $stats[$i]['upavg'];
				$listdata['downavg'] += $stats[$i]['downavg'];

				list ($stats[$i]['upload'], $stats[$i]['uploadunit']) = setunits($stats[$i]['upload']);
				list ($stats[$i]['download'], $stats[$i]['downloadunit']) = setunits($stats[$i]['download']);
			}

			$listdata['upavg'] = $listdata['upavg'] / date('t', $from);
			$listdata['downavg'] = $listdata['downavg'] / date('t', $from);
			list ($listdata['upload'], $listdata['uploadunit']) = setunits($listdata['upload']);
			list ($listdata['download'], $listdata['downloadunit']) = setunits($listdata['download']);

			$SMARTY->assign('stats', $stats);
			$SMARTY->assign('listdata', $listdata);
		}

		if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
			$output = $SMARTY->fetch('print/printcustomertraffic.html');
			html2pdf($output, trans('Reports'), $layout['pagetitle']);
		} else
			$SMARTY->display('print/printcustomertraffic.html');
		break;

/*
	default:
		$layout['pagetitle'] = trans('Reports');

		$yearstart = date('Y', (int) $DB->GetOne('SELECT MIN(dt) FROM stats'));
		$yearend = date('Y', (int) $DB->GetOne('SELECT MAX(dt) FROM stats'));
		for($i=$yearstart; $i<$yearend+1; $i++)
			$statyears[] = $i;
		for($i=1; $i<13; $i++)
			$months[$i] = strftime('%B', mktime(0,0,0,$i,1));

		if (!ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.big_networks', false)))
			$SMARTY->assign('customers', $LMS->GetCustomerNames());
		$SMARTY->assign('currmonth', date('n'));
		$SMARTY->assign('curryear', date('Y'));
		$SMARTY->assign('statyears', $statyears);
		$SMARTY->assign('months', $months);
		$SMARTY->assign('printmenu', 'traffic');
		$SMARTY->display('print/printindex.html');
		break;
*/
}

?>
