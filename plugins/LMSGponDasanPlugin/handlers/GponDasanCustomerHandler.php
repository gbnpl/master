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
 * CustomerHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class GponDasanCustomerHandler {
	public function customerInfoBeforeDisplay(array $hook_data) {
		global $LMS;

		$GPON = LMSGponDasanPlugin::getGponInstance();

		$SMARTY = $hook_data['smarty'];
		require_once(PLUGINS_DIR . '/' . LMSGponDasanPlugin::plugin_directory_name . '/modules/gpondasanonu.inc.php');

		return $hook_data;
	}

	public function customerEditBeforeDisplay(array $hook_data) {
		global $LMS;

		$GPON = LMSGponDasanPlugin::getGponInstance();

		$SMARTY = $hook_data['smarty'];
		require_once(PLUGINS_DIR . '/' . LMSGponDasanPlugin::plugin_directory_name . '/modules/gpondasanonu.inc.php');

		return $hook_data;
	}
}

?>
