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

if (defined('USERPANEL_SETUPMODE')) {
	function module_setup() {
		global $SMARTY, $LMS;

		$SMARTY->assign('speedtest_url', ConfigHelper::getConfig('userpanel.speedtest_url'));
		$SMARTY->display('module:speedtest:setup.html');
    }

	function module_submit_setup() {
		global $SMARTY, $DB;

		$error = null;

		$speedtest_url = $_POST['speedtest_url'];
		$comps = parse_url($speedtest_url);
		if ($comps === false || !array_key_exists('scheme', $comps) || !array_key_exists('host', $comps))
			$error['speedtest_url'] = trans('Invalid URL!');

		if (isset($error)) {
			$SMARTY->assign('speedtest_url', $speedtest_url);
			$SMARTY->assign('error', $error);
			$SMARTY->display('module:speedtest:setup.html');
		} else
			$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
				array($speedtest_url, 'userpanel', 'speedtest_url'));
			header('Location: ?m=userpanel&module=speedtest');
	}
}

function module_main() {
	global $SMARTY;

	$SMARTY->display('module:speedtest.html');
}

?>
