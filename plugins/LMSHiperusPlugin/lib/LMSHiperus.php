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


define('H_SESSION_FILE', PLUGINS_DIR . DIRECTORY_SEPARATOR . 'LMSHiperusPlugin' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'session');
define('H_LOCK_FILE', PLUGINS_DIR . DIRECTORY_SEPARATOR . 'LMSHiperusPlugin' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'lock');

class LMSHiperus {
	private $DB;


	public function __construct(&$DB) {
		$this->DB = &$DB;
	}

    private function HP_ChangePSTNNumberData($e_data) {
        $hlib = new HiperusLib();
        $r = new stdClass();
	$r->id_extension = $e_data['id'];
	$r->extension = $e_data['extension'];
	$r->country_code = $e_data['country_code'];
	$r->number = $e_data['number'];
	$r->is_main = ($e_data['is_main'] == 't' ? true : false );
	$r->disa_enabled = ($e_data['disa_enabled'] == 't' ? true : false );
        $r->clir = ($e_data['clir'] == 't' ? true : false );
        $r->virtual_fax = ($e_data['virtual_fax'] == 't' ? true : false );
        $r->terminal_name = $e_data['terminal_name'];
        $r->voicemail_enabled = ($e_data['voicemail_enabled'] == 't' ? true : false );
        $response = $hlib->sendRequest("SaveExtensionData",$r);
        if($response->success===false) {
            throw new Exception("Nie można zapisać danych numeru PSTN. \n".$response->error_message);
        }
        return true;
    }


