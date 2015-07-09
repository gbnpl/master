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
class HiperusNodeHandler {
	private function GetHiperusAccounts($cid) {
		global $SMARTY, $DB, $HIPERUS;

		if ($DB->GetOne('SELECT id FROM hv_customers WHERE ext_billing_id=? LIMIT 1', array($cid)))
			$SMARTY->assign('hiperusaccountcustomerlist',
				$HIPERUS->GetCustomerListList('name,asc', array('extid' => $cid)));
	}

	public function NodeInfoOnLoad() {
		global $DB;
		$this->GetHiperusAccounts($DB->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array(intval($_GET['id']))));
	}

	public function NodeEditOnLoad() {
		global $DB;
		$this->GetHiperusAccounts($DB->GetOne('SELECT ownerid FROM nodes WHERE id = ?', array(intval($_GET['id']))));
	}

	public function NodeAddOnLoad() {
		$this->GetHiperusAccounts(intval($_GET['ownerid']));
	}
}

?>
