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
class SpeedtestCustomerHandler {
	private function getSpeedtests(Smarty $SMARTY, $cid) {
		$speedtests = LMSDB::getInstance()->GetAll('SELECT s.*, INET_NTOA(n.ipaddr) AS ip,
				n.name, n.id AS nodeid FROM speedtests s
			JOIN nodes n ON n.id = s.nodeid
			JOIN customers c ON c.id = n.ownerid
			WHERE c.id = ?
			ORDER BY s.dt DESC LIMIT ' . intval(ConfigHelper::getConfig('speedtest.display_limit', 20)),
			array($cid));
		if (!empty($speedtests))
			foreach ($speedtests as &$test)
				foreach (array('download', 'upload') as $idx)
					if ($test[$idx] > 1024)
						$test[$idx] = (round(floatval($test[$idx]) / 1024.0, 2)) . ' M';
					else
						$test[$idx] = $test[$idx] . ' k';
		$SMARTY->assign('speedtests', $speedtests);
	}

	public function customerInfoBeforeDisplay(array $hook_data) {
		$this->getSpeedtests($hook_data['smarty'], intval($_GET['id']));
		return $hook_data;
	}

	public function customerEditBeforeDisplay(array $hook_data) {
		$this->getSpeedTests($hook_data['smarty'], intval($_GET['id']));
		return $hook_data;
	}
}

?>