	public function ImportEndUserList($cid = NULL) {
		if (is_null($cid))
			$cids = $this->DB->GetCol('SELECT id FROM hv_customers ORDER BY id');
		else
			$cids = array($cid);
		$euids = array();
		foreach ($cids as $cid) {
			$endusers = HiperusActions::GetEndUserList($cid);
			sleep(1);
			if (!is_array($endusers) || empty($endusers))
				continue;
			foreach ($endusers as $enduser)
				if (!is_null($enduser['id'])) {
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_enduserlist WHERE id=?',
						array($enduser['id'])))
						$this->DB->Execute('INSERT INTO hv_enduserlist (id,customerid,password,email,admin,vm_count,fax_count,exten_count,vexten_count) VALUES (?,?,?,?,?,?,?,?,?)',
							array(
							    $enduser['id'],
							    $cid,
							    (!empty($enduser['password']) ? $enduser['password'] : NULL),
							    (!empty($enduser['email']) ? $enduser['email'] : NULL),
							    (!empty($enduser['admin']) ? $enduser['admin'] : 't'),
							    (!empty($enduser['vm_count']) ? $enduser['vm_count'] : NULL),
							    (!empty($enduser['fax_count']) ? $enduser['fax_count'] : NULL),
							    (!empty($enduser['exten_count']) ? $enduser['exten_count'] : NULL),
							    (!empty($enduser['vexten_count']) ? $enduser['vexten_count'] : NULL)));
					else
						$this->DB->Execute('UPDATE hv_enduserlist SET customerid=?, password=?, email=?, admin=?, vm_count=?, fax_count=?, exten_count=?, vexten_count=? WHERE id=?',
							array(
							    $cid,
				 			    (!empty($enduser['password']) ? $enduser['password'] : NULL),
							    (!empty($enduser['email']) ? $enduser['email'] : NULL),
							    (!empty($enduser['admin']) ? $enduser['admin'] : 't'),
							    (!empty($enduser['vm_count']) ? $enduser['vm_count'] : NULL),
							    (!empty($enduser['fax_count']) ? $enduser['fax_count'] : NULL),
							    (!empty($enduser['exten_count']) ? $enduser['exten_count'] : NULL),
							    (!empty($enduser['vexten_count']) ? $enduser['vexten_count'] : NULL),
							    $enduser['id']));
					$euids[] = $enduser['id'];
				}
		}
		$this->DB->Execute('DELETE FROM hv_enduserlist' . (empty($euids) ? '' : ' WHERE id NOT IN (' . implode(',', $euids) . ')'));
	}


	public function ImportPriceList() {
		$pricelists = HiperusActions::GetPriceListList();
		$plids = array();
		if (is_array($pricelists) && !empty($pricelists))
			foreach ($pricelists as $pricelist)
				if (!is_null($pricelist['id'])) {
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_pricelist WHERE id=?',
						array($pricelist['id'])))
						$this->DB->Execute('INSERT INTO hv_pricelist (id,name,charge_internal_call) VALUES (?,?,?)',
							array(
								$pricelist['id'],
								(!empty($pricelist['name']) ? $pricelist['name'] : NULL),
								(!empty($pricelist['chare_internal_call']) ? $pricelist['charge_internal_call'] : 'f')));
					else
						$this->DB->Execute('UPDATE hv_pricelist SET name=?, charge_internal_call=? WHERE id=?',
							array(
								(!empty($pricelist['name']) ? $pricelist['name'] : NULL),
								(!empty($pricelist['chare_internal_call']) ? $pricelist['charge_internal_call'] : 'f'),
								$pricelist['id']));
					$plids[] = $pricelist['id'];
				}
		$this->DB->Execute('DELETE FROM hv_pricelist' . (empty($plids) ? '' : ' WHERE id NOT IN (' . implode(',', $plids) . ')'));
	}


	public function ImportSubscriptionList() {
		$subscriptions = HiperusActions::GetSubscriptionlist();
		$sids = array();
		if (is_array($subscriptions) && !empty($subscriptions))
			foreach ($subscriptions as $subscription)
				if (!is_null($subscription['id'])) {
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_subscriptionlist WHERE id=?',
						array($subscription['id'])))
						$this->DB->Execute('INSERT INTO hv_subscriptionlist (id,name,value,f_dld,f_mobile,f_ild,id_reseller,invoice_value) VALUES (?,?,?,?,?,?,?,?)',
						    array(
							    $subscription['id'],
							    (!empty($subscription['name']) ? $subscription['name'] : NULL),
							    (!empty($subscription['value']) ? $subscription['value'] : '0.00'),
							    (!empty($subscription['f_dld']) ? $subscription['f_dld'] : NULL),
							    (!empty($subscription['f_mobile']) ? $subscription['f_mobile'] : NULL),
							    (!empty($subscription['f_ild']) ? $subscription['f_ild'] : NULL),
							    (!empty($subscription['id_reseller']) ? $subscription['id_reseller'] : NULL),
							    (!empty($subscription['invoice_value']) ? $subscription['invoice_value'] : '0.00')));
					else
						$this->DB->Execute('UPDATE hv_subscriptionlist SET name=?, value=?, f_dld=?, f_mobile=?, f_ild=?, id_reseller=?, invoice_value=? WHERE id=?',
							array(
							    (!empty($subscription['name']) ? $subscription['name'] : NULL),
							    (!empty($subscription['value']) ? $subscription['value'] : '0.00'),
							    (!empty($subscription['f_dld']) ? $subscription['f_dld'] : NULL),
							    (!empty($subscription['f_mobile']) ? $subscription['f_mobile'] : NULL),
							    (!empty($subscription['f_ild']) ? $subscription['f_ild'] : NULL),
							    (!empty($subscription['id_reseller']) ? $subscription['id_reseller'] : NULL),
							    (!empty($subscription['invoice_value']) ? $subscription['invoice_value'] : '0.00'),
							    $subscription['id']));
					$sids[] = $subscription['id'];
				}
		$this->DB->Execute('DELETE FROM hv_subscriptionlist' . (empty($sids) ? '' : ' WHERE id NOT IN (' . implode(',', $sids) . ')'));
	}

	private function InsertBilling($record, $cusid, $success) {
		if (empty($record) || $this->DB->GetOne('SELECT 1 FROM hv_billing WHERE id = ?',
			array($record['id'])))
			return;
		$this->DB->Execute('INSERT INTO hv_billing (id,customerid,rel_cause,start_time,start_time_unix,customer_name,terminal_name,ext_billing_id,caller,bill_cpb,duration,calltype,
			country,description,operator,type,cost,price,init_charge,reseller_price,reseller_cost,reseller_init_charge,margin,subscription_used,platform_type,success_call)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
			array(
				$record['id'],
				$cusid,
				$record['rel_cause'],
				$record['start_time'],
				strtotime($record['start_time']),
				$record['customer_name'],
				(!empty($record['terminal_name']) ? $record['terminal_name'] : NULL),
				(!empty($record['ext_billing_id']) ? $record['ext_billing_id'] : 0),
				(!empty($record['caller']) ? $record['caller'] : NULL),
				(!empty($record['bill_cpb']) ? $record['bill_cpb'] : NULL),
				(!empty($record['duration']) ? $record['duration'] : 0),
				(!empty($record['calltype']) ? $record['calltype'] : NULL),
				(!empty($record['country']) ? $record['country'] : NULL),
				(!empty($record['description']) ? $record['description'] : NULL),
				(!empty($record['operator']) ? $record['operator'] : NULL),
				(!empty($record['type']) ? $record['type'] : NULL),
				(!empty($record['cost']) ? $record['cost'] : 0),
				(!empty($record['price']) ? $record['price'] : 0),
				(!empty($record['init_charge']) ? $record['init_charge'] : 0),
				(!empty($record['reseller_price']) ? $record['reseller_price'] : 0),
				(!empty($record['reseller_cost']) ? $record['reseller_cost'] : 0),
				(!empty($record['reseller_init_charge']) ? $record['reseller_init_charge'] : 0),
				(!empty($record['margin']) ? $record['margin'] : 0),
				$record['subscription_used'],
				(!empty($record['platform_type']) ? $record['platform_type'] : NULL),
				$success
			));
	}

	public function ImportBilling($from = NULL, $to = NULL, $success = 'yes', $type = 'outgoing', $cid = NULL, $quiet = true) {
		if (is_null($from) || is_null($to)) {
			$from = date('Y-m-d', time());
			$to = $from;
		}

		$success = strtolower($success);
		if (!in_array($success, array('all', 'yes', 'no')))
			$success = 'yes';
		$type = strtolower($type);

		if (!in_array($type, array('all', 'incoming', 'outgoing', 'disa', 'forwarded', 'internal', 'vpbx')))
			$type = 'outgoing';

		if (is_null($cid)) {
			if (!$quiet)
				print  "Pobieram dla wszystkich klientów (typ połączenia: " . $type . ") ..." . PHP_EOL;

			$customers = $this->DB->GetAllByKey('SELECT customerid, username FROM hv_terminal',
				'username');
			if (empty($customers))
				return;

			if ($success === 'yes' || $success === 'all') {
				$records = HiperusActions::GetBilling($from, $to, null, null, true, null, $type);
				if (!empty($records)) {
					if (!$quiet)
						print "Pobrano " . count($records) . " rekordów bilingowych dla połączeń zakończonych sukcesem." . PHP_EOL;
					foreach ($records as $record)
						if (empty($record['terminal_name'])) {
							if (!$quiet)
								print "Brak powiązania z klientem w LMS dla rekordu " . $record['id'] . " (" . $record['customer_name'] . ")!" . PHP_EOL;
						} else
							$this->InsertBilling($record, $customers[$record['terminal_name']]['customerid'], 't');
				}
			}
			if ($success === 'no' || $success === 'all') {
				$records = HiperusActions::GetBilling($from, $to, null, null, false, null, $type);
				if (!empty($records)) {
					if (!$quiet)
						print "Pobrano " . count($records) . " rekordów bilingowych dla połączeń zakończonych niepowodzeniem." . PHP_EOL;
					foreach ($records as $record)
						if (empty($record['terminal_name'])) {
							if (!$quiet)
								print "Brak powiązania z klientem w LMS dla rekordu " . $record['id'] . " (" . $record['customer_name'] . ")!" . PHP_EOL;
						} else
							$this->InsertBilling($record, $customers[$record['terminal_name']]['customerid'], 'f');
				}
			}
		} else {
			$customername = $this->DB->GetOne('SELECT name FROM hv_customers WHERE id = ?', array($cid));
			if (!$quiet)
				print  "Pobieram dla: " . $customername . " (typ połączenia: " . $type . ") ..." . PHP_EOL;

			if ($success === 'yes' || $success === 'all') {
				$records = HiperusActions::GetBilling($from, $to, null, null, true, $cid, $type);
				if (!empty($records)) {
					if (!$quiet)
						print "Pobrano " . count($records) . " rekordów bilingowych dla połączeń zakończonych sukcesem." . PHP_EOL;
					foreach ($records as $record)
						$this->InsertBilling($record, $cid, 't');
				}
			}
			if ($success === 'no' || $success === 'all') {
				$records = HiperusActions::GetBilling($from, $to, null, null, false, $cid, $type);
				if (!empty($records)) {
					if (!$quiet)
						print "Pobrano " . count($records) . " rekordów bilingowych dla połączeń zakończonych niepowodzeniem." . PHP_EOL;
					foreach ($records as $record)
						$this->InsertBilling($record, $cid, 'f');
				}
			}

			if (!$quiet)
				print PHP_EOL;
		}

		sleep(5);
	}

	public function ImportBillingToFile($from, $to) {
		return HiperusActions::GetBillingFile($from, $to);
	}


	public function AllImportBilling($from = NULL, $to = NULL) {
		return HiperusActions::GetBilling($from, $to);
	}

    function GetCustomerList() {
    
	return $this->DB->GetAll('SELECT * FROM hv_customers');
	
    }
    
    
    function GetCustomerListList($sort=NULL,$filtr=NULL)
    {
	
	if (is_null($sort)) $sort = ' ORDER BY hv.name ASC';
	else
	{
	    switch ($sort)
	    {
		case 'name,asc'		: $sort = ' ORDER BY hv.name ASC';	break;
		case 'name,desc'	: $sort = ' ORDER BY hv.name DESC';	break;
		default 		: $sort = ' ORDER BY hv.name ASC';	break;
	    }
	}
	$hvext = $hvvat = $hvpayment = $hvprice = $extid = '';
	if (!is_null($filtr) && is_array($filtr))
	{
	    if (isset($filtr['hvext'])) { 
		switch ($filtr['hvext'])
		{
		    case '1'		: $hvext = ' AND hv.ext_billing_id!=0'; break;
		    case '2'		: $hvext = ' AND ( hv.ext_billing_id=0 OR hv.ext_billing_id IS NULL) '; break;
		    default		: $hvext = ''; break;
		}
	    } else $hvext = '';
	    
	    if (isset($filtr['hvvat'])) {
		switch ($filtr['hvvat'])
		{
		    case 'none'		: $hvvat = ' AND ha.keyvalue=\'0\' '; break;
		    case 'hiperus'	: $hvvat = ' AND ha.keyvalue=\'1\' '; break;
		    case 'lms'		: $hvvat = ' AND ha.keyvalue=\'2\' '; break;
		    default		: $hvvat = ''; break;
		}
	    } else $hvvat = '';
	    
	    if (isset($filtr['hvpayment'])) {
		switch ($filtr['hvpayment'])
		{
		    case 'postpaid'	: $hvpayment = ' AND hv.payment_type=\'postpaid\' '; break;
		    case 'prepaid'	: $hvpayment = ' AND hv.payment_type=\'prepaid\' '; break;
		    default		: $hvpayment = ''; break;
		}
	    } else $hvpayment = '';
	    
	    if (isset($filtr['hvprice'])) {
		{
		    if ($filtr['hvprice'] == 'noprice') $hvprice = ' AND id_default_pricelist IS NULL ';
		    elseif ($filtr['hvprice'] == '') $hvprice = ' ';
		    else $hvprice = ' AND hv.id_default_pricelist = '.$filtr['hvprice'].' ';
		}
	    } else $hvprice = '';
	    
	    if (isset($filtr['extid']))
	    {
		$extid = ' AND hv.ext_billing_id = '.$filtr['extid'].' ';
	    }
	    else $extid = '';
	}
	$sql = 'SELECT 
		hv.id, hv.name, hv.address, hv.street_number, hv.apartment_number, hv.postcode, hv.city, hv.ext_billing_id ,hv.id_default_pricelist ,
		hv.payment_type, hv.active, 
		c.id AS cid, c.lastname AS clastname, c.name AS cname, c.address AS caddress, c.zip AS czip, c.city AS ccity,'
		.' COALESCE( (SELECT hv_pricelist.name FROM hv_pricelist WHERE hv_pricelist.id = hv.id_default_pricelist LIMIT 1),NULL) AS price_name, '
		.' COALESCE((SELECT COUNT(*) FROM hv_pstn WHERE hv_pstn.customerid=hv.id),0) AS pstncount, '
		.' COALESCE((SELECT COUNT(*) FROM hv_terminal WHERE hv_terminal.customerid=hv.id),0) AS terminalcount, '
		.' ha.keyvalue AS invoice '
		.' FROM hv_customers AS hv '
		.' LEFT JOIN hv_assign AS ha ON (ha.customerid = hv.id) '
		.' LEFT JOIN customers AS c ON (c.id = hv.ext_billing_id) '
		.' WHERE 1=1 '
		.$hvext
		.$hvvat
		.$hvpayment
		.$hvprice
		.$extid
		.($sort ? $sort : '')
		.' ;';
	return $this->DB->GetAll($sql);
	
    }
    
    
    
    function GetCustomerLMSMinList($id=NULL)
    {
	if (is_null($id))
	return $this->DB->GetAll('SELECT id, lastname, name, (SELECT contact FROM customercontacts WHERE customerid = customers.id AND type = ? LIMIT 1) AS email,
		address,zip,city,ten,ssn,regon,post_address,post_zip,post_city FROM customers WHERE deleted=0 AND status=3
		ORDER BY lastname,name ASC', array(CONTACT_EMAIL));
	else
	return $this->DB->GetRow('SELECT id, lastname, name, (SELECT contact FROM customercontacts WHERE customerid = customers.id AND type = ? LIMIT 1) AS email,
		address,zip,city,ten,ssn,regon,post_address,post_zip,post_city FROM customers WHERE  id=?', array($id, CONTACT_EMAIL));
    }
    
    function GetPriceList()
    {
	return $this->DB->GetAll('SELECT * FROM hv_pricelist ;');
    }
    
    function GetSubscriptionList()
    {
	return $this->DB->GetAll('SELECT * FROM hv_subscriptionlist ;');
    }


    function GetListBillingByCustomer($customerid,$rok=NULL,$msc=NULL,$calltype=NULL,$callsuccess=NULL,$terminal=NULL)
    {
	$call_success = NULL;
	if (!is_null($terminal) && empty($terminal)) $terminal=NULL;
	if (!is_null($rok) && empty($rok)) $rok=NULL;
	if (!is_null($msc) && empty($msc)) $msc=NULL;
	if (!is_null($calltype) && empty($calltype)) $calltype=NULL;
	if (!is_null($callsuccess) && empty($callsuccess)) $callsuccess=NULL;
	if (!is_null($callsuccess))
	{
	    if (is_bool($callsuccess)===true)
	    {
		if ($callsuccess===true) $call_success='t';
		elseif ($callsuccess===false) $call_success='f';
		else $call_success = NULL;
	    }
	    elseif (is_string($callsuccess)===true)
	    {
		if (strtolower($callsuccess)=='t') $call_success='t';
		elseif (strtolower($callsuccess)=='f') $call_success='f';
		else $call_success=NULL;
	    }
	    else $call_success = NULL;
	}
	    else $call_success = NULL;
	    
	$zap = 'SELECT 
		SUM(cost) AS cost, SUM(init_charge) AS init_charge, SUM(reseller_cost) AS reseller_cost, SUM(reseller_init_charge) AS reseller_init_charge, '.$this->DB->month('start_time').' AS msc, '.$this->DB->year('start_time').' AS rok 
		FROM hv_billing 
		WHERE customerid='.$customerid.' '
		.(!is_null($rok) ? ' AND '.$this->DB->year('start_time').' = \''.$rok.'\' ' : '')
		.(!is_null($msc) ? ' AND '.$this->DB->month('start_time').' = \''.$msc.'\' ' : '')
		.(!is_null($calltype) ? ' AND calltype=\''.$calltype.'\' ' : '') 
		.(!is_null($call_success) ? ' AND success_call = \''.$call_success.'\' ' : '')
		.(!is_null($terminal) ? ' AND terminal_name = \''.$terminal.'\' ': '')
		.' GROUP BY  msc, rok ORDER BY rok DESC, msc DESC ;';
	return $this->DB->GetAll($zap);
    }

	public function GetListBillingByCustomer2($customerid, $year = NULL, $month = NULL, $terminal = NULL) {
		$call_success = NULL;
		if (!is_null($terminal) && empty($terminal)) $terminal = NULL;
		if (!is_null($year) && empty($year)) $year = NULL;
		if (!is_null($month) && empty($month)) $month = NULL;
		$subscription = $this->GetSubscriptionByTerminalName($terminal);
		$query = 'SELECT SUM(b.cost) AS cost, SUM(b.init_charge) AS init_charge,
			SUM(b.reseller_cost) AS reseller_cost, SUM(b.reseller_init_charge) AS reseller_init_charge,
			' . $this->DB->Month('b.start_time') . ' AS month,
			' . $this->DB->Year('b.start_time') . ' AS year,
			? AS subscription
			FROM hv_billing b
			WHERE customerid = ? '
			. (!is_null($terminal) ? ' AND terminal_name = \''.$terminal.'\' ': '')
			. (!is_null($year) ? ' AND ' . $this->DB->Year('start_time') . ' = \'' . $year . '\' ' : '')
			. (!is_null($month) ? ' AND ' . $this->DB->Month('start_time') . ' = \'' . $month . '\' ' : '')
			.' GROUP BY month, year ORDER BY year DESC, month DESC';
		return $this->DB->GetAll($query, array($subscription, $customerid));
	}

	public function GetBillingByCustomer($customerid, $year = NULL, $month = NULL, $calltype = NULL, $callsuccess = NULL, $terminal = NULL) {
		$call_success = NULL;
		if (!is_null($year) && empty($year)) $year = NULL;
		if (!is_null($month) && empty($month)) $month = NULL;
		if (!is_null($calltype) && empty($calltype)) $calltype = NULL;
		if (!is_null($callsuccess) && empty($callsuccess)) $callsuccess = NULL;
		if (!is_null($terminal) && empty($terminal)) $terminal = NULL;
		if (!is_null($callsuccess)) {
			if (is_bool($callsuccess)) {
				if ($callsuccess === true) $call_success = 't';
				elseif ($callsuccess === false) $call_success = 'f';
				else $call_success = NULL;
			} elseif (is_string($callsuccess) === true) {
				if (strtolower($callsuccess) == 't') $call_success = 't';
				elseif (strtolower($callsuccess) == 'f') $call_success = 'f';
				else $call_success = NULL;
			} else $call_success = NULL;
		} else $call_success = NULL;

		$query = 'SELECT * FROM hv_billing
			WHERE customerid = ? '
			.(!is_null($terminal) ? ' AND terminal_name = \'' . $terminal . '\'' : '')
			.(!is_null($year) ? ' AND '.$this->DB->Year('start_time') . ' = \'' . $year . '\'' : '')
			.(!is_null($month) ? ' AND '.$this->DB->Month('start_time') . ' = \'' . $month . '\'' : '')
			.(!is_null($calltype) ? ' AND calltype = \'' . $calltype . '\'' : '')
			.(!is_null($call_success) ? ' AND success_call = \'' . $call_success . '\'' : '')
			. ' ORDER BY start_time DESC';
		return $this->DB->GetAll($query, array($customerid));
	}

    function GetSubscriptionByTerminalName($terminal=NULL)
    {
	if (is_null($terminal) || !is_string($terminal)) return false;
	return $this->DB->GetOne('SELECT COALESCE(s.invoice_value,0) AS value 
				FROM hv_terminal AS t 
				LEFT JOIN hv_subscriptionlist AS s ON (s.id = t.id_subscription) 
				WHERE t.username = ? LIMIT 1',array($terminal));
    }


    function GetListProvince($active=NULL)
    {
	return $this->DB->GetAll('SELECT id, name FROM hv_province ;');
    }
    
    function GetListCountyByProvince($id)
    {
	return $this->DB->GetAll('SELECT hv_county.id, hv_county.name FROM hv_county JOIN hv_pcb ON (hv_county.id = hv_pcb.county) WHERE hv_pcb.province=? GROUP BY hv_county.name, hv_county.id ORDER BY hv_county.name ASC ;',array($id));
    }
    
    function GetListBoroughByCounty($id)
    {
	return $this->DB->GetAll('SELECT hv_borough.id, hv_borough.name FROM hv_borough JOIN hv_pcb ON (hv_borough.id = hv_pcb.borough) WHERE hv_pcb.county=? GROUP BY hv_borough.name, hv_borough.id ORDER BY hv_borough.name ASC ;',array($id));
	
    }
    
    
    
    function GetNameProvince($id)
    {
	return $this->DB->GetOne('SELECT name FROM hv_province WHERE id=? LIMIT 1',array($id));
    }
    
    function GetNameCounty($id)
    {
	return $this->DB->GetOne('SELECT name FROM hv_county WHERE id=? LIMIT 1',array($id));
    }
    
    function GetNameBorough($id)
    {
	return $this->DB->GetOne('SELECT name FROM hv_borough WHERE id=? LIMIT 1',array($id));
    }


    function GetPSTNOneOrList($pstnid=NULL,$customerid=NULL)
    {
	if (!is_null($pstnid)) return $this->DB->GetRow('SELECT t.* FROM hv_pstn AS t WHERE t.id=? ;',array($pstnid));
	elseif (!is_null($customerid)) return $this->DB->GetAll('SELECT t.* FROM hv_pstn AS t WHERE t.customerid=? ;',array($customerid));
	else return $this->DB->GetAll('SELECT t.* FROM hv_pstn AS t ;');
    }
    
    function GetPstnRangeList($active=false)
    {
	if (!is_bool($active)) $active = false;
	if ($active === true) $tmp = ' WHERE ussage=\'t\' ';
	else $tmp = '';
	$sql = 'SELECT pr.*, 
	    (SELECT COUNT(*) FROM hv_pstnusage WHERE hv_pstnusage.customerid!=0 AND hv_pstnusage.idrange = pr.id) AS uzyte,
	    (SELECT COUNT(*) FROM hv_pstnusage WHERE hv_pstnusage.idrange = pr.id) AS ilosc 
	     FROM hv_pstnrange AS pr '.$tmp.' ORDER BY range_start ASC ;';
	return $this->DB->GetAll($sql);
    }
    

    function GetPSTNInfoList($id,$empty=false)
    {
	$id = intval($id);
	$zap = 'SELECT * FROM hv_pstnusage WHERE idrange='.$id.' '.($empty ? 'AND customerid=0 ' : '').' ORDER BY number ASC ;';
	return $this->DB->GetAll($zap);
    }
    
    
    function UpdatePSTN($dane=NULL)
    {
	if (is_null($dane) || !is_array($dane)) return false;
	$old_extenstion = $dane['oldpstn'];
	$customer_id = $dane['customerid'];
	unset($dane['oldpstn']);
	unset($dane['customerid']);
	if ($this->HP_ChangePSTNNumberData($dane))
	{
	    $this->ImportTerminalList($customer_id);
	    $this->ImportPSTNList($customer_id);
	    if ($lista = $this->GetPSTNOneOrList(NULL,$customer_id))
	    {
		$cusname = $this->DB->GetOne('SELECT name FROM hv_customers WHERE id='.$customer_id.' LIMIT 1 ;');
		$this->DB->Execute('UPDATE hv_pstnusage SET customerid=0, customer_name=NULL WHERE customerid='.$customer_id.' ;');
		for ($i=0;$i<count($lista);$i++) $this->DB->Execute('UPDATE hv_pstnusage SET customerid='.$customer_id.' , customer_name=\''.$cusname.'\' WHERE extension=\''.$lista[$i]['extension'].'\' ;');
	    }
	    return true;
	}
    }
    

    function DeletePSTNCustomer($dane=NULL)
    {
	if (is_null($dane) ) return false;
	$customer_id = $this->DB->GetOne('SELECT customerid FROM hv_pstn WHERE id=? ;',array($dane));
	if (HiperusActions::DelPSTNNumber($dane))
	{
	    $cusname = $this->DB->GetOne('SELECT name FROM hv_customers WHERE id=? LIMIT 1 ;',array($customer_id));
	    $this->DB->Execute('DELETE FROM hv_pstn WHERE id=? ;',array($dane));
	    $this->ImportTerminalList($customer_id);
	    $this->ImportPSTNList($customer_id);
	    $this->DB->Execute('UPDATE hv_pstnusage SET customerid=0, customer_name=NULL WHERE customerid='.$customer_id.' ;');
	    if ($lista = $this->GetPSTNOneOrList(NULL,$customer_id))
	    {
		for ($i=0;$i<count($lista);$i++) $this->DB->Execute('UPDATE hv_pstnusage SET customerid='.$customer_id.' , customer_name=\''.$cusname.'\' WHERE extension=\''.$lista[$i]['extension'].'\' ;');
	    }
	    return true;
	}
    }

	public function ImportPSTNList($cid = NULL) {
		if (is_null($cid)) 
			$cids = $this->DB->GetCol('SELECT id FROM hv_customers ORDER BY id');
		else
			$cids = array($cid);
		$nids = array();
		if (is_array($cids) && !empty($cids))
			foreach ($cids as $cid) {
				$numbers = HiperusActions::GetPSTNNumberList($cid);
				sleep(2);
				if (!is_array($numbers) || empty($numbers))
					continue;
				foreach ($numbers as $number)
					if (!is_null($number['id'])) {
						if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_pstn WHERE id=?',
							array($number['id'])))
							$this->DB->Execute('INSERT INTO hv_pstn (id,customerid,extension,country_code,number,is_main,disa_enabled,clir,virtual_fax,terminal_name,id_auth,create_date,voicemail_enabled)
								VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
								array(
									$number['id'], $cid,
									(!empty($number['extension']) ? $number['extension'] : NULL),
									(!empty($number['country_code']) ? $number['country_code'] : '48'),
									(!empty($number['number']) ? $number['number'] : NULL),
									$number['is_main'],
									$number['disa_enabled'],
									$number['clir'],
									$number['virtual_fax'],
									(!empty($number['terminal_name']) ? $number['terminal_name'] : NULL),
									(!empty($number['id_auth']) ? $number['id_auth'] : NULL),
									(!empty($number['create_date']) ? $number['create_date'] : NULL),
									$number['voicemail_enabled']));
						else
							$this->DB->Execute('UPDATE hv_pstn SET customerid=?, extension=?, country_code=?, number=?, is_main=?, disa_enabled=?, clir=?, virtual_fax=?, 
								terminal_name=?, id_auth=?, create_date=?, voicemail_enabled=? WHERE id=? ;',
								array(
									$cid,
									(!empty($number['extension']) ? $number['extension'] : NULL),
									(!empty($number['country_code']) ? $number['country_code'] : '48'),
									(!empty($number['number']) ? $number['number'] : NULL),
									$number['is_main'],
									$number['disa_enabled'],
									$number['clir'],
									$number['virtual_fax'],
									(!empty($number['terminal_name']) ? $number['terminal_name'] : NULL),
									(!empty($number['id_auth']) ? $number['id_auth'] : NULL),
									(!empty($number['create_date']) ? $number['create_date'] : NULL),
									$number['voicemail_enabled'],
									$number['id']));
						$nids[] = $number['id'];
					}
			}
		$this->DB->Execute('DELETE FROM hv_pstn' . (empty($nids) ? '' : ' WHERE id NOT IN (' . implode(',', $nids) . ')'));
	}
    
	public function ImportPSTNRangeList() {
		$hlib = new HiperusLib();
		$r = new stdClass();
		$response = $hlib->sendRequest("GetPlatformNumberingRange",$r);
		if  (!$response || !$response->success) $ranges = array();
		else $ranges = $response->result_set;
		if (is_null($ranges) || !is_array($ranges) || empty($ranges))
			return;

		$this->DB->Execute('DELETE FROM hv_pstnrange');
		foreach ($ranges as $range)
			$this->DB->Execute('INSERT INTO hv_pstnrange (id,range_start,range_end,description,id_reseller,country_code,open_registration) VALUES (?,?,?,?,?,?,?)',
				array(
				    $range['id'],
				    $range['range_start'],
				    $range['range_end'],
				    $range['description'],
				    $range['id_reseller'],
				    $range['country_code'],
				    $range['open_registration']));
	}

	public function ImportPSTNUsageList() {
		$this->DB->Execute('DELETE FROM hv_pstnusage');
		$ranges = $this->DB->GetCol('SELECT id FROM hv_pstnrange');
		if (!is_array($ranges) || empty($ranges))
			return;
		foreach ($ranges as $range) {
			$hlib = new HiperusLib();
			$r = new stdClass();
			$r->id_platform_numbering = $range;
			$response = $hlib->sendRequest("GetPlatformNumberingUsage",$r);
			if (!$response || !$response->success) $usages = array();
			else $usages = $response->result_set;
			sleep(2);
			if (!is_array($usages) || empty($usages))
				continue;

			foreach ($usages as $usage) {
				if (!isset($usage['id_customer']) || is_null($usage['id_customer']) || empty($usage['id_customer']))
					$customerid = 0;
				else
					$customerid = $usage['id_customer'];
				if (!isset($usage['customer_name']) || is_null($usage['customer_name']) || empty($usage['customer_name']))
					$customername = NULL;
				else
					$customername = $usage['customer_name'];
				$this->DB->Execute('INSERT INTO hv_pstnusage (extension,number,customerid,country_code,customer_name,idrange) VALUES (?,?,?,?,?,?)',
					array($usage['extension'],
						$usage['number'],
						$customerid,
						$usage['country_code'],
						$customername,
						$range));
			}
		}
	}

    function AddPstnForTerminal($dane) 
    {
	$id_customer = $dane['id_customer'];
        
        $number_data = array();
        $number_data['number'] = $dane['number'];
        $number_data['country_code'] = $dane['country_code'];
    
        if ($dane['is_main']=='t') $number_data['is_main'] = true; else $number_data['is_main'] = false;
        if ($dane['disa_enabled']=='t') $number_data['disa_enabled'] = true; else $number_data['disa_enabled'] = false;
        if ($dane['clir']=='t') $numer_data['clir'] = true; else $number_data['clir'] = false;
        if ($dane['virtual_fax']=='t') $number_data['virtual_fax'] = true; else $number_data['virtual_fax'] = false;
        if ($dane['voicemail_enabled']=='t') $number_data['voicemail_enabled'] = true; else $number_data['voicemail_enabled'] = false;
    
        $terminal_data = array();
        $terminal_data['id_terminal'] = $dane['id_terminal'];
    
        if (HiperusActions::CreatePSTNNumber($id_customer,$number_data,$terminal_data))
        {
		$this->ImportTerminalList($id_customer);
		$this->ImportPSTNList($id_customer);
		$cusname = $this->DB->GetRow('SELECT id, name FROM hv_customers WHERE id='.$id_customer.' LIMIT 1 ;');
		$this->DB->Execute('UPDATE hv_pstnusage SET customerid=?, customer_name=? WHERE number=? ;',array($cusname['id'],$cusname['name'],$number_data['number']));
		return true;
        } else return false;
        
    }



    function getcustomerexists($id)
    {
	return ($this->DB->GetOne('SELECT id FROM hv_customers WHERE id=? LIMIT 1;',array($id)) ? TRUE : FALSE);
    }
    
    function DelCustomer($id)
    {
	if ($return=HiperusActions::DelCustomer($id))
	{
	    $this->DB->Execute('DELETE FROM hv_billing WHERE customerid=? AND (ext_billing_id=0 OR ISNULL(ext_billing_id)) ;',array($id));
	    $this->DB->Execute('UPDATE hv_pstnusage SET customerid=0, customer_name=? WHERE customerid=? ;',array('',$id));
	    $this->DB->Execute('DELETE FROM hv_customers WHERE id=? ;',array($id));
	    $this->DB->Execute('DELETE FROM hv_assign WHERE customerid=? ;',array($id));
	    $this->DB->Execute('DELETE FROM hv_enduserlist WHERE customerid=? ;',array($id));
	    $this->DB->Execute('DELETE FROM hv_terminal WHERE customerid=? ;',array($id));
	    $this->DB->Execute('DELETE FROM hv_pstn WHERE customerid=? ;',array($id));
	    
	    return true;
	} else return false;
    }

	public function ImportCustomerList() {
		$customers = HiperusActions::GetCustomerList();
		if (!is_array($customers) || empty($customers))
			return;
		$cids = array();
		$invoice_source = intval(ConfigHelper::getConfig('hiperus_c5.invoice_source', 0));
		foreach ($customers as $customer)
			if (!is_null($customer['id'])) {
				if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_customers WHERE id=?', array($customer['id']))) {
					$this->DB->Execute('INSERT INTO hv_customers (id,name,id_reseller,email,address,street_number,apartment_number,postcode,city,country,b_name,b_address,b_street_number,b_apartment_number,
						b_postcode,b_city,b_country,b_nip,b_regon,ext_billing_id,issue_invoice,id_default_pricelist,id_default_balance,payment_type,is_wlr,active,create_date,
						consent_data_processing,platform_user_add_stamp,open_registration,is_removed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ;',
						array(
							$customer['id'],
							(!empty($customer['name']) ? $customer['name'] : NULL ),
							(!empty($customer['id_reseller']) ? $customer['id_reseller'] :NULL ),
							(!empty($customer['email']) ? $customer['email'] :NULL ),
							(!empty($customer['address']) ? $customer['address'] : NULL ),
							(!empty($customer['street_number'])?$customer['street_number']:NULL),
							(!empty($customer['apartment_number'])?$customer['apartment_number']:NULL),
							(!empty($customer['postcode']) ? $customer['postcode' ]:NULL),
							(!empty($customer['city'])?$customer['city']:NULL),
							(!empty($customer['country'])?$customer['country']:NULL),
							(!empty($customer['b_name'])?$customer['b_name']:NULL),
							(!empty($customer['b_address'])?$customer['b_address']:NULL),
							(!empty($customer['b_street_number'])?$customer['b_street_number']:NULL),
							(!empty($customer['b_apartment_number'])?$customer['b_apartment_number']:NULL),
							(!empty($customer['b_postcode'])?$customer['b_postcode']:NULL),
							(!empty($customer['b_city'])?$customer['b_city']:NULL),
							(!empty($customer['b_country'])?$customer['b_country']:NULL),
							(!empty($customer['b_nip'])?$customer['b_nip']:NULL),
							(!empty($customer['b_regon'])?$customer['b_regon']:NULL),
							(!empty($customer['ext_billing_id']) ? $customer['ext_billing_id'] : NULL),
							(!empty($customer['issue_invoice'])?$customer['issue_invoice']:'f'),
							(!empty($customer['id_default_pricelist'])?$customer['id_default_pricelist']:NULL),
							(!empty($customer['id_default_balance'])?$customer['id_default_balance']:NULL),
							(!empty($customer['payment_type'])?$customer['payment_type']:'postpaid'),
							(!empty($customer['is_wlr'])?$customer['is_wlr']:'f'),
							(!empty($customer['active'])?$customer['active']:'t'),
							(!empty($customer['create_date'])?$customer['create_date']:NULL),
							(!empty($customer['consent_data_processing'])?$customer['consent_data_processing']:'f'),
							(!empty($customer['platform_user_add_stamp'])?$customer['platform_user_add_stamp']:NULL),
							(!empty($customer['open_registration'])?$customer['open_registration']:'f'),
							(!empty($customer['is_removed'])?$customer['is_removed']:'f')));
					$issue_invoice = ($customer['issue_invoice'] == 'f' ? 0 : $invoice_source);
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_assign WHERE customerid=? AND keytype=?',
						array($customer['id'], 'issue_invoice')))
						$this->DB->Execute('INSERT INTO hv_assign (customerid,keytype,keyvalue) VALUES (?,?,?)',
							array($customer['id'],'issue_invoice', $issue_invoice));
					else
						$this->DB->Execute('UPDATE hv_assign SET keyvalue=? WHERE customerid=? AND keytype=?',
							array($issue_invoice, $customer['id'], 'issue_invoice'));
				} else {
					$this->DB->Execute('UPDATE hv_customers SET name=?, id_reseller=?, email=?, address=?, street_number=?, apartment_number=?, postcode=?, city=?,country=?, b_name=?, b_address=?, 
						b_street_number=?, b_apartment_number=?, b_postcode=?, b_city=?, b_country=?, b_nip=?, b_regon=?, ext_billing_id=?, issue_invoice=?, id_default_pricelist=?, 
						id_default_balance=?, payment_type=?, is_wlr=?, active=?, create_date=?, consent_data_processing=?, platform_user_add_stamp=?, 
						open_registration=?, is_removed=? WHERE id=? ;',
						array(
							(!empty($customer['name']) ? $customer['name'] : NULL ),
							(!empty($customer['id_reseller']) ? $customer['id_reseller'] :NULL ),
							(!empty($customer['email']) ? $customer['email'] :NULL ),
							(!empty($customer['address']) ? $customer['address'] : NULL ),
							(!empty($customer['street_number'])?$customer['street_number']:NULL),
							(!empty($customer['apartment_number'])?$customer['apartment_number']:NULL),
							(!empty($customer['postcode']) ? $customer['postcode' ]:NULL),
							(!empty($customer['city'])?$customer['city']:NULL),
							(!empty($customer['country'])?$customer['country']:NULL),
							(!empty($customer['b_name'])?$customer['b_name']:NULL),
							(!empty($customer['b_address'])?$customer['b_address']:NULL),
							(!empty($customer['b_street_number'])?$customer['b_street_number']:NULL),
							(!empty($customer['b_apartment_number'])?$customer['b_apartment_number']:NULL),
							(!empty($customer['b_postcode'])?$customer['b_postcode']:NULL),
							(!empty($customer['b_city'])?$customer['b_city']:NULL),
							(!empty($customer['b_country'])?$customer['b_country']:NULL),
							(!empty($customer['b_nip'])?$customer['b_nip']:NULL),
							(!empty($customer['b_regon'])?$customer['b_regon']:NULL),
							(!empty($customer['ext_billing_id']) ? $customer['ext_billing_id'] : NULL),
							(!empty($customer['issue_invoice'])?$customer['issue_invoice']:'f'),
							(!empty($customer['id_default_pricelist'])?$customer['id_default_pricelist']:NULL),
							(!empty($customer['id_default_balance'])?$customer['id_default_balance']:NULL),
							(!empty($customer['payment_type'])?$customer['payment_type']:'postpaid'),
							(!empty($customer['is_wlr'])?$customer['is_wlr']:'f'),
							(!empty($customer['active'])?$customer['active']:'t'),
							(!empty($customer['create_date'])?$customer['create_date']:NULL),
							(!empty($customer['consent_data_processing'])?$customer['consent_data_processing']:'f'),
							(!empty($customer['platform_user_add_stamp'])?$customer['platform_user_add_stamp']:NULL),
							(!empty($customer['open_registration'])?$customer['open_registration']:'f'),
							(!empty($customer['is_removed'])?$customer['is_removed']:'f'),
							$customer['id']));
					$issue_invoice = ($customer['issue_invoice'] == 'f' ? 0 : $invoice_source);
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_assign WHERE customerid=? AND keytype=?',
							array($customer['id'],'issue_invoice')))
						$this->DB->Execute('INSERT INTO hv_assign (customerid,keytype,keyvalue) VALUES (?,?,?)',
							array($customer['id'], 'issue_invoice', $issue_invoice));
					else
						$this->DB->Execute('UPDATE hv_assign SET keyvalue=? WHERE customerid=? AND keytype=?',
							array($issue_invoice, $customer['id'], 'issue_invoice'));
				}
				$cids[] = $customer['id'];
			}
		$this->DB->Execute('DELETE FROM hv_assign WHERE keytype = ?' . (empty($cids) ? '' : ' AND customerid NOT IN (' . implode(',', $cids) . ')'),
			array('issue_invoice'));
		$this->DB->Execute('DELETE FROM hv_customers' . (empty($cids) ? '' : ' WHERE id NOT IN (' . implode(',', $cids) . ')'));
	}

    function AddCustomer($dane)
    {
	if (!is_array($dane)) return false;

	if ($dane['invoice']=='1') $dane['issue_invoice'] = 't'; else $dane['issue_invoice'] = 'f';
	
	$tmp_invoice = $dane['invoice'];
	unset($dane['invoice']);
	    
	if ($return = HiperusActions::CreateCustomer($dane))
	{
	    $result = HiperusActions::GetCustomerData($return);
	    $this->DB->Execute('INSERT INTO hv_customers (id,name,id_reseller,email,address,street_number,apartment_number,postcode,city,country,b_name,b_address,b_street_number,b_apartment_number,
			    b_postcode,b_city,b_country,b_nip,b_regon,ext_billing_id,issue_invoice,id_default_pricelist,id_default_balance,payment_type,is_wlr,active,create_date,
			    consent_data_processing,platform_user_add_stamp,open_registration,is_removed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ;',
			    array(
				$result['id'],
				( !empty($result['name']) ? $result['name']:NULL),
				( !empty($result['id_reseller']) ? $result['id_reseller']:NULL),
				( !empty($result['email']) ? $result['email']:NULL),
				( !empty($result['address']) ? $result['address']:NULL),
				( !empty($result['street_number']) ? $result['street_number']:NULL),
				( !empty($result['apartment_number']) ? $result['apartment_number']:NULL),
				( !empty($result['postcode']) ? $result['postcode']:NULL),
				( !empty($result['city']) ? $result['city']:NULL),
				( !empty($result['country']) ? $result['country']:NULL),
				( !empty($result['b_name']) ? $result['b_name']:NULL),
				( !empty($result['b_address']) ? $result['b_address']:NULL),
				( !empty($result['b_street_number']) ? $result['b_street_number']:NULL),
				( !empty($result['b_apartment_number']) ? $result['b_apartment_number']:NULL),
				( !empty($result['b_postcode']) ? $result['b_postcode']:NULL),
				( !empty($result['b_city']) ? $result['b_city']:NULL),
				( !empty($result['b_country']) ? $result['b_country']:NULL),
				( !empty($result['b_nip']) ? $result['b_nip']:NULL),
				( !empty($result['b_regon']) ? $result['b_regon']:NULL),
				( !empty($result['ext_billing_id']) ? $result['ext_billing_id'] : NULL),
				( !empty($result['issue_invoice']) ? $result['issue_invoice'] : 'f'),
				( !empty($result['id_default_pricelist']) ? $result['id_default_pricelist'] : NULL),
				( !empty($result['id_default_balance']) ? $result['id_default_balance'] : NULL),
				( !empty($result['payment_type']) ? $result['payment_type'] : 'postpaid'),
				( !empty($result['is_wlr']) ? $result['is_wlr'] : 'f'),
				( !empty($result['active']) ? $result['active'] : 't'),
				( !empty($result['create_date']) ? $result['create_date'] : NULL),
				( !empty($result['consent_data_processing']) ? $result['consent_data_processing'] : 'f'),
				( !empty($result['platform_user_add_stamp']) ? $result['platform_user_add_stamp'] : NULL),
				( !empty($result['open_registration']) ? $result['open_registration'] : 'f'),
				( !empty($result['is_removed']) ? $result['is_removed'] : 'f')
			    ));
			    
	    $this->DB->Execute('INSERT INTO hv_assign (customerid,keytype,keyvalue) VALUES (?,?,?) ;',array($result['id'],'issue_invoice',$tmp_invoice));
	    return $return;
	}

    }
    
    function GetCustomer($id)
    {
	return $this->DB->GetRow('SELECT h.*, (SELECT keyvalue FROM hv_assign WHERE customerid=h.id AND keytype=?) AS invoice FROM hv_customers AS h WHERE id=? LIMIT 1 ;',array('issue_invoice',$id));
    }
    
    function UpdateCustomer($dane)
    {
	
	$invoice_source = intval(ConfigHelper::getConfig('hiperus_c5.invoice_source', 0));
	$dane['issue_invoice'] = ($dane['invoice'] == $invoice_source ? 't' : 'f');
	
	$invoice = $dane['invoice'];
	unset($dane['invoice']);
	
    	if (HiperusActions::ChangeCustomerData($dane))
	{
		$oldname = $this->DB->GetOne('SELECT name FROM hv_customers WHERE id='.$dane['id'].' LIMIT 1 ;');
		$dane = HiperusActions::GetCustomerData($dane['id']);
		$dane['invoice'] = $invoice;
		$this->DB->Execute('UPDATE hv_customers SET name=?, id_reseller=?, email=?, address=?, street_number=?, apartment_number=?, postcode=?, city=?, country=?, b_name=?, 
			    b_address=?, b_street_number=?, b_apartment_number=?, b_postcode=?,b_city=?,b_country=?,b_nip=?,b_regon=?,ext_billing_id=?,issue_invoice=?,id_default_pricelist=?,
			    id_default_balance=?,payment_type=?,is_wlr=?,active=?,create_date=?, consent_data_processing=?,platform_user_add_stamp=?,open_registration=?,is_removed=? WHERE id=? ;',
			    array(
				(!empty($dane['name']) ? $dane['name'] : NULL),
				(!empty($dane['id_reseller']) ? $dane['id_reseller'] : NULL),
				(!empty($dane['email']) ? $dane['email'] : NULL),
				(!empty($dane['address']) ? $dane['address'] : NULL),
				(!empty($dane['street_number']) ? $dane['street_number'] : NULL),
				(!empty($dane['apartment_number']) ? $dane['apartment_number'] : NULL),
				(!empty($dane['postcode']) ? $dane['postcode'] : NULL),
				(!empty($dane['city']) ? $dane['city'] : NULL),
				(!empty($dane['country']) ? $dane['country'] : NULL),
				(!empty($dane['b_name']) ? $dane['b_name'] : NULL),
				(!empty($dane['b_address']) ? $dane['b_address'] : NULL),
				(!empty($dane['b_street_number']) ? $dane['b_street_number'] : NULL),
				(!empty($dane['b_apartment_number']) ? $dane['b_apartment_number'] : NULL),
				(!empty($dane['b_postcode']) ? $dane['b_postcode'] : NULL),
				(!empty($dane['b_city']) ? $dane['b_city'] : NULL),
				(!empty($dane['b_country']) ? $dane['b_country'] : NULL),
				(!empty($dane['b_nip']) ? $dane['b_nip'] : NULL),
				(!empty($dane['b_regon']) ? $dane['b_regon'] : NULL),
				(!empty($dane['ext_billing_id']) ? $dane['ext_billing_id'] : NULL),
				(!empty($dane['issue_invoice']) ? $dane['issue_invoice'] : 'f'),
				(!empty($dane['id_default_pricelist']) ? $dane['id_default_pricelist'] : NULL),
				(!empty($dane['id_default_balance']) ? $dane['id_default_balance'] : NULL),
				(!empty($dane['payment_type']) ? $dane['payment_type'] : 'postpaid'),
				(!empty($dane['is_wlr']) ? $dane['is_wlr'] : 'f'),
				(!empty($dane['active']) ? $dane['active'] : 't'),
				(!empty($dane['create_date']) ? $dane['create_date'] : NULL),
				(!empty($dane['consent_data_processing']) ? $dane['consent_data_processing'] : 'f'),
				(!empty($dane['platform_user_add_stamp']) ? $dane['platform_user_add_stamp'] : NULL),
				(!empty($dane['open_registration']) ? $dane['open_registration'] : 'f'),
				(!empty($dane['is_removed']) ? $dane['is_removed'] : 'f'),
				$dane['id']
			    ));
		$this->DB->Execute('UPDATE hv_assign SET keytype=?, keyvalue=? WHERE customerid=? ;', array('issue_invoice',$invoice,$dane['id']));
		if ($dane['name']!==$oldname)
		{
		    $this->DB->Execute('UPDATE hv_billing SET customer_name=? WHERE customer_name=? ;',array($dane['name'],$oldname));
		    $this->DB->Execute('UPDATE hv_terminal SET customer_name=? WHERE customer_name=? ;',array($dane['name'],$oldname));
		    $this->DB->Execute('UPDATE hv_pstnusage SET customer_name=? WHERE customer_name=? ;',array($dane['name'],$oldname));
		}
		unset($oldname);
		unset($dane);
		return true;
	}
	else return false;
	
    }
    
    function GetLMSCustomerByVoIPID($id)
    {
	return $this->DB->GetRow('SELECT c.* FROM customers c JOIN hv_customers h ON (c.id = h.ext_billing_id) WHERE h.id = '.$id.' LIMIT 1');
    }
    

    function getterminalexists($id)
    {
	return ($this->DB->GetOne('SELECT id FROM hv_terminal WHERE id=? LIMIT 1;',array($id)) ? TRUE : FALSE);
    }

	public function ImportTerminalList($cid = NULL) {
		if (empty($cid))
			$cids = $this->DB->GetCol('SELECT id FROM hv_customers ORDER BY id'); 
		else
			$cids = array($cid);
		if (empty($cids))
			return;
		$tids = array();
		foreach ($cids as $cid) {
			$terminals = HiperusActions::GetTerminalList($cid);
			sleep(1);
			if (!is_array($terminals) || empty($terminals))
				continue;
			foreach ($terminals as $terminal)
				if (!is_null($terminal['id'])) {
					$tids[] = $terminal['id'];
					if (!$this->DB->GetOne('SELECT COUNT(*) FROM hv_terminal WHERE id=?', array($terminal['id'])))
						$this->DB->Execute('INSERT INTO hv_terminal (id,customerid,username,password,screen_numbers,t38_fax,customer_name,id_pricelist,pricelist_name,balance_value,id_auth,id_subscription,
						    subscription_from,subscription_to,value_left,id_terminal_location,area_code,borough,county,province,sip_proxy,subscriptions,extensions) 
						    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ;',
						    array(
							$terminal['id'],
							$cid,
							(!empty($terminal['username']) ? $terminal['username'] : NULL),
							(!empty($terminal['password']) ? $terminal['password'] : NULL),
							(!empty($terminal['screen_numbers']) ? $terminal['screen_numbers'] : 't'),
							(!empty($terminal['t38_fax']) ? $terminal['t38_fax'] : 'f'),
							(!empty($terminal['customer_name']) ? $terminal['customer_name'] : NULL),
							(!empty($terminal['id_pricelist']) ? $terminal['id_pricelist'] : NULL),
							(!empty($terminal['pricelist_name']) ? $terminal['pricelist_name'] : NULL),
							(!empty($terminal['balance_value']) ? $terminal['balance_value'] : '0.00'),
							(!empty($terminal['id_auth']) ? $terminal['id_auth'] : NULL),
							(!empty($terminal['id_subscription']) ? $terminal['id_subscription'] : NULL),
							(!empty($terminal['subscription_from']) ? $terminal['subscription_from'] : NULL),
							(!empty($terminal['subscription_to']) ? $terminal['subscription_to'] : NULL),
							(!empty($terminal['value_left']) ? $terminal['value_left'] : '0.00'), 
							(!empty($terminal['id_terminal_location']) ? $terminal['id_terminal_location'] : NULL),
							(!empty($terminal['area_code']) ? $terminal['area_code'] : NULL),
							(!empty($terminal['borough']) ? $terminal['borough'] : NULL),
							(!empty($terminal['county']) ? $terminal['county'] : NULL),
							(!empty($terminal['province']) ? $terminal['province'] : NULL),
							(!empty($terminal['sip_proxy']) ? $terminal['sip_proxy'] : NULL),
							(!empty($terminal['subscriptions']) ? $terminal['subscriptions'] : NULL),
							(!empty($terminal['extensions']) ? $terminal['extensions'] : NULL)));				
					else
						$this->DB->Execute('UPDATE hv_terminal SET customerid=?, username=?, password=?, screen_numbers=?, t38_fax=?, customer_name=?, id_pricelist=?, pricelist_name=?, 
								    balance_value=?, id_auth=?, id_subscription=?, subscription_from=?, subscription_to=?, value_left=?, id_terminal_location=?, area_code=?, borough=?, 
								    county=?, province=?, sip_proxy=?, subscriptions=?, extensions=? WHERE id=? ',
						    array(
							$cid,
							(!empty($terminal['username']) ? $terminal['username'] : NULL),
							(!empty($terminal['password']) ? $terminal['password'] : NULL),
							(!empty($terminal['screen_numbers']) ? $terminal['screen_numbers'] : 't'),
							(!empty($terminal['t38_fax']) ? $terminal['t38_fax'] : 'f'),
							(!empty($terminal['customer_name']) ? $terminal['customer_name'] : NULL),
							(!empty($terminal['id_pricelist']) ? $terminal['id_pricelist'] : NULL),
							(!empty($terminal['pricelist_name']) ? $terminal['pricelist_name'] : NULL),
							(!empty($terminal['balance_value']) ? $terminal['balance_value'] : '0.00'),
							(!empty($terminal['id_auth']) ? $terminal['id_auth'] : NULL),
							(!empty($terminal['id_subscription']) ? $terminal['id_subscription'] : NULL),
							(!empty($terminal['subscription_from']) ? $terminal['subscription_from'] : NULL),
							(!empty($terminal['subscription_to']) ? $terminal['subscription_to'] : NULL),
							(!empty($terminal['value_left']) ? $terminal['value_left'] : '0.00'), 
							(!empty($terminal['id_terminal_location']) ? $terminal['id_terminal_location'] : NULL),
							(!empty($terminal['area_code']) ? $terminal['area_code'] : NULL),
							(!empty($terminal['borough']) ? $terminal['borough'] : NULL),
							(!empty($terminal['county']) ? $terminal['county'] : NULL),
							(!empty($terminal['province']) ? $terminal['province'] : NULL),
							(!empty($terminal['sip_proxy']) ? $terminal['sip_proxy'] : NULL),
							(!empty($terminal['subscriptions']) ? $terminal['subscriptions'] : NULL),
							(!empty($terminal['extensions']) ? $terminal['extensions'] : NULL),
							$terminal['id']));
				}
			$this->DB->Execute('DELETE FROM hv_terminal WHERE customerid = ?' . (empty($tids) ? '' : ' AND id NOT IN (' . implode(',', $tids) . ')'),
				array($cid));
		}
	}

	public function GetTerminalOneOrList($terminalid = NULL, $customerid = NULL, $sort = NULL, $filter = NULL) {
		if (!is_null($terminalid))
			return $this->DB->GetRow('SELECT t.* FROM hv_terminal AS t WHERE t.id = ?', array($terminalid));
		elseif (!is_null($customerid))
			return $this->DB->GetAll('SELECT t.* FROM hv_terminal AS t WHERE t.customerid = ?', array($customerid));
		else {
			if (is_null($sort) || !is_string($sort)) $sort = 'id,asc';
			switch ($sort) {
				case 'id,asc':
					$sort = ' ORDER BY t.id ASC'; break;
				case 'id,desc':
					$sort = ' ORDER BY t.id DESC'; break;
				case 'numbers,asc':
					$sort = ' ORDER BY t.extensions ASC'; break;
				case 'numbers,desc':
					$sort = ' ORDER BY t.extensions DESC'; break;
				case 'username,asc':
					$sort = ' ORDER BY t.username ASC'; break;
				case 'username,desc':
					$sort = ' ORDER BY t.username DESC'; break;
				case 'customername,asc':
					$sort = ' ORDER BY t.customer_name ASC'; break;
				case 'customername,desc':
					$sort = ' ORDER BY t.customer_name DESC'; break;
				default:
					$sort = ' ORDER BY t.id ASC'; break;
			}

			$price = '';
			$subscription = '';

			if (!is_null($filter) && is_array($filter)) {
				if (isset($filter['price'])) {
					if ($filter['price'] == 'noprice') $price = ' AND t.id_pricelist IS NULL';
					elseif ($filter['price'] == '') $price = ' ';
					else $price = ' AND t.id_pricelist = ' . $filter['price'];
				}

				if (isset($filter['subscription'])) {
					if ($filter['subscription'] == 'nosubscription') $subscription = ' AND t.id_subscription IS NULL';
					elseif ($filter['subscription'] == '') $subscription = ' ';
					else $subscription = ' AND t.id_subscription = ' . $filter['subscription'];
				}
			}

			$sql = 'SELECT t.* FROM hv_terminal AS t WHERE 1=1'
				. $price
				. $subscription
				. $sort;
			return $this->DB->GetAll($sql);
		}
	}

	public function GetIDLocationTerminal($p, $c, $b) {
		return $this->DB->GetOne('SELECT id FROM hv_pcb WHERE province = ? AND county = ? AND borough = ?',
			array($p, $c, $b));
	}

	public function CreateTerminal($terminal) {
		extract($terminal);
		if (!isset($screen))
			$screen = null;
		if (!isset($t38))
			$t38 = null;
		if (!isset($subscription_id))
			$subscription_id = null;
		if (!isset($subscription_from))
			$subscription_from = null;
		if (!isset($subscription_to))
			$subscription_to = null;
		if (!isset($id_terminal_location))
			$id_terminal_location = null;

		$hlib = new HiperusLib();
		$req = new stdClass();
		$req->id_customer = $customer_id;
		$req->username = $username;
		$req->password = $password;
		$req->id_pricelist = $id_pricelist;
		if (is_null($screen)) $req->screen_numbers = true;
		if (!is_bool($screen)) {
			$screen = strtolower($screen);
			if ($screen == 't') $req->screen_numbers = true;
			elseif ($screen == 'f') $req->screen_numbers = false;
			else $req->screen_numbers = true;
		}
		if (is_null($t38)) $req->t38_fax = false;
		if (!is_bool($t38)) {
			$t38 = strtolower($t38);
			if ($t38 == 't') $req->t38_fax = true;
			elseif ($t38 == 'f') $req->t38_fax = false;
			else $req->t38_fax = false;
		}
		if (!is_null($subscription_id))
			$req->id_subscription = $subscription_id;
		if (!is_null($subscription_from))
			$req->subscription_from = str_replace('/', '-', $subscription_from);
		if (!is_null($subscription_to))
			$req->subscription_to = str_replace('/', '-', $subscription_to);
		if (!is_null($id_terminal_location))
			$req->id_terminal_location = $id_terminal_location;
		$ret = $hlib->sendRequest("AddTerminal", $req);
		if (!$ret || !$ret->success || !$ret->result_set[0]->id_terminal)
			return null;
		return $ret->result_set[0]->id_terminal;
	}

	public function UpdateTerminal($terminal = null) {
		if (is_null($terminal) || !is_array($terminal)) return false;

		if (!isset($terminal['screen_numbers'])) $terminal['screen_numbers'] = true;
		if (!isset($terminal['t38_fax'])) $terminal['t38_fax'] = false;
		if (!is_bool($terminal['screen_numbers'])) {
			$terminal['screen_numbers'] = strtolower($terminal['screen_numbers']);
			if ($terminal['screen_numbers'] == 't') $terminal['screen_numbers'] = true;
			elseif($terminal['screen_numbers'] == 'f') $terminal['screen_numbers'] = false;
			else $terminal['screen_numbers'] = true;
		}
		if (!is_bool($terminal['t38_fax'])) {
			$terminal['t38_fax'] = strtolower($terminal['t38_fax']);
			if ($terminal['t38_fax'] == 't') $terminal['t38_fax'] = true;
			elseif($terminal['t38_fax'] == 'f') $terminal['t38_fax'] = false;
			else $terminal['t38_fax'] = true;
		}

		$this->DB->Execute('UPDATE hv_terminal SET location = ?, location_city = ?, location_street = ?,
			location_house = ?, location_flat = ? WHERE id = ?', array($terminal['location'],
			$terminal['location_city'], $terminal['location_street'], $terminal['location_house'],
			$terminal['location_flat'], $terminal['id_terminal']));
		if (HiperusActions::ChangeTerminalData($terminal)) {
			$oldname = $this->DB->GetOne('SELECT username FROM hv_terminal WHERE id = ?', array($terminal['id_terminal']));
			if ($terminal['username'] !== $oldname) {
				$this->DB->Execute('UPDATE hv_billing SET terminal_name = ? WHERE terminal_name = ?',
					array($terminal['username'], $oldname));
				$this->DB->Execute('UPDATE hv_pstn SET terminal_name = ? WHERE terminal_name = ?',
					array($terminal['username'], $oldname));
				$this->DB->Execute('UPDATE hv_terminal SET username = ? WHERE username = ?',
					array($terminal['username'], $oldname));
			}
			return true;
		} else
			return false;
	}

	public function DeleteTerminal($id = null) {
		if (is_null($id))
			return false;
		$pstn = $this->DB->GetOne('SELECT extensions FROM hv_terminal WHERE id = ?', array($id));
		$id = intval($id);
		$numbers = explode("\n", $pstn);
		array_pop($numbers);
		if (HiperusActions::DelTerminal($id)) {
			foreach ($numbers as $number) {
				$this->DB->Execute('UPDATE hv_pstnusage SET customerid = ?, customer_name = ? WHERE extension = ?',
					array(0, null, $number));
				$this->DB->Execute('DELETE FROM hv_pstn WHERE extension = ?', array($number));
			}
			$this->DB->Execute('DELETE FROM hv_terminal WHERE id = ?', array($id));
		}
	}

    function GetInvoiceList()
    {
	$hlib = new HiperusLib();
        $r = new stdClass();
        $response = $hlib->sendRequest("GetInvoiceList",$r);
        
        if(!$response || !$response->success) return false;
	    else return $response->result_set;
    }
    
	public function GetConfigHiperus()
	{
		$hlib = new HiperusLib();
		$r = new stdClass();
		$response = $hlib->sendRequest("CheckLogin", $r);
		if (!$response || !$response->success)
			return false;
		else  {
			$result = $response->result_set[0];

			if ($this->DB->GetOne('SELECT 1 FROM uiconfig WHERE section=? AND var=?', array('hiperus_c5', 'voip_services')))
				$this->DB->Execute('UPDATE uiconfig SET value=? WHERE section=? AND var=?',
					array($result['voip_services'] ? 1 : 0, 'hiperus_c5', 'voip_services'));
			else
				$this->DB->Execute('INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)',
					array('hiperus_c5','voip_services', $result['voip_services'] ? 1 : 0));

			if ($this->DB->GetOne('SELECT 1 FROM uiconfig WHERE section=? AND var=?', array('hiperus_c5', 'wlr')))
				$this->DB->Execute('UPDATE uiconfig SET value=? WHERE section=? AND var=?',
					array($result['wlr_services'] ? 1 : 0, 'hiperus_c5', 'wlr'));
			else
				$this->DB->Execute('INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)',
					array('hiperus_c5', 'wlr', $result['wlr_services'] ? 1 : 0));
		}
	}
    
    
    
    function userpanel_getterminalinfo($cusid)
    {
	$result = array();
	$cusid = intval($cusid);
	$hvid = $this->DB->GetOne('SELECT id FROM hv_customers WHERE ext_billing_id = '.$cusid.' LIMIT 1 ;');
	if ($lista = $this->DB->GetAll('SELECT * FROM hv_terminal WHERE customerid=? ',array($hvid))) return $lista;
	else return $result;
    }
    
    

}

?>
