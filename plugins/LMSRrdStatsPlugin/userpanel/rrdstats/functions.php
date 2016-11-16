<?php

/*
 *  LMS version 1.11-git
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

if (defined('USERPANEL_SETUPMODE')) {
	function module_setup() {
		global $SMARTY,$LMS;
		$SMARTY->assign('owner_stats', ConfigHelper::getConfig('userpanel.owner_rrdstats'));
		$SMARTY->display('module:rrdstats:setup.html');
	}

	function module_submit_setup() {
		global $SMARTY;
		$DB = LMSDB::getInstance();
		if ($_POST['owner_stats']) {
			$DB->Execute('UPDATE uiconfig SET value = \'1\' WHERE section = \'userpanel\' AND var = \'owner_rrdstats\'');
		} else {
			$DB->Execute('UPDATE uiconfig SET value = \'0\' WHERE section = \'userpanel\' AND var = \'owner_rrdstats\'');
		}
		header('Location: ?m=userpanel&module=rrdstats');
	}
}

function module_main() {
	global $SMARTY, $SESSION;
	 $bars = 1;

	if (isset($_GET['bar']) && isset($_POST['order']))
		$SESSION->save('trafficorder', $_POST['order']);

	$bar = isset($_GET['bar']) ? $_GET['bar'] : '';
	$owner = ConfigHelper::checkConfig('userpanel.owner_rrdstats') ? $SESSION->id : NULL;

	switch ($bar) {
		case 'hour':
			$traffic = RRDStats::Traffic(array(
				'from' => time()-(60*60),
				'to' => time(),
				'customer' => $owner,
				'order' => 'download',
			));
			break;

		case 'day':
			$traffic = RRDStats::Traffic(array(
				'from' => time()-(60*60*24),
				'to' => time(),
				'customer' => $owner,
				'order' => 'download',
			));
			break;

		case 'year':
			$traffic = RRDStats::Traffic(array(
				'from' => time()-(60*60*24*365),
				'to' => time(),
				'customer' => $owner,
				'order' => 'download',
			));
			break;

		case 'all':
			$traffic = RRDStats::Traffic(array(
				'from' => 0,
				'to' => time(),
				'customer' => $owner,
				'order' => 'download',
			));
			break;

		case 'month':
		default:
			$traffic = RRDStats::Traffic(array(
				'from' => time()-(60*60*24*30),
				'to' => time(),
				'customer' => $owner,
				'order' => 'download',
			));
			break;
	}

	if (isset($traffic)) {
		$SMARTY->assign('download', $traffic['download']);
		$SMARTY->assign('upload', $traffic['upload']);
	}

	$layout['pagetitle'] = trans('Network Statistics');

	$SMARTY->assign('bar', $bar ? $bar : 'month');
	$SMARTY->display('module:stats.html');
}

?>
