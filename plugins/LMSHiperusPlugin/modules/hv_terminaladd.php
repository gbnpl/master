<?php


/*
 * LMS iNET
 *
 *  (C) Copyright 2012 LMS iNET Developers
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


$cusid = intval($_GET['cusid']);
$account = $HIPERUS->GetCustomer($cusid);
$layout['pagetitle'] = 'Nowy Terminal dla : '.$account['name'];
$terminal = array();
$blad = false;

if (isset($_POST['terminaladd'])) {
	$terminal = $_POST['terminaladd'];
	if ($tid = $HIPERUS->CreateTerminal($terminal)) {
		$HIPERUS->ImportTerminalList($terminal['customer_id']);
		$DB->Execute('UPDATE hv_terminal SET location = ?, location_city = ?, location_street = ?,
			location_house = ?, location_flat = ? WHERE id = ?', array($terminal['location'],
			$terminal['location_city'], $terminal['location_street'], $terminal['location_house'],
			$terminal['location_flat'], $tid));
		$SESSION->redirect('?m=hv_accountinfo&id=' . $terminal['customer_id']);
	} else {
		$blad = true;
		$terminal['username'] = '';
	}
} else {
	$default_location = ConfigHelper::getConfig('hiperus_c5.default_location');
	if (!empty($default_location)) {
		$location = $DB->GetRow('SELECT p.name AS province, c.name AS county, b.name AS borough FROM hv_pcb
			JOIN hv_province p ON p.id = province
			JOIN hv_county c ON c.id = county
			JOIN hv_borough b ON b.id = borough
			WHERE hv_pcb.id = ?', array(intval($default_location)));
		if ($location) {
			$terminal = array_merge($terminal, $location);
			$terminal['id_terminal_location'] = $default_location;
		}
	}
	$default_terminal_username = ConfigHelper::getConfig('hiperus_c5.default_terminal_username');
	if (!empty($default_terminal_username)) {
		$terminalcount = 1 + $DB->GetOne("SELECT COUNT(*) FROM hv_terminal WHERE customerid = ?", array($cusid));
		$terminal['username'] = preg_replace(array('/%cid/', '/%tcount/'), array($account['ext_billing_id'], $terminalcount), $default_terminal_username);
	}
	$terminal['subscription_from'] = strftime("%Y/%m/%d");
}

$SMARTY->assign('terminal', $terminal);
$SMARTY->assign('blad',$blad);
$SMARTY->assign('account',$account);
$SMARTY->assign('pricelists', $HIPERUS->GetPriceList());
$SMARTY->assign('subscriptions', $HIPERUS->GetSubscriptionList());
$SMARTY->display('hv_terminaladd.html');

?>
