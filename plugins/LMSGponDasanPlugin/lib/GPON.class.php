<?php

/*
 * LMS version 1.11-git
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

// GPON Class

class GPON {
	private $DB;			// database object
	private $AUTH;			// object from Session.class.php (session management)
	public $snmp;

	public function __construct(&$DB, &$AUTH) { // class variables setting
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;

		$options = array();
		$this->snmp = new GPON_SNMP($options, $this);
	}

	public function FlatArrayFromDB($arraydata,$keyname,$valuename)
	{
		$result=array();
		if(is_array($arraydata) && count($arraydata)>0)
		{
			foreach ($arraydata as $k=>$v)
			{
				if(is_array($v) && key_exists($keyname,$v) && key_exists($valuename,$v))
				{
					$result[$v[$keyname]]=$v[$valuename];
				}
			}
		}
		return $result;
	}

	public function Log($loglevel = 0, $what = NULL, $xid = NULL, $message = NULL, $detail = NULL) {
		$detail = str_replace("'", '"', $detail);
		if (ConfigHelper::getConfig('gpon-dasan.syslog'))
			$this->DB->Execute('INSERT INTO syslog (time, userid, level, what, xid, message, detail)
				VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $what, $xid, $message, $detail));
	}

	//--------------OLT----------------
	function GponOltAdd($gponoltdata)
	{
		if ($this->DB->Execute('INSERT INTO gponolt (snmp_version,snmp_description,snmp_host,snmp_community,snmp_auth_protocol,snmp_username,snmp_password,snmp_sec_level,snmp_privacy_passphrase,snmp_privacy_protocol) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				array(
					$gponoltdata['snmp_version'],
					$gponoltdata['snmp_description'],
					$gponoltdata['snmp_host'],
					$gponoltdata['snmp_community'],
					$gponoltdata['snmp_auth_protocol'],
					$gponoltdata['snmp_username'],
					$gponoltdata['snmp_password'],
					$gponoltdata['snmp_sec_level'],
					$gponoltdata['snmp_privacy_passphrase'],
					$gponoltdata['snmp_privacy_protocol']
		))) {
		
			$id = $this->DB->GetLastInsertID('gponolt');			
			$dump = var_export($gponoltdata, true);
			$this->Log(4, 'gponolt', $id, 'added '. $gponoltdata['snmp_host'], $dump);
			return $id;
		}
		else
			return FALSE;
	}
	
	function GetGponOlt($id)
	{
		$result = $this->DB->GetRow('SELECT g.*
			FROM gponolt g
			WHERE g.id = ?', array($id));
		return $result;
	}
	
	function GponOltUpdate($gponoltdata)
	{
		$dump = var_export($gponoltdata, true);
		$this->Log(4, 'gponolt', $gponoltdata['gponoltid'], 'updated '. $gponoltdata['snmp_host'], $dump);

		$this->DB->Execute('UPDATE gponolt SET snmp_version=?,snmp_description=?,snmp_host=?,snmp_community=?,snmp_auth_protocol=?,snmp_username=?,snmp_password=?,snmp_sec_level=?,snmp_privacy_passphrase=?,snmp_privacy_protocol=?
				WHERE id=?', 
				array( 
					$gponoltdata['snmp_version'],
					$gponoltdata['snmp_description'],
					$gponoltdata['snmp_host'],
					$gponoltdata['snmp_community'],
					$gponoltdata['snmp_auth_protocol'],
					$gponoltdata['snmp_username'],
					$gponoltdata['snmp_password'],
					$gponoltdata['snmp_sec_level'],
					$gponoltdata['snmp_privacy_passphrase'],
					$gponoltdata['snmp_privacy_protocol'],
					$gponoltdata['gponoltid']
				));
	}
	function DeleteGponOlt($id)
	{
		$this->DB->BeginTrans();
		$gponoltid=$this->DB->GetOne('SELECT gponoltid FROM netdevices WHERE id=?', array($id));
		$this->DB->Execute('DELETE FROM gponolt WHERE id=?', array($gponoltid));
		$this->DB->Execute('DELETE FROM gponoltports WHERE gponoltid=?', array($gponoltid));
		$this->Log(4, 'gponolt', $gponoltid, 'deleted, devid: '.$id);
		$this->DB->CommitTrans();
	}
	function GponOltPortsAdd($gponoltportsdata)
	{
		if(is_array($gponoltportsdata) && count($gponoltportsdata)>0)
		{
			$logolt=0;
			foreach($gponoltportsdata as $v)
			{
				$this->DB->Execute('INSERT INTO gponoltports (gponoltid,numport,maxonu) 
						VALUES (?, ?, ?)', 
						array(
							$v['gponoltid'],
							$v['numport'],
							$v['maxonu']	
				));
				$logolt=$v['gponoltid'];
			}
			$dump = var_export($gponoltportsdata, true);
			$this->Log(4, 'gponolt', $logolt, 'ports added', $dump);
		}
	}
	function GetGponOltPorts($gponoltid)
	{
		if($result = $this->DB->GetAll('SELECT gp.*, nd.model,
		    (SELECT COUNT(go2o.gpononuid) FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE nd.gponoltid=gp.gponoltid AND go2o.numport=gp.numport) AS countlinkport
			FROM gponoltports gp
			JOIN netdevices nd ON nd.gponoltid=gp.gponoltid
			WHERE gp.gponoltid = ? ORDER BY gp.numport ASC', array($gponoltid)))
		{
		    foreach($result as $idx => $row)
		    {
			if(preg_match('/8240/', $row['model']))
				$result[$idx]['numportf'] = $this->OLT8240_format($row['numport']);
		    }
		}

		return $result;
	}
	function GponOltPortsUpdate($gponoltportsdata)
	{
		$this->DB->BeginTrans();
		if(is_array($gponoltportsdata) && count($gponoltportsdata)>0)
		{
			$countport=$this->DB->GetOne('SELECT COUNT(numport) as cn FROM gponoltports WHERE gponoltid=?', array($gponoltportsdata[1]['gponoltid']));
			if(count($gponoltportsdata)<$countport)
			{
				$this->DB->Execute('DELETE FROM gponoltports WHERE gponoltid=? AND numport>?', 
						array(
						$gponoltportsdata[1]['gponoltid'],
							count($gponoltportsdata)	
				));
			}
			foreach($gponoltportsdata as $v)
			{
				$numport=$this->DB->GetOne('SELECT numport FROM gponoltports WHERE gponoltid=? AND numport=?', array($v['gponoltid'],$v['numport']));
				if($numport>0)
				{
					$this->DB->Execute('UPDATE gponoltports SET maxonu=?
							WHERE gponoltid=? AND numport=?', 
							array( 
								$v['maxonu'],
								$v['gponoltid'],
								$v['numport']
							));
				}
				else 
				{
					$this->DB->Execute('INSERT INTO gponoltports (gponoltid,numport,maxonu) 
						VALUES (?, ?, ?)', 
						array(
							$v['gponoltid'],
							$v['numport'],
							$v['maxonu']	
					));
				}
			}
		}
		$dump = var_export($gponoltportsdata, true);
		$this->Log(4, 'gponolt', $gponoltportsdata[1]['gponoltid'], 'ports updated', $dump);
		$this->DB->CommitTrans();
	}
	function GetGponOltPortsMaxOnu($netdevicesid,$numport)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		return $this->DB->GetOne('SELECT gop.maxonu FROM gponoltports gop
		inner join netdevices nd on nd.gponoltid=gop.gponoltid
		WHERE nd.id=? AND gop.numport=?', array($netdevicesid,$numport));
	}
	function GetGponOltPortsExists($netdevicesid,$numport)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		return $this->DB->GetOne('SELECT gop.id FROM gponoltports gop
		inner join netdevices nd on nd.gponoltid=gop.gponoltid
		WHERE nd.id=? AND gop.numport=?', array($netdevicesid,$numport));
	}
	function GetNotConnectedOlt()
	{
		return $this->DB->GetAll('SELECT distinct nd.id,nd.name
		FROM netdevices nd
		inner join gponoltports gop on gop.gponoltid=nd.gponoltid
		where gop.maxonu>(select count(id) from gpononu2olt where netdevicesid=nd.id and numport=gop.numport) ORDER BY nd.name ASC');
	}
	function GetFreeOltPort($netdevicesid)
	{
		return $this->DB->GetAll('SELECT distinct nd.id, gop.numport
		FROM netdevices nd
		inner join gponoltports gop on gop.gponoltid=nd.gponoltid
		where gop.maxonu>(select count(id) from gpononu2olt where netdevicesid=nd.id and numport=gop.numport) and nd.id=?',array($netdevicesid));
	}
	function GetGponOltConnectedNames($gponoltid)
	{
		if($list = $this->DB->GetAll('SELECT nd.*,go2o.numport
			FROM gponolt g 
			inner join netdevices nd on nd.gponoltid=g.id
			inner join gpononu2olt go2o on go2o.netdevicesid=nd.id
		    WHERE go2o.gpononuid=?', array($gponoltid)))
		{
		    foreach($list as $idx => $row)
		    {
			if(preg_match('/8240/', $row['model']))
				$list[$idx]['numportf'] = $this->OLT8240_format($row['numport']);
		    }
		}
		return $list;
	}
	function GetGponOltProfiles()
	{
		$result = $this->DB->GetAll('SELECT gop.id,gop.name 
		FROM  gponoltprofiles gop
		ORDER BY gop.name ASC');
		
		return $result;
	}
	function AddGponOltProfiles($name)
	{
		$name=trim($name);
		if(strlen($name)>0)
		{
			if($this->DB->GetOne('SELECT COUNT(id) as id FROM gponoltprofiles 
			WHERE name=?',array($name))==false)
			{
				$this->DB->Execute('INSERT INTO gponoltprofiles (name) VALUES (?)',array($name));
				$pid = $this->DB->GetLastInsertID('gponoltprofiles');
				$this->Log(4, 'gponoltprofile', $pid, 'added '.$name);
			}
		}
	}
	//--------------ONU----------------
	function DeleteGponOnu($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM gpononu WHERE id=? and id not in(select distinct gpononuid from gpononu2olt)', array($id));
		$this->Log(4, 'gpononu', $id, 'deleted');
		$this->DB->CommitTrans();
	}
	function IsGponOnuLink2olt($gpononuid)
	{
		$gpononuid=intval($gpononuid);
		return $this->DB->GetOne('SELECT COUNT(id) as liczba FROM gpononu2olt WHERE gpononuid=?',array($gpononuid));
	}
	function IsGponOnuLink($netdevicesid,$numport,$gpononuid)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		$gpononuid=intval($gpononuid);
		return $this->DB->GetOne('SELECT COUNT(id) as liczba FROM gpononu2olt 
			WHERE netdevicesid=? and numport=? and gpononuid=?',
			array($netdevicesid,$numport,$gpononuid));
	}
	function GponOnuLink($netdevicesid,$numport,$gpononuid)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		$gpononuid=intval($gpononuid);
		if($netdevicesid>0 && $numport>0 && $gpononuid>0 && !$this->IsGponOnuLink($netdevicesid,$numport,$gpononuid))
		{
			$this->Log(4, 'gpononu', $gpononuid, 'link to ' .$netdevicesid. ', port ' .$numport);

			return $this->DB->Execute('INSERT INTO gpononu2olt
					(netdevicesid,numport,gpononuid) 
					VALUES (?, ?, ?)', 
					array($netdevicesid,$numport,$gpononuid));
		}

		return FALSE;
	}
	function GponOnuUnLink($netdevicesid,$numport,$gpononuid)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		$gpononuid=intval($gpononuid);
		$this->DB->Execute('DELETE FROM gpononu2olt WHERE netdevicesid=? and numport=? and gpononuid=?',array($netdevicesid,$numport,$gpononuid));
		$this->DB->Execute('update gpononu set onuid=null,autoscript=0 WHERE id=?',array($gpononuid));
		$this->Log(4, 'gpononu', $gpononuid, 'unlink with ' .$netdevicesid. ', port ' .$numport);
	}
	function GponOnuUnLinkAll($gpononuid)
	{
		$gpononuid=intval($gpononuid);
		$this->DB->Execute('DELETE FROM gpononu2olt WHERE gpononuid=?',array($gpononuid));
		$this->Log(4, 'gpononu', $gpononuid, 'unlink with all');
	}
	function GponOnuUpdateOnuId($gpononuid,$onuid)
	{
		$gpononuid=intval($gpononuid);
		$this->DB->Execute('UPDATE gpononu SET onuid=?
				WHERE id=?', 
				array( 
					$onuid,
					$gpononuid
				));
		$this->Log(4, 'gpononu', $gpononuid, 'onuid updated:'.$onuid);
	}
	function GetGponOnuConnectedNames($netdevicesid, $order='numport,asc', $oltport=0)
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');
		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
		switch($order)
		{
		    case 'id':
			    $sqlord = ' ORDER BY id';
		    break;
		    case 'numport':
			    $sqlord = ' ORDER BY go2o.numport';
		    break;
		    case 'onuid':
			    $sqlord = ' ORDER BY onuid';
		    break;
		    default:
			    $sqlord = ' ORDER BY id';
		    break;
		}

		if(intval($oltport) > 0)
		    $where = ' AND go2o.numport='.intval($oltport);
		else
		    $where = ' ';

		if($list = $this->DB->GetAll('SELECT n.gponoltid, n.model AS oltmodel, g.*, gom.name AS model, gom.producer, go2o.numport,
		    (SELECT SUM(portscount) FROM gpononuportstype2models WHERE gpononumodelsid=g.gpononumodelsid) AS ports
			FROM gpononu g 
			inner join gpononumodels gom on gom.id=g.gpononumodelsid 
			inner join gpononu2olt go2o on go2o.gpononuid=g.id
			inner join netdevices n on n.id=go2o.netdevicesid
		    WHERE go2o.netdevicesid=? '.$where . $sqlord .' '. $direction, array($netdevicesid)))
		{
		    foreach($list as $idx => $row)
		    {
			if(preg_match('/8240/', $row['oltmodel']))
				$list[$idx]['numportf'] = $this->OLT8240_format($row['numport']);
		    }
		}

		return $list;

	}
	function GetGponOnuCustomersNames($ownerid)
	{
		$list = null;
		if($list = $this->DB->GetAll('SELECT g.*, gom.name AS model, gom.producer, n.model AS oltmodel, gp.name AS profil,
		    (SELECT SUM(portscount) FROM gpononuportstype2models WHERE gpononumodelsid=g.gpononumodelsid) AS ports,
		    (SELECT nd.name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponolt,
		    (SELECT nd.gponoltid FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltid,
		    (SELECT go2o.numport FROM gpononu2olt go2o WHERE go2o.gpononuid=g.id) AS gponoltnumport,
		    (SELECT nd.id AS name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltnetdevicesid
			FROM gpononu g 
			INNER JOIN gpononumodels gom ON gom.id=g.gpononumodelsid 
			INNER JOIN gpononu2customers g2c ON g2c.gpononuid=g.id
			LEFT JOIN gpononu2olt go2o ON go2o.gpononuid=g.id
			LEFT JOIN netdevices n ON n.id=go2o.netdevicesid
			LEFT JOIN gponoltprofiles gp ON gp.id = g.gponoltprofilesid
		WHERE g2c.customersid=?', array($ownerid)))
		{
		    foreach($list as $idx => $row)
		    {
			if(preg_match('/8240/', $row['oltmodel']))
				$list[$idx]['gponoltnumportf'] = $this->OLT8240_format($row['gponoltnumport']);
		    }
		}

		return $list;
	}
	function GetGponOnu2Customers($gpononuid)
	{
		return $this->DB->GetAll("SELECT g2c.id,c.id as customersid, (" . $this->DB->Concat('c.lastname', "' '", 'c.name') . ") as customersname 
			FROM gpononu2customers g2c
			INNER JOIN customers c On c.id=g2c.customersid
			WHERE g2c.gpononuid=? ORDER BY g2c.id ASC", array($gpononuid));
	}
	function GponOnuClearCustomers($gpononuid)
	{
		$this->DB->Execute('DELETE FROM gpononu2customers WHERE gpononuid=?', array($gpononuid));
		$this->Log(4, 'gpononu', $gpononuid, 'customers removed');
	}
	function GponOnuAddCustomer($gpononuid,$customersid)
	{
		if($gpononuid>0 && $customersid>0)
		{
			if(intval($this->DB->GetOne('SELECT COUNT(id) as liczba FROM gpononu2customers WHERE gpononuid=? AND customersid=?',array($gpononuid,$customersid)))==0)
			{
				$this->DB->Execute('INSERT INTO gpononu2customers (gpononuid,customersid) 
					VALUES (?, ?)', 
					array(
						$gpononuid,
						$customersid
				));
				$this->Log(4, 'gpononu', $gpononuid, 'customers added: '.$customersid);
			}
		}
	}
	function GetGponOnuForCustomer($ownerid)
	{
		$result = $this->DB->GetRow('SELECT g.*,gom.name AS model,(select sum(portscount) from gpononuportstype2models where gpononumodelsid=g.gpononumodelsid) as ports,gom.producer,(select nd.name from gpononu2olt go2o inner join netdevices nd on nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) as gponolt,(select go2o.numport from gpononu2olt go2o WHERE go2o.gpononuid=g.id) as gponoltnumport,(select nd.id AS name from gpononu2olt go2o inner join netdevices nd on nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) as gponoltnetdevicesid  
			FROM gpononu g
			inner join gpononumodels gom on gom.id=g.gpononumodelsid
			inner join gpononu2customers g2c on g2c.gpononuid=g.id
			WHERE g2c.customersid = ?', array($ownerid));
		
		return $result;
	}
	
	function GetGponOnuPhoneVoip($gpononuid)
	{
		$result = $this->DB->GetAll('SELECT v.id,v.phone 
		FROM  voipaccounts v
		INNER JOIN customers c ON c.id = v.ownerid
		INNER JOIN gpononu2customers g2c ON g2c.customersid = c.id
		WHERE g2c.gpononuid=?', array($gpononuid));
		
		return $result;
	}
	function GetPhoneVoip($id)
	{
		$result=array();
		if($id>0)
		{
			$result = $this->DB->GetRow('SELECT v.id,v.login,v.passwd,v.phone 
			FROM  voipaccounts v
			WHERE v.id=?', array($id));
		}
		
		return $result;
	}
	function GetPhoneVoipForCustomer($ownerid)
	{
		$result=array();
		if($ownerid>0)
		{
			$result = $this->DB->GetAll('SELECT v.id,v.phone 
			FROM  voipaccounts v
			INNER JOIN customers c ON c.id = v.ownerid
			WHERE c.id=?', array($ownerid));
		}
		
		return $result;
	}	
	function GetHostNameForCustomer($ownerid)
	{
		$result=array();
		if($ownerid>0)
		{
			$result = $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
			FROM nodes n
			INNER JOIN customers c ON c.id = n.ownerid
			WHERE c.id=?", array($ownerid));
		}
		
		return $result;
	}
	function GetHostForNetdevices()
	{
	    return $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
		FROM nodes n 
		LEFT JOIN gpononu g1 ON g1.host_id1 = n.id
		LEFT JOIN gpononu g2 ON g2.host_id2 = n.id
		WHERE g1.host_id1 IS NULL
		  AND g2.host_id2 IS NULL
		  AND n.ownerid=0 ORDER BY host");
	}
	function IsNodeIdNetDevice($id)
	{
	    if($this->DB->GetOne("SELECT id FROM nodes WHERE ownerid=0 AND id = ?", array($id)))
		return true;
	    else
		return false;
	}
	function GetGponOnuCountOnPort($netdevicesid,$numport)
	{
		$netdevicesid=intval($netdevicesid);
		$numport=intval($numport);
		return $this->DB->GetOne('SELECT count(gpononuid) as CountOnPort FROM gpononu2olt go2o
		WHERE go2o.netdevicesid=? AND go2o.numport=?', array($netdevicesid,$numport));
	}
	function GetGponOnuList($order='name,asc')
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'profil':
				$sqlord = ' ORDER BY gp.name';
			break;
			case 'model':
				$sqlord = ' ORDER BY gom.name';
			break;
			case 'ports':
				$sqlord = ' ORDER BY ports';
			break;
			case 'serialnumber':
				$sqlord = ' ORDER BY serialnumber';
			break;
			case 'location':
				$sqlord = ' ORDER BY location';
			break;
			case 'owner':
				$sqlord = ' ORDER BY owner';
			break;
			case 'gponolt':
				$sqlord = ' ORDER BY gponolt';
			break;
			default:
				$sqlord = ' ORDER BY name';
			break;
		}
		$where=' WHERE 1=1 ';
		if($netdevlist = $this->DB->GetAll('SELECT g.*,gom.name AS model,gom.producer,(select sum(portscount) from gpononuportstype2models where gpononumodelsid=g.gpononumodelsid) as ports, gp.name AS profil,
		(SELECT nd.name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponolt,
		(SELECT nd.gponoltid FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltid,
		(SELECT nd.model FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltmodel,
		(SELECT go2o.numport from gpononu2olt go2o WHERE go2o.gpononuid=g.id) AS gponoltnumport,
		(SELECT nd.id AS name from gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltnetdevicesid  
			FROM gpononu g 
			LEFT JOIN gponoltprofiles gp ON gp.id = g.gponoltprofilesid
			INNER JOIN gpononumodels gom ON gom.id=g.gpononumodelsid '.$where
			.($sqlord != '' ? $sqlord.' '.$direction : '')))
		{
		    foreach($netdevlist as $idx => $row)
		    {
			if(preg_match('/8240/', $row['gponoltmodel'])) //jesli duzy olt to formatujemy 1/1,...
			{
				$netdevlist[$idx]['gponoltnumportf'] = $this->OLT8240_format($row['gponoltnumport']);
			}
		    }
		}

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;
		return $netdevlist;
	}
	function OLT8240_format($port)
	{
		$a = ceil(intval($port) / 4);
		$b = intval($port ) % 4;
		if ($b == 0)
			$b = 4;

		return $a . '/'. $b;
	}
	function GetNotConnectedOnu()
	{
		return $this->DB->GetAll('SELECT g.*,gom.name AS model,gom.producer,(select sum(portscount) from gpononuportstype2models where gpononumodelsid=g.gpononumodelsid) as ports
			FROM gpononu g 
			inner join gpononumodels gom on gom.id=g.gpononumodelsid
			where g.id not in (select distinct gpononuid from gpononu2olt)
			ORDER BY name');
	}
	function GetGponOnu($id)
	{
		$result = $this->DB->GetRow("SELECT g.*, gom.name AS model,
		    (SELECT SUM(portscount) FROM gpononuportstype2models WHERE gpononumodelsid=g.gpononumodelsid) AS ports, gom.producer, 
		    (SELECT portscount FROM gpononuportstype2models gm2p JOIN gpononuportstype gpt ON gpt.id = gm2p.gpononuportstypeid WHERE gpononumodelsid=g.gpononumodelsid AND gpt.name='pots') AS potsports, 
		    (SELECT nd.name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponolt,
		    (SELECT nd.id AS name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltnetdevicesid,
		    (SELECT go2o.numport FROM gpononu2olt go2o WHERE go2o.gpononuid=g.id) AS gponoltnumport,
		    (SELECT nd.gponoltid AS name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltid,
		    (SELECT gop.name FROM gponoltprofiles gop WHERE gop.id=g.gponoltprofilesid) AS profil_olt,
		    (SELECT va.phone FROM voipaccounts va WHERE va.id=g.voipaccountsid1) AS voipaccountsid1_phone,
		    (SELECT va.phone FROM voipaccounts va WHERE va.id=g.voipaccountsid2) AS voipaccountsid2_phone,
		    (SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no WHERE no.id=g.host_id1) AS host_id1_host,
		    (SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no WHERE no.id=g.host_id2) AS host_id2_host
		FROM gpononu g
		INNER JOIN gpononumodels gom on gom.id=g.gpononumodelsid
		WHERE g.id = ?", array($id));

		$result['createdby'] = $this->DB->GetOne('SELECT name FROM users WHERE id=?', array($result['creatorid']));
		$result['modifiedby'] = $this->DB->GetOne('SELECT name FROM users WHERE id=?', array($result['modid']));
		$result['creationdateh'] = date('Y/m/d, H:i',$result['creationdate']);
		$result['moddateh'] = date('Y/m/d, H:i',$result['moddate']);
		
		return $result;
	}
	function GetGponOnuFromName($name)
	{
		$result = $this->DB->GetRow("SELECT g.*,
		    (SELECT SUM(portscount) FROM gpononuportstype2models WHERE gpononumodelsid=g.gpononumodelsid) AS ports,
		    (SELECT portscount FROM gpononuportstype2models gm2p JOIN gpononuportstype gpt ON gpt.id = gm2p.gpononuportstypeid WHERE gpononumodelsid=g.gpononumodelsid AND gpt.name='pots') AS potsports,
		    (SELECT nd.name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponolt,
		    (SELECT nd.id AS name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltnetdevicesid,
		    (SELECT go2o.numport FROM gpononu2olt go2o WHERE go2o.gpononuid=g.id) AS gponoltnumport,
		    (SELECT nd.gponoltid AS name FROM gpononu2olt go2o INNER JOIN netdevices nd ON nd.id=go2o.netdevicesid WHERE go2o.gpononuid=g.id) AS gponoltid,
		    (SELECT gop.name FROM gponoltprofiles gop WHERE gop.id=g.gponoltprofilesid) AS profil_olt,
		    (SELECT va.phone FROM voipaccounts va WHERE va.id=g.voipaccountsid1) AS voipaccountsid1_phone,
		    (SELECT va.phone FROM voipaccounts va WHERE va.id=g.voipaccountsid2) AS voipaccountsid2_phone,
		    (SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no WHERE no.id=g.host_id1) AS host_id1_host,
		    (SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no WHERE no.id=g.host_id2) AS host_id2_host
			FROM gpononu g
			WHERE g.name = ?", array($name));
		
		return $result;
	}
	function GponOnuNameExists($name)
	{
		return ($this->DB->GetOne("SELECT * FROM gpononu WHERE name=?", array($name)) ? TRUE : FALSE);
	}
	
	function GponOnuExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM gpononu WHERE id=?', array($id)) ? TRUE : FALSE);
	}
	function GponOnuAdd($gpononudata)
	{
		$ogonek     = array("ą", "ś", "ż", "ź", "ć", "ń", "ł", "ó", "ę", "Ą", "Ś", "Ż", "Ź", "Ć", "Ń", "Ł", "Ó", "Ę");
		$bez_ogonek = array("a", "s", "z", "z", "c", "n", "l", "o", "e", "A", "S", "Z", "Z", "C", "N", "L", "O", "E");
		$gpononudata['onu_description'] = str_replace($ogonek, $bez_ogonek, $gpononudata['onu_description']);
		$gpononudata['gpononumodelsid']=intval($gpononudata['gpononumodelsid']);
		$gpononumodelid=1;
		if($gpononudata['gpononumodelsid']==0)
		{
			$gpononudata['onu_model']=trim($gpononudata['onu_model']);
			if(strlen($gpononudata['onu_model'])>0)
			{
				$result = $this->DB->GetRow("SELECT id FROM gpononumodels
				WHERE name = ?", array($gpononudata['onu_model']));
				$gpononudata['gpononumodelsid']=intval($result['id']);
				if($gpononudata['gpononumodelsid']==0)
				{
					if ($this->DB->Execute('INSERT INTO gpononumodels (name) 
						VALUES (?)', 
						array(
							$gpononudata['onu_model'],
						)))
					{
						$gpononudata['gpononumodelsid'] = $this->DB->GetLastInsertID('gpononumodels');
						$this->Log(4, 'gpononumodel', $gpononudata['gpononumodelsid'], 'model added via onuadd: '.$gpononudata['onu_model']);
					}
				}
			}
		}
		$gpononudata['gpononumodelsid']=intval($gpononudata['gpononumodelsid']);
		if($gpononudata['gpononumodelsid']==0)
		{
			$gpononudata['gpononumodelsid']=1;
		}
		$gpononudata['gponoltprofilesid']=intval($gpononudata['gponoltprofilesid'])>0 ? $gpononudata['gponoltprofilesid']: NULL;
		$gpononudata['voipaccountsid1']=intval($gpononudata['voipaccountsid1'])>0 ? $gpononudata['voipaccountsid1']: NULL;
		$gpononudata['voipaccountsid2']=intval($gpononudata['voipaccountsid2'])>0 ? $gpononudata['voipaccountsid2']: NULL;
		$gpononudata['host_id1']=intval($gpononudata['host_id1'])>0 ? $gpononudata['host_id1']: NULL;
		$gpononudata['host_id2']=intval($gpononudata['host_id2'])>0 ? $gpononudata['host_id2']: NULL;
		if ($this->DB->Execute('INSERT INTO gpononu (name, location, gpononumodelsid, description, serialnumber, purchasetime, guaranteeperiod, password, autoprovisioning, onudescription, gponoltprofilesid, voipaccountsid1, voipaccountsid2, host_id1, host_id2, creatorid, creationdate) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?)', 
				array(
					$gpononudata['name'],
					$gpononudata['location'],
					$gpononudata['gpononumodelsid'],
					$gpononudata['description'],
					$gpononudata['serialnumber'],
					$gpononudata['purchasetime'],
					$gpononudata['guaranteeperiod'],
					$gpononudata['password'],
					$gpononudata['autoprovisioning'],
					$gpononudata['onu_description'],
					$gpononudata['gponoltprofilesid'],
					$gpononudata['voipaccountsid1'],
					$gpononudata['voipaccountsid2'],
					$gpononudata['host_id1'],
					$gpononudata['host_id2'],
					$this->AUTH->id
		))) {
		
			$id = $this->DB->GetLastInsertID('gpononu');
			$dump = var_export($gpononudata, true);
			$this->Log(4, 'gpononu', $id, 'added '.$gpononudata['name'], $dump);
			return $id;
		}
		else
			return FALSE;
	}
	function GponOnuDescriptionUpdate($id,$onudescription)
	{
		$ogonek     = array("ą", "ś", "ż", "ź", "ć", "ń", "ł", "ó", "ę", "Ą", "Ś", "Ż", "Ź", "Ć", "Ń", "Ł", "Ó", "Ę");
		$bez_ogonek = array("a", "s", "z", "z", "c", "n", "l", "o", "e", "A", "S", "Z", "Z", "C", "N", "L", "O", "E");
		$onudescription = str_replace($ogonek, $bez_ogonek, $onudescription);
		$id=intval($id);
		if($id>0)
		{
			$this->DB->Execute('UPDATE gpononu SET onudescription=?
				WHERE id=?', 
				array( 
					$onudescription,
					$id
				));
			$this->Log(4, 'gpononu', $id, 'description set: '.$onudescription);
		}
	}
	function GponOnuVoipUpdate($id, $port, $voipid)
	{
		if ($port > 0)
		{
			$colname = 'voipaccountsid'. $port;
			$this->DB->Execute('UPDATE gpononu SET '. $colname .' = ? WHERE id = ?', array($voipid, $id));
			$this->Log(4, 'gpononu', $id, 'voip '.$port.' set: '.$voipid);
		}
	}
	function GponOnuProfileUpdateByName($id, $profile)
	{
		$id=intval($id);
		if($id>0)
		{
			if ($pid = $this->DB->GetOne('SELECT id FROM gponoltprofiles WHERE name = ?', array($profile)))
			{
				$this->DB->Execute('UPDATE gpononu SET gponoltprofilesid = ?
					WHERE id=?',
					array(
						$pid,
						$id
					));
				$this->Log(4, 'gpononu', $id, 'profile set: '.$profile);
			}
		}

	}
	function GponOnuUpdate($gpononudata)
	{
		$ogonek     = array("ą", "ś", "ż", "ź", "ć", "ń", "ł", "ó", "ę", "Ą", "Ś", "Ż", "Ź", "Ć", "Ń", "Ł", "Ó", "Ę");
		$bez_ogonek = array("a", "s", "z", "z", "c", "n", "l", "o", "e", "A", "S", "Z", "Z", "C", "N", "L", "O", "E");
		$gpononudata['onudescription'] = str_replace($ogonek, $bez_ogonek, $gpononudata['onudescription']);
		$gpononudata['gponoltprofilesid']=intval($gpononudata['gponoltprofilesid'])>0 ? $gpononudata['gponoltprofilesid']: NULL;
		$gpononudata['voipaccountsid1']=intval($gpononudata['voipaccountsid1'])>0 ? $gpononudata['voipaccountsid1']: NULL;
		$gpononudata['voipaccountsid2']=intval($gpononudata['voipaccountsid2'])>0 ? $gpononudata['voipaccountsid2']: NULL;
		$gpononudata['host_id1']=intval($gpononudata['host_id1'])>0 ? $gpononudata['host_id1']: NULL;
		$gpononudata['host_id2']=intval($gpononudata['host_id2'])>0 ? $gpononudata['host_id2']: NULL;
		$this->DB->Execute('UPDATE gpononu SET location=?, gpononumodelsid=?, description=?, serialnumber=?, purchasetime=?,
			    guaranteeperiod=?, password=?, autoprovisioning=?, onudescription=?, gponoltprofilesid=?,
			    voipaccountsid1=?, voipaccountsid2=?, host_id1=?, host_id2=?, modid=?, moddate=?NOW?
				WHERE id=?', 
				array( 
					$gpononudata['location'],
					intval($gpononudata['gpononumodelsid']),
					$gpononudata['description'],
					$gpononudata['serialnumber'],
					$gpononudata['purchasetime'],
					$gpononudata['guaranteeperiod'],
					$gpononudata['password'],
					$gpononudata['autoprovisioning'],
					$gpononudata['onudescription'],
					$gpononudata['gponoltprofilesid'],
					$gpononudata['voipaccountsid1'],
					$gpononudata['voipaccountsid2'],
					$gpononudata['host_id1'],
					$gpononudata['host_id2'],
					$this->AUTH->id,
					
					$gpononudata['id']
				));
		$dump = var_export($gpononudata, true);
		$this->Log(4, 'gpononu', $gpononudata['id'], 'updated '.$gpononudata['name'], $dump);
	}
	function GetGponOnuCheckList($devid=0)
	{
		$onu_list=array();
		$olts=$this->GetGponAllOlt($devid);
		if(is_array($olts) && count($olts)>0)
		{
			$i=0;
			foreach($olts as $k=>$v)
			{
				$this->snmp->clear_options();
				if(is_array($v) && count($v)>0)
				{
					$this->snmp->set_options($v);
					$olt_name=$v['name'];
					$olt_netdevicesid=$v['netdevicesid'];
					$gponoltid=$v['id'];
				}
				$error_snmp=$this->snmp->get_correct_connect_snmp();
				if(strlen($error_snmp)>0)
				{
					$error_snmp.=' - <b>('.$olt_name.')</b><br />';
				}
				if(strlen($error_snmp)>0)
				{
					$onu_list[$i]['olt_name']=$error_snmp;
					$i++;
				}
				$olts_walk=$this->snmp->walk('sleGponOltId');
				if(is_array($olts_walk) && count($olts_walk)>0)
				{
					//wgranie brakujacych profili do bazy LMS
					$profiles_olt=$this->snmp->walk('sleGponProfileName');
					if(is_array($profiles_olt) && count($profiles_olt)>0)
					{
						foreach($profiles_olt as $k_p=>$v_p)
						{
							$v_p=$this->snmp->clean_snmp_value($v_p);
							$this->AddGponOltProfiles($v_p);
						}
					}
					foreach($olts_walk as $k1=>$v1)
					{
						$olt_port=$this->snmp->clean_snmp_value($v1);
						$onus_walk=$this->snmp->walk('sleGponOnuSerial.'.$olt_port);
						
						if(is_array($onus_walk) && count($onus_walk)>0)
						{
							foreach($onus_walk as $k2=>$v2)
							{
								$onu_id=str_replace('SLE-GPON-MIB::sleGponOnuSerial.'.$olt_port.'.','',$k2);
								$onu_serial=$this->snmp->clean_snmp_value($v2);
								if($this->IsGponOnuSerialConected($v['id'],$olt_port,$onu_id,$onu_serial)==false)
								{
									$onu_list[$i]['olt_name']=$olt_name;
									$onu_list[$i]['olt_netdevicesid']=$olt_netdevicesid;
									$onu_list[$i]['gponoltid']=$gponoltid;
									$onu_list[$i]['olt_port']=$olt_port;
									$onu_list[$i]['onu_id']=$onu_id;
									$onu_list[$i]['onu_serial']=$onu_serial;
									$onu_list[$i]['onu_description']=$this->snmp->get('sleGponOnuDescription.'.$olt_port.'.'.$onu_id);
									$onu_list[$i]['onu_password']=$this->snmp->hexToStr($this->snmp->get('sleGponOnuPasswd.'.$olt_port.'.'.$onu_id));
									$onu_list[$i]['onu_passwordMode']=$this->snmp->get('sleGponOnuPasswdMode.'.$olt_port.'.'.$onu_id);
									$onu_list[$i]['onu_model']=$this->snmp->get('sleGponOnuModelName.'.$olt_port.'.'.$onu_id);
									$onu_list[$i]['onu_exists']=0;
									
									//$this->IsGponOnuSerialConectedOtherOlt($gponoltid,$onu_serial)
									
									if($this->GponOnuNameExists($onu_serial)==true)
									{
										$onu_list[$i]['onu_exists']=1;
										if($this->IsGponOnuSerialConectedOtherOlt($v['id'],$onu_serial)==true)
										{
											$onu_list[$i]['onu_error']=1;
											$onu_list[$i]['onu_error_text']='ONU jest przypisane w LMS do innego OLT. Należy usunąć przypisanie ręcznie.';
										}
										else 
										{
											$gpon_onu_in_db=$this->GetGponOnuFromName($onu_serial);
											if(is_array($gpon_onu_in_db) && count($gpon_onu_in_db)>0)
											{
												$this->GponOnuUnLinkAll($gpon_onu_in_db['id']);
												$this->GponOnuUpdateOnuId($gpon_onu_in_db['id'],$onu_id);
												$this->GponOnuLink($olt_netdevicesid,$olt_port,$gpon_onu_in_db['id']);
												$this->snmp->ONU_set_description($olt_port,$onu_id,$gpon_onu_in_db['onudescription']);
												$gponoltprofiles_temp=$this->FlatArrayFromDB($this->GetGponOltProfiles(),'id','name');
												$this->snmp->ONU_SetProfile($olt_port,$onu_id,$gponoltprofiles_temp[$gpon_onu_in_db['gponoltprofilesid']]);
												
												$phone_data=$this->GetPhoneVoip($gpon_onu_in_db['voipaccountsid1']);
												$VoIP1=$this->snmp->ONU_SetPhoneVoip($olt_port,$onu_id,2,1,$phone_data);
												
												$phone_data=$this->GetPhoneVoip($gpon_onu_in_db['voipaccountsid2']);
												$VoIP2=$this->snmp->ONU_SetPhoneVoip($olt_port,$onu_id,2,2,$phone_data);
											}
										}
									}
									$i++;
								}
							}
						}
					}
				}
				
			}
		}
		return $onu_list;
	}
	function GetDuplicateOnu($devid=0)
	{
		$onu_list=array();
		$olts=$this->GetGponAllOlt($devid);
		$output='';
		if(is_array($olts) && count($olts)>0)
		{
			$i=0;
			foreach($olts as $k=>$v)
			{
				$this->snmp->clear_options();
				if(is_array($v) && count($v)>0)
				{
					$this->snmp->set_options($v);
					$olt_name=$v['name'];
					$olt_netdevicesid=$v['netdevicesid'];
					$gponoltid=$v['id'];
				}
				$error_snmp=$this->snmp->get_correct_connect_snmp();
				if(strlen($error_snmp)>0)
				{
					$error_snmp.=' - <b>('.$olt_name.')</b><br />';
				}
				$output.=$error_snmp;
				$olts_walk=$this->snmp->walk('sleGponOltId');
				if(is_array($olts_walk) && count($olts_walk)>0)
				{
					foreach($olts_walk as $k1=>$v1)
					{
						$olt_port=$this->snmp->clean_snmp_value($v1);
						$onus_walk=$this->snmp->walk('sleGponOnuSerial.'.$olt_port);
						if(is_array($onus_walk) && count($onus_walk)>0)
						{
							foreach($onus_walk as $k2=>$v2)
							{
								$onu_serial=$this->snmp->clean_snmp_value($v2);
								$onu_list[]=$onu_serial;
							}
						}
					}
				}
			}
		}
		$result='';
		if(is_array($onu_list) && count($onu_list)>0)
		{
			//$onu_list[]='DSNWaaaaaad2';
			$duplicate_onu=array_diff_assoc($onu_list,array_unique($onu_list));
			if(is_array($duplicate_onu) && count($duplicate_onu)>0)
			{
				$result='<div style="border:1px solid red;background-color:white;padding:3px;"><b><font color="red">Na OLT wykryto duplikaty ONU:</font><br />'.implode('<br />',$duplicate_onu).'</b></div>';
			}
		}
		return $result;
	}
	function GetGponAutoScript($debug)
	{
		$output='';
		$output.=$this->GetDuplicateOnu();
		$onu_list=array();
		$olts=$this->GetGponAllOlt();
		
		$podlaczam=0;
		if($debug==1)
		{
			$output.='<br />OLT## Wczytanie wszystkich OLT z bazy danych';
		}
		if(is_array($olts) && count($olts)>0)
		{
			$i=0;
			foreach($olts as $k=>$v)
			{
				$this->snmp->clear_options();
				if(is_array($v) && count($v)>0)
				{
					$this->snmp->set_options($v);
					$olt_name=$v['name'];
					$olt_netdevicesid=$v['netdevicesid'];
					$gponoltid=$v['id'];
				}
				$error_snmp=$this->snmp->get_correct_connect_snmp();
				if(strlen($error_snmp)>0)
				{
					$error_snmp.=' - <b>('.$olt_name.')</b><br />';
				}
				$output.=$error_snmp;
				if($debug==1)
				{
					$output.='<br />OLT-nazwa## <b>'.$olt_name.'</b>';
				}
				$olts_walk=$this->snmp->walk('sleGponOltId');
				if(is_array($olts_walk) && count($olts_walk)>0)
				{
					//wgranie brakujacych profili do bazy LMS
					$profiles_olt=$this->snmp->walk('sleGponProfileName');
					if(is_array($profiles_olt) && count($profiles_olt)>0)
					{
						foreach($profiles_olt as $k_p=>$v_p)
						{
							$v_p=$this->snmp->clean_snmp_value($v_p);
							$this->AddGponOltProfiles($v_p);
						}
					}
					foreach($olts_walk as $k1=>$v1)
					{
						if($debug==1)
						{
							$output.='<br />OLT-snmp-id## '.$k1;
						}
						$olt_port=$this->snmp->clean_snmp_value($v1);
						if($debug==1)
						{
							$output.='<br /><b>OLT-snmp-port## '.$olt_port.'</b>';
						}
						$onus_walk=$this->snmp->walk('sleGponOnuSerial.'.$olt_port);
						if($debug==1)
						{
							$output.='<br />ONU-snmp## Wczytanie wszystkich ONU z portu OLT';
						}
						if(is_array($onus_walk) && count($onus_walk)>0)
						{
							$onu_to_olt_db=0;
							$onu_db_correct=0;
							$onu_exists_db=0;
							$onu_autoprovisioning=0;
							$onu_autoscript=0;
							$onu_data=array();
							foreach($onus_walk as $k2=>$v2)
							{
								$error_onu=0;
								$onu_id=str_replace('SLE-GPON-MIB::sleGponOnuSerial.'.$olt_port.'.','',$k2);
								$onu_serial=$this->snmp->clean_snmp_value($v2);
								if($debug==1)
								{
									$output.='<br />ONU-snmp-ONU-ID## <b>'.$k2.'</b>';
								}
								if($debug==1)
								{
									$output.='<br />ONU-snmp-ONU-serial## <b>'.$onu_serial.'</b>';
								}
								if($this->IsGponOnuSerialConected($v['id'],$olt_port,$onu_id,$onu_serial)==true)
								{
									$onu_to_olt_db=1;
									if($debug==1)
									{
										$output.='<br /><font color="blue">ONU-TO-OLT# Jest polaczenie w bazie danych</font>';
									}
								}
								elseif($this->IsGponOnuSerialConectedOtherOlt($v['id'],$onu_serial)==true)
								{
									$error_onu=1;
									$output.='<br /><font color="red"><b>ERROR!!! - Wystapil blad!!! ONU '.$onu_serial.' jest podłączone pod inny OLT</b></font>';
								}
								$onu_database=$this->GponGetOnuNameFromOltOnuId($v['id'],$olt_port,$onu_id);
								if($onu_database['name']==$onu_serial)
								{
									$onu_db_correct=1;
									if($debug==1)
									{
										$output.='<br /><font color="blue">ONU# W bazie danych jest to samo ONU</font>';
									}
								}
								elseif(strlen($onu_database['name'])>0) 
								{
									$onu_db_correct=0;
									//if($debug==1)
									//{
										$error_onu=1;
										$output.='<br /><font color="red"><b>ERROR!!! - Wystapil blad!!! Inne ONU jest w bazie (OLT-port: '.$olt_port.', ONU-ID: '.$onu_id.', ONU-Serial: '.$onu_database['name'].')</b></font>';
									//}
								}
								$onu_data=$this->GetGponOnuFromName($onu_serial);
								if(is_array($onu_data) && count($onu_data)>0)
								{
									$onu_exists_db=1;
									if($debug==1)
									{
										$output.='<br /><font color="blue">ONU# Jest bazie danych</font>';
									}
									if(intval($onu_data['autoprovisioning'])==1)
									{
										$onu_autoprovisioning=1;
										if($debug==1)
										{
											$output.='<br /><font color="blue">ONU# Wydano do klienta</font>';
										}
										if(intval($onu_data['autoscript'])==1)
										{
											$onu_autoscript=1;
											if($debug==1)
											{
												$output.='<br /><font color="red">ONU# Konfiguracja juz wyslana</font>';
											}
										}
									}
								}
								if($error_onu==0 && $onu_to_olt_db==0 && $onu_exists_db==1 && $onu_autoprovisioning==1 && $onu_autoscript==0 && is_array($onu_data) && count($onu_data)>0 && $onu_data['name']==$onu_serial)
								{
									if($debug==1)
									{
										$output.='<br />---------------<b>'.$onu_serial.'</b>-------------------------';
									}
									if($debug==1)
									{
										$output.='<br /><font color="blue"><b>ONU# SNMP - konfiguracja START</b></font>';
									}
									//var_dump($onu_data);
									
									$password=$this->snmp->ONU_set_password($olt_port,$onu_id,$onu_serial,$onu_data['password']);
									if($debug==1)
									{
										$output.='<br />Password: '.$this->GetSNMPresultMsg($password);
									}
									
									$profile=$this->snmp->ONU_SetProfile($olt_port,$onu_id,$onu_data['profil_olt']);
									if($debug==1)
									{
										$output.='<br />Profile: '.$this->GetSNMPresultMsg($profile);
									}
									$description=$this->snmp->ONU_set_description($olt_port,$onu_id,$onu_data['onudescription']);
									if($debug==1)
									{
										$output.='<br />Description:'.$this->GetSNMPresultMsg($description);
									}
									
									
									$phone_data=$this->GetPhoneVoip($onu_data['voipaccountsid1']);
									$VoIP1=$this->snmp->ONU_SetPhoneVoip($olt_port,$onu_id,2,1,$phone_data);
									if($debug==1)
									{
										$output.='<br />VoIP1: '.$this->GetSNMPresultMsg($VoIP1);
									}
									
									$phone_data=$this->GetPhoneVoip($onu_data['voipaccountsid2']);
									$VoIP2=$this->snmp->ONU_SetPhoneVoip($olt_port,$onu_id,2,2,$phone_data);
									if($debug==1)
									{
										$output.='<br />VoIP2: '.$this->GetSNMPresultMsg($VoIP2);
									}
									
									$reset=$this->snmp->ONU_Reset($olt_port,$onu_id);
									if($debug==1)
									{
										$output.='<br />RESET: '.$this->GetSNMPresultMsg($reset);
									}
									$this->GponOnuUnLinkAll($onu_data['id']);
									$onu_to_olt_db_set=$this->GponOnuLink($olt_netdevicesid,$olt_port,$onu_data['id']);
									$this->GponOnuUpdateOnuId($onu_data['id'],$onu_id);
									$onu_to_olt_db_set=$onu_to_olt_db_set==1?'<b>OK</b>':'<font color="red"><b>ERROR</b></font>';
									if($debug==1)
									{
										$output.='<br />SET ONU TO OLT:'.$onu_to_olt_db_set;
									}
									$this->GponOnuSetAutoScript($onu_data['id']);
									
									if($debug==1)
									{
										$output.='<br /><font color="blue"><b>ONU# SNMP - konfiguracja KONIEC</b></font>';
										$output.='<br />-------------------------------------------------------';
									}
									$podlaczam=1;
									$output.='<br /><b>Podlaczono ONU '.$onu_serial.' na OLT port '.$olt_port.'/'.$onu_id.'</b>';
								}
								else 
								{
									if($debug==1)
									{
										$output.='<br /><font color="red">ONU# Nie spelniono warunkow - nie wyslano konfiguracji</font>';
									}
									if($onu_to_olt_db==1)
									{
										if($debug==1)
										{
											$output.='<br /><font color="red">ONU-TO-OLT# Jest polaczenie z OLT w bazie danych</font>';
										}
									}
									if($onu_exists_db==0)
									{
										if($debug==1)
										{
											$output.='<br /><font color="red">ONU# Nie ma w bazie danych</font>';
										}
									}
									else 
									{
										if(!is_array($onu_data) || count($onu_data)==0)
										{
											if($debug==1)
											{
												$output.='<br /><font color="red">ONU# Nie pobrano danych z bazy danych</font>';
											}
										}
									}
									if($onu_autoprovisioning==0)
									{
										if($debug==1)
										{
											$output.='<br /><font color="red">ONU# Nie wydano do klienta</font>';
										}
									}
									if($onu_autoscript==1)
									{
										if($debug==1)
										{
											$output.='<br /><font color="red">ONU# Konfiguracja juz wyslana</font>';
										}
									}
									
									
									
									if($debug==1)
									{
										$output.='<br />-------------------------------------------------------';
									}
								}
							}
						}
						else 
						{
							if($debug==1)
							{
								$output.='<br /><font color="blue">ONU-snmp# Brak ONU dla OLT</font>';
							}
						}
					}
				}
				
			}
		}
		if($podlaczam==0)
		{
			$output.='<br /><b>Nic nie podlaczono</b>';
		}
		return $output;
	}
	function GetSNMPresultMsg($result_array=array())
	{
		$result='<b>OK</b>';
		if(is_array($result_array) && count($result_array)>0)
		{
			foreach($result_array as $k=>$v)
			{
				if($v==false)
				{
					$result='<font color="red"><b>ERROR</b></font>';
				}
			}
		}
		return $result;
	}
	function GponOnuSetAutoScript($gpononuid,$autoscript=1)
	{
		$gpononuid=intval($gpononuid);
		if($gpononuid>0)	
		{
			$this->DB->Execute('UPDATE gpononu SET autoscript=?
				WHERE id=?', 
				array( 
					$autoscript,
					$gpononuid
				));
			$this->Log(4, 'gpononu', $gpononuid, 'autoscript set to '.$autoscript);
		}
	}
	function GetGponAllOlt($olt=0)
	{
		$where = ' WHERE 1=1';
		if($olt > 0)
		{
			$where .= ' AND d.id = '.$olt;
		}

		$result = $this->DB->GetAll('SELECT g.*,d.name,d.id AS netdevicesid
			FROM gponolt g INNER JOIN netdevices d ON d.gponoltid=g.id'. $where);
		return $result;
	}
	function IsGponOnuSerialConected($gponoltid,$olt_port,$onu_id,$onu_serial)
	{
		return ($this->DB->GetOne("SELECT g2o.* FROM gpononu2olt g2o 
		INNER JOIN netdevices n ON n.id=g2o.netdevicesid 
		INNER JOIN gpononu go ON go.id=g2o.gpononuid
		
		WHERE n.gponoltid=? AND g2o.numport=? AND go.onuid=? AND go.name=?", array($gponoltid,$olt_port,$onu_id,$onu_serial)) ? TRUE : FALSE);
	}
	function IsGponOnuSerialConectedOtherOlt($gponoltid,$onu_serial)
	{
		return ($this->DB->GetOne("SELECT g2o.* FROM gpononu2olt g2o 
		INNER JOIN netdevices n ON n.id=g2o.netdevicesid 
		INNER JOIN gpononu go ON go.id=g2o.gpononuid
		
		WHERE n.gponoltid<>? AND go.name=?", array($gponoltid,$onu_serial)) ? TRUE : FALSE);
	}
	function GponGetOnuNameFromOltOnuId($gponoltid,$olt_port,$onu_id)
	{
		$result = $this->DB->GetRow("SELECT go.name FROM gpononu2olt g2o 
		INNER JOIN netdevices n ON n.id=g2o.netdevicesid 
		INNER JOIN gpononu go ON go.id=g2o.gpononuid
		
		WHERE n.gponoltid=? AND g2o.numport=? AND go.onuid=?", array($gponoltid,$olt_port,$onu_id));
		//var_dump($this->DB);
		return $result;
	}
	//--------------ONU_MODELS----------------
	function GetGponOnuModelsList($order='name,asc')
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'producer':
				$sqlord = ' ORDER BY producer';
			break;		
			default:
				$sqlord = ' ORDER BY name';
			break;
		}
		$where=' WHERE 1=1 ';
		$netdevlist = $this->DB->GetAll('SELECT *
			FROM gpononumodels g '.$where
			.($sqlord != '' ? $sqlord.' '.$direction : ''));

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}
	function GponOnuModelsExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM gpononumodels WHERE id=?', array($id)) ? TRUE : FALSE);
	}
	function CountGponOnuModelsLinks($id)
	{
		return $this->DB->GetOne('SELECT COUNT(*) FROM gpononu WHERE gpononumodelsid = ?', 
				array($id));
	}
	function GetGponOnuModels($id)
	{
		$result = $this->DB->GetRow('SELECT g.*
			FROM gpononumodels g
			WHERE g.id = ?', array($id));
		return $result;
	}
	function GponOnuModelsUpdate($gpononumodelsdata)
	{
		$this->DB->Execute('UPDATE gpononumodels SET name=?,description=?,producer=?
				WHERE id=?', 
				array( 
					$gpononumodelsdata['name'],
					$gpononumodelsdata['description'],
					$gpononumodelsdata['producer'],
					$gpononumodelsdata['id']
				));
		$dump = var_export($gpononumodelsdata, true);
		$this->Log(4, 'gpononumodel', $gpononumodelsdata['id'], 'updated '.$gpononudata['name'], $dump);
	}
	function GponOnuModelsAdd($gpononumodelsdata)
	{
		if ($this->DB->Execute('INSERT INTO gpononumodels (name,description,producer) 
				VALUES (?, ?, ?)', 
				array(
					$gpononumodelsdata['name'],
					$gpononumodelsdata['description'],
					$gpononumodelsdata['producer']
		))) {
		
			$id = $this->DB->GetLastInsertID('gpononumodels');
			$dump = var_export($gpononumodelsdata, true);
			$this->Log(4, 'gpononumodel', $id, 'added '.$gpononudata['name'], $dump);
			return $id;
		}
		else
			return FALSE;
	}
	function DeleteGponOnuModels($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM gpononumodels WHERE id=?', array($id));
		$this->DB->Execute('DELETE FROM gpononuportstype2models WHERE gpononumodelsid=?', array($id));
		$this->Log(4, 'gpononumodel', $id, 'model removed');
		$this->DB->CommitTrans();
	}
	function GetGponOnuModelPorts($model)
	{
		$result = $this->DB->GetAll('SELECT p.id, p.name, portscount FROM gpononuportstype2models p2m
			JOIN gpononuportstype p ON p.id = p2m.gpononuportstypeid
			JOIN gpononumodels m ON m.id = p2m.gpononumodelsid
			WHERE m.id = ? ORDER BY name', array($model));

		return $result;
	}
	function GetGponOnuPorts($id, $disabled=0)
	{
		if($disabled = 1)
			$where_dis = ' AND portdisable =1';
		else
			$where_dis = '';

		$result = $this->DB->GetAll('SELECT p.*, t.name FROM gpononuport p
			JOIN gpononuportstype t ON t.id = p.typeid
			WHERE p.onuid = ?'.
			$where_dis
			, array($id));

		return $result;
	}
	function EnableGponOnuPortDB($onu, $porttype, $port)
	{
		$this->DB->Execute('UPDATE gpononuport SET portdisable =0 WHERE onuid=? AND typeid=? AND portid=?',
			array($onu, $porttype, $port));
		$this->Log(4, 'gpononu', $onu, 'port enabled: '.$port.', typ: '.$porttype);
	}
	function DisableGponOnuPortDB($onu, $porttype, $port)
	{
		$rows = $this->DB->Execute('UPDATE gpononuport SET portdisable =1 WHERE onuid=? AND typeid=? AND portid=?',
			array($onu, $porttype, $port));
		if($rows == 0)
		{
			$rows = $this->DB->Execute('INSERT INTO gpononuport(onuid, typeid, portid, portdisable)
				VALUES(?, ?, ?, 1)',
				array($onu, $porttype, $port));
		}
		$this->Log(4, 'gpononu', $onu, 'port disabled: '.$port.', typ: '.$porttype);
	}
	function GetGponOnuPortsType()
	{
		$result = $this->DB->GetAll('SELECT gpt.*
			FROM gpononuportstype gpt
			 ORDER BY gpt.id ASC');
		return $result;
	}
	function GetGponOnuPortsType2Models($gpononumodelsid)
	{
		$result = $this->DB->GetAll('SELECT gpt2m.*
			FROM gpononuportstype2models gpt2m
			WHERE gpt2m.gpononumodelsid=? ORDER BY gpt2m.gpononuportstypeid ASC', array($gpononumodelsid));
		return $result;
	}
	function SetGponOnuPortsType2Models($gpononumodelsid,$portstypedata)
	{
		if($gpononumodelsid>0)
		{
			$this->DB->BeginTrans();
			$this->DB->Execute('DELETE FROM gpononuportstype2models WHERE gpononumodelsid=?', array($gpononumodelsid));
			if(is_array($portstypedata) && count($portstypedata)>0)
			{
				foreach($portstypedata as $k=>$v)
				{
					if(intval($v)>0)
					{
						$this->DB->Execute('INSERT INTO gpononuportstype2models(gpononuportstypeid,gpononumodelsid,portscount) 
								VALUES (?, ?, ?)', 
								array(
									intval($k),
									$gpononumodelsid,
									intval($v)
						));
					}
				}
			}
			$dump = var_export($portstypedata, true);
			$this->Log(4, 'gpononumodel', $gpononumodelsid, 'ports type updated', $dump);
			$this->DB->CommitTrans();
		}
	}
	//--------------GPON_TV----------------
	function GetGponOnuTvList($order='name,asc')
	{
		list($order,$direction) = sscanf($order, '%[^,],%s');

		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

		switch($order)
		{
			case 'id':
				$sqlord = ' ORDER BY id';
			break;
			case 'producer':
				$sqlord = ' ORDER BY ipaddr';
			break;		
			default:
				$sqlord = ' ORDER BY channel';
			break;
		}
		$where=' WHERE 1=1 ';
		$netdevlist = $this->DB->GetAll('SELECT  g.id,inet_ntoa(g.ipaddr) AS ipaddr,g.channel
			FROM gpononutv g '.$where
			.($sqlord != '' ? $sqlord.' '.$direction : ''));

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}
	function GetGponOnuTv($id)
	{
		$result = $this->DB->GetRow('SELECT g.id,inet_ntoa(g.ipaddr) AS ipaddr,g.channel
			FROM gpononutv g
			WHERE g.id = ?', array($id));
		return $result;
	}
	function GponOnuTvUpdate($gpononutvdata)
	{
		$this->DB->Execute('UPDATE gpononutv SET ipaddr=inet_aton(?),channel=?
				WHERE id=?', 
				array( 
					$gpononutvdata['ipaddr'],
					$gpononutvdata['channel'],
					$gpononutvdata['id']
				));
		$this->Log(4, 'gpononutv', $gpononutvdata['id'], 'updated: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
	}
	function GponOnuTvAdd($gpononutvdata)
	{
		if ($this->DB->Execute('INSERT INTO gpononutv (ipaddr,channel) 
				VALUES (inet_aton(?), ?)', 
				array(
					$gpononutvdata['ipaddr'],
					$gpononutvdata['channel']
		))) {
		
			$id = $this->DB->GetLastInsertID('gpononutv');
			$this->Log(4, 'gpononutv', $id, 'added: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
			return $id;
		}
		else
			return FALSE;
	}
	function DeleteGponOnuTv($id)
	{
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM gpononutv WHERE id=?', array($id));
		$this->Log(4, 'gpononutv', $id, 'deleted');
		$this->DB->CommitTrans();
	}
	function GponOnuTvIpExists($ip,$id=0)
	{
		if($id==0)
		{
			return ($this->DB->GetOne('SELECT * FROM gpononutv WHERE ipaddr=inet_aton(?)', array($ip)) ? TRUE : FALSE);
		}
		else 
		{
			return ($this->DB->GetOne('SELECT * FROM gpononutv WHERE ipaddr=inet_aton(?) AND id<>?', array($ip,$id)) ? TRUE : FALSE);
		}
	}
	function GponOnuTvExists($id)
	{
		return ($this->DB->GetOne('SELECT * FROM gpononutv WHERE id=?', array($id)) ? TRUE : FALSE);
	}
	function GetGponOnuTvChannel($ipaddr)
	{
		$ipaddr=trim($ipaddr);
		$result = $this->DB->GetRow("SELECT g.channel
			FROM gpononutv g
			WHERE g.ipaddr = inet_aton(?)", array($ipaddr));
		return $result;
	}
	function IsGponOnuTvMulticast($ipaddr)
	{
		$result=false;
		$address=explode('.',$ipaddr);
		if(is_array($address) && count($address)>0)
		{
			if(intval($address[0])>223)
			{
				$result=true;
			}
		}
		return $result;
	}
	function IsNotOldOnuModel($model)
	{
		$oldonu = array('H640V',
		    'H640GV',
		    'H640R',
		    'H640GR',
		    'H640RW',
		    'H645A',
		    'H645B',
		    'H640GW');

		if(in_array($model, $oldonu))
			return false;
		else
			return true;
	}
	function OnuModelWithRF($model)
	{
	    $onurf = array('H640GR',
		    'H640GR-02',
		    'H640RW',
		    'H640RW-02');

	    if(in_array($model, $onurf))
		    return true;
	    else
		    return false;
	}
	function GetGponOnuLastAuth($onuid)
	{
	    return $this->DB->GetAll("SELECT * FROM gponauthlog 
		WHERE onuid = ? ORDER BY time DESC",
		array($onuid));
	}
}

?>
