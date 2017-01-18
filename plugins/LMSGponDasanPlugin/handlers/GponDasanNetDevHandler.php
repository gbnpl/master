<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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
class GponDasanNetDevHandler {
	public function netdevinfoBeforeDisplay(array $hook_data) {
		$smarty = $hook_data['smarty'];
		$db = LMSDB::getInstance();

		$netdevinfo = $smarty->getTemplateVars('netdevinfo');
		$netdevinfo['gpononuid'] = $db->GetOne('SELECT id FROM ' . GPON_DASAN::SQL_TABLE_GPONONU . ' WHERE netdevid = ?',
			array($netdevinfo['id']));
		$smarty->assign('netdevinfo', $netdevinfo);

		return $hook_data;
	}
}

?>
