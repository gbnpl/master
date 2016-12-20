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

class GPON_DASAN {
	const SQL_TABLE_SYSLOG = 'syslog';
	const SQL_TABLE_GPONOLT = 'gpondasanolts';
	const SQL_TABLE_GPONOLTPORTS = 'gpondasanoltports';
	const SQL_TABLE_GPONONU2OLT = 'gpondasanonu2olts';
	const SQL_TABLE_GPONOLTPROFILES = 'gpondasanoltprofiles';
	const SQL_TABLE_GPONONU = 'gpondasanonus';
	const SQL_TABLE_GPONONU2CUSTOMERS = 'gpondasanonu2customers';
	const SQL_TABLE_GPONONUMODELS = 'gpondasanonumodels';
	const SQL_TABLE_GPONONUPORTTYPE2MODELS = 'gpondasanonuporttype2models';
	const SQL_TABLE_GPONONUPORTTYPES = 'gpondasanonuporttypes';
	const SQL_TABLE_GPONONUPORTS = 'gpondasanonuports';
	const SQL_TABLE_GPONONUTV = 'gpondasanonutv';
	const SQL_TABLE_GPONAUTHLOG = 'gpondasanauthlog';

	private $DB;			// database object
	private $AUTH;			// object from Session.class.php (session management)
	public $snmp;

	public function __construct() { // class variables setting
		global $AUTH;

		$this->DB = LMSDB::getInstance();
		$this->AUTH = $AUTH;

		$options = array();
		$this->snmp = new GPON_DASAN_SNMP($options, $this);
	}

	public function Log($loglevel = 0, $what = NULL, $xid = NULL, $message = NULL, $detail = NULL) {
		$detail = str_replace("'", '"', $detail);
		if (ConfigHelper::getConfig('gpon-dasan.syslog'))
			$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_SYSLOG . ' (time, userid, level, what, xid, message, detail)
				VALUES (?NOW?, ?, ?, ?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $what, $xid, $message, $detail));
	}

	//--------------OLT----------------
	public function GponOltAdd($gponoltdata) {
		if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLT . ' (snmp_version, snmp_description, snmp_host,
				snmp_community, snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level,
				snmp_privacy_passphrase, snmp_privacy_protocol, netdeviceid)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
					$gponoltdata['netdevid'],
			))) {
			$id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONOLT);
			$dump = var_export($gponoltdata, true);
			$this->Log(4, self::SQL_TABLE_GPONOLT, $id, 'added ' . $gponoltdata['snmp_host'], $dump);
			return $id;
		} else
			return false;
	}

	public function NetDevUpdate($netdevdata) {
		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLT . ' SET netdeviceid = ? WHERE id = ?',
			array($netdevdata['id'], $netdevdata['gponoltid']));
	}

	public function GetGponOltIdByNetdeviceId($netdeviceid) {
		return $this->DB->GetOne('SELECT id FROM ' . self::SQL_TABLE_GPONOLT
			. ' WHERE netdeviceid = ?', array($netdeviceid));
	}

	public function GetGponOlt($id) {
		$result = $this->DB->GetRow('SELECT g.*
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			WHERE g.id = ?', array($id));
		return $result;
	}

	public function GetGponOltList($o) {
		return $this->DB->GetAll('SELECT nd.id, COUNT(gp.id) AS gponports,
				nd.name, nd.location, nd.description, nd.producer, nd.model, nd.serialnumber,
				nd.ports, go.id AS gponoltid
			FROM ' . self::SQL_TABLE_GPONOLT . ' go
			JOIN netdevices nd ON nd.id = go.netdeviceid
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' gp ON gp.gponoltid = go.id
			GROUP BY nd.id, nd.name, nd.location, nd.description, nd.producer, nd.model,
				nd.serialnumber, nd.ports, go.id');
	}

	public function GponOltUpdate($gponoltdata) {
		$dump = var_export($gponoltdata, true);
		$this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltdata['gponoltid'], 'updated '. $gponoltdata['snmp_host'], $dump);

		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLT . ' SET snmp_version=?, snmp_description=?, snmp_host=?, snmp_community=?,
			snmp_auth_protocol=?, snmp_username=?, snmp_password=?, snmp_sec_level=?, snmp_privacy_passphrase=?,
			snmp_privacy_protocol=?, netdeviceid = ? WHERE id = ?',array(
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
					$gponoltdata['id'],
					$gponoltdata['gponoltid']
				));
		if ($gponoltdata['id'] != $gponoltdata['oldid']) {
			$this->DB->Execute('UPDATE netdevices SET name = ? WHERE id = ?',
				array($gponoltdata['name'], $gponoltdata['id']));
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU2OLT
				. ' SET netdevicesid = ? WHERE netdevicesid = ?', array($gponoltdata['id'], $gponoltdata['oldid']));
		} else
			$this->DB->Execute('UPDATE netdevices SET name = ? WHERE id = ?', array($gponoltdata['name'], $gponoltdata['id']));
	}

	public function DeleteGponOlt($id) {
		$this->DB->BeginTrans();
		$gponoltid = $this->GetGponOltIdByNetdeviceId($id);
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLT . ' WHERE id = ?', array($gponoltid));
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' WHERE gponoltid = ?', array($gponoltid));
		$this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltid, 'deleted, devid: '.$id);
		$this->DB->CommitTrans();
	}

	public function GponOltPortsAdd($gponoltportsdata) {
		if (is_array($gponoltportsdata) && count($gponoltportsdata)) {
			$logolt=0;
			foreach ($gponoltportsdata as $v) {
				$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLTPORTS . ' (gponoltid, numport, maxonu)
					VALUES (?, ?, ?)', array($v['gponoltid'], $v['numport'], $v['maxonu']));
				$logolt = $v['gponoltid'];
			}
			$dump = var_export($gponoltportsdata, true);
			$this->Log(4, self::SQL_TABLE_GPONOLT, $logolt, 'ports added', $dump);
		}
	}

	public function GetGponOltPorts($gponoltid) {
		if ($result = $this->DB->GetAll('SELECT gp.*, nd.model,
			(SELECT COUNT(go2o.gpononuid) FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				JOIN ' . self::SQL_TABLE_GPONOLT . ' g ON g.netdeviceid = nd.id
				WHERE g.id = gp.gponoltid AND go2o.numport = gp.numport
			) AS countlinkport
			FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gp
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gp.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE gp.gponoltid = ? ORDER BY gp.numport ASC',
			array($gponoltid)))
			foreach ($result as $idx => $row)
				if (preg_match('/8240/', $row['model']))
					$result[$idx]['numportf'] = $this->OLT8240_format($row['numport']);

		return $result;
	}

	public function GponOltPortsUpdate($gponoltportsdata) {
		$this->DB->BeginTrans();
		if (is_array($gponoltportsdata) && count($gponoltportsdata)) {
			$countport = $this->DB->GetOne('SELECT COUNT(numport) as cn FROM ' . self::SQL_TABLE_GPONOLTPORTS
				. ' WHERE gponoltid = ?', array($gponoltportsdata[1]['gponoltid']));
			if (count($gponoltportsdata) < $countport)
				$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' WHERE gponoltid=? AND numport>?',
						array($gponoltportsdata[1]['gponoltid'], count($gponoltportsdata)));
			foreach ($gponoltportsdata as $v) {
				$numport = $this->DB->GetOne('SELECT numport FROM ' . self::SQL_TABLE_GPONOLTPORTS
					. ' WHERE gponoltid = ? AND numport = ?', array($v['gponoltid'], $v['numport']));
				if ($numport)
					$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLTPORTS . ' SET maxonu = ?
							WHERE gponoltid = ? AND numport = ?',
							array($v['maxonu'], $v['gponoltid'], $v['numport']));
				else
					$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLTPORTS . ' (gponoltid, numport, maxonu)
						VALUES (?, ?, ?)', array($v['gponoltid'], $v['numport'], $v['maxonu']));
			}
		}
		$dump = var_export($gponoltportsdata, true);
		$this->Log(4, self::SQL_TABLE_GPONOLT, $gponoltportsdata[1]['gponoltid'], 'ports updated', $dump);
		$this->DB->CommitTrans();
	}

	public function GetGponOltPortsMaxOnu($netdeviceid, $numport) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		return $this->DB->GetOne('SELECT gop.maxonu FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gop
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gop.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE nd.id = ? AND gop.numport = ?', array($netdeviceid, $numport));
	}

	public function GetGponOltPortsExists($netdeviceid, $numport) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		return $this->DB->GetOne('SELECT gop.id FROM ' . self::SQL_TABLE_GPONOLTPORTS . ' gop
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = gop.gponoltid
			JOIN netdevices nd ON nd.id = go.netdeviceid
			WHERE nd.id = ? AND gop.numport = ?', array($netdeviceid, $numport));
	}

	public function GetNotConnectedOlt() {
		return $this->DB->GetAll('SELECT DISTINCT nd.id, nd.name
			FROM netdevices nd
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
			JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' p ON p.gponoltid = go.id
			WHERE p.maxonu > (SELECT COUNT(id) FROM ' . self::SQL_TABLE_GPONONU2OLT . ' WHERE netdevicesid = nd.id AND numport = p.numport)
			ORDER BY nd.name ASC');
	}

	public function GetFreeOltPort($netdeviceid) {
		return $this->DB->GetAll('SELECT DISTINCT nd.id, gop.numport
			FROM netdevices nd
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
			JOIN ' . self::SQL_TABLE_GPONOLTPORTS . ' gop ON gop.gponoltid = go.id
			WHERE gop.maxonu > (SELECT COUNT(id) FROM ' . self::SQL_TABLE_GPONONU2OLT
					. ' WHERE netdevicesid = nd.id AND numport = gop.numport)
				AND nd.id = ?', array($netdeviceid));
	}

	public function GetGponOltConnectedNames($gpononuid) {
		if ($list = $this->DB->GetAll('SELECT nd.*, go2o.numport, g.id AS gponoltid
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			JOIN netdevices nd ON nd.id = g.netdeviceid
			JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.netdevicesid = nd.id
			WHERE go2o.gpononuid = ?', array($gpononuid)))
			foreach ($list as &$row)
				if (preg_match('/8240/', $row['model']))
					$row['numportf'] = $this->OLT8240_format($row['numport']);
		return $list;
	}

	public function GetGponOltProfiles($gponoltid = null) {
		$result = $this->DB->GetAllByKey('SELECT p.id, p.name' . (empty($gponoltid) ? ', nd.name AS oltname' : '') . '
			FROM ' . self::SQL_TABLE_GPONOLTPROFILES . ' p
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.id = p.gponoltid
			LEFT JOIN netdevices nd ON nd.id = go.netdeviceid '
			. (empty($gponoltid) ? '' : 'WHERE p.gponoltid IS NULL OR p.gponoltid = ' . intval($gponoltid)) . '
			ORDER BY p.name ASC', 'id');

		return $result;
	}

	public function AddGponOltProfile($name, $gponoltid) {
		$name = trim($name);
		if (!strlen($name))
			return;

		if ($pid = $this->DB->GetOne('SELECT id FROM ' . self::SQL_TABLE_GPONOLTPROFILES . '
			WHERE name = ? AND (gponoltid IS NULL OR gponoltid = ?) LIMIT 1',
				array($name, $gponoltid))) {
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONOLTPROFILES . ' SET name = ?, gponoltid = ?
				WHERE id = ?', array($name, $gponoltid, $pid));
			$this->Log(4, self::SQL_TABLE_GPONOLTPROFILES, $pid, 'updated ' . $name
				. ' oltid ' . $gponoltid);
		} else {
			$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONOLTPROFILES . ' (name, gponoltid)
				VALUES (?, ?)', array($name, $gponoltid));
			$pid = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONOLTPROFILES);
			$this->Log(4, self::SQL_TABLE_GPONOLTPROFILES, $pid, 'added ' . $name
				. ' oltid ' . $gponoltid);
		}
	}

	public function GetNotGponOltDevices($gponoltid = null) {
		return $this->DB->GetAll('SELECT n.id, n.name FROM netdevices n
			LEFT JOIN ' . self::SQL_TABLE_GPONOLT . ' g ON g.netdeviceid = n.id
			WHERE g.netdeviceid IS NULL OR g.id = ?
			ORDER BY name', array($gponoltid));
	}

	//--------------ONU----------------
	public function DeleteGponOnu($id) {
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU
			. ' WHERE id=? AND id NOT IN (SELECT DISTINCT gpononuid FROM ' . self::SQL_TABLE_GPONONU2OLT . ')', array($id));
		$this->Log(4, self::SQL_TABLE_GPONONU, $id, 'deleted');
		$this->DB->CommitTrans();
	}

	public function IsGponOnuLink2olt($gpononuid) {
		$gpononuid = intval($gpononuid);
		return $this->DB->GetOne('SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2OLT
			. ' WHERE gpononuid=?', array($gpononuid));
	}

	public function IsGponOnuLink($netdeviceid, $numport, $gpononuid) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		$gpononuid = intval($gpononuid);
		return $this->DB->GetOne('SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2OLT
			. ' WHERE netdevicesid = ? AND numport = ? AND gpononuid = ?',
			array($netdeviceid, $numport, $gpononuid));
	}

	public function GponOnuLink($netdeviceid, $numport, $gpononuid) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		$gpononuid = intval($gpononuid);
		if ($netdeviceid && $numport && $gpononuid && !$this->IsGponOnuLink($netdeviceid, $numport, $gpononuid)) {
			$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'link to ' .$netdeviceid. ', port ' .$numport);

			return $this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONU2OLT
				. ' (netdevicesid, numport, gpononuid) VALUES (?, ?, ?)',
				array($netdeviceid, $numport, $gpononuid));
		}

		return FALSE;
	}

	public function GponOnuUnLink($netdeviceid, $numport, $gpononuid) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		$gpononuid = intval($gpononuid);
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU2OLT
			. ' WHERE netdevicesid = ? AND numport = ? AND gpononuid = ?',
			array($netdeviceid, $numport, $gpononuid));
		$this->DB->Execute('update ' . self::SQL_TABLE_GPONONU
			. ' SET onuid = NULL, autoscript = 0 WHERE id = ?', array($gpononuid));
		$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'unlink with ' .$netdeviceid. ', port ' .$numport);
	}

	public function GponOnuUnLinkAll($gpononuid) {
		$gpononuid = intval($gpononuid);
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU2OLT . ' WHERE gpononuid = ?',
			array($gpononuid));
		$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'unlink with all');
	}

	public function GponOnuUpdateOnuId($gpononuid, $onuid) {
		$gpononuid=intval($gpononuid);
		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET onuid = ?
				WHERE id = ?', array($onuid, $gpononuid));
		$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'onuid updated:'.$onuid);
	}

	function GetGponOnuConnectedNames($netdeviceid, $order='numport,asc', $oltport = 0) {
		list ($order, $direction) = sscanf($order, '%[^,],%s');
		($direction=='desc') ? $direction = 'desc' : $direction = 'asc';
		switch ($order) {
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

		$oltport = intval($oltport);
		if ($oltport)
			$where = ' AND go2o.numport=' . $oltport;
		else
			$where = ' ';

		if ($list = $this->DB->GetAll('SELECT go.id AS gponoltid, n.model AS oltmodel, g.*, gom.name AS model, gom.producer, go2o.numport,
			(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.gpononuid = g.id
			JOIN netdevices n ON n.id = go2o.netdevicesid
			JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = n.id
			WHERE go2o.netdevicesid=? '. $where . $sqlord .' '. $direction, array($netdeviceid)))
			foreach ($list as $idx => $row)
				if (preg_match('/8240/', $row['oltmodel']))
					$list[$idx]['numportf'] = $this->OLT8240_format($row['numport']);

		return $list;
	}

	public function GetGponOnuCustomersNames($ownerid) {
		if ($list = $this->DB->GetAll('SELECT g.*, gom.name AS model, gom.producer, n.model AS oltmodel, gp.name AS profil,
			(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid=g.gpononumodelsid) AS ports,
			(SELECT nd.name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				WHERE go2o.gpononuid = g.id) AS gponolt,
			(SELECT go.id AS gponoltid FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
				WHERE go2o.gpononuid = g.id) AS gponoltid,
			(SELECT go2o.numport FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				WHERE go2o.gpononuid = g.id) AS gponoltnumport,
			(SELECT nd.id AS name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
				JOIN netdevices nd ON nd.id = go2o.netdevicesid
				WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id=g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.gpononuid=g.id
			LEFT JOIN ' . self::SQL_TABLE_GPONONU2OLT . ' go2o ON go2o.gpononuid=g.id
			LEFT JOIN netdevices n ON n.id=go2o.netdevicesid
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPROFILES . ' gp ON gp.id = g.gponoltprofilesid
			WHERE g2c.customersid=?', array($ownerid)))
			foreach ($list as $idx => $row)
				if (preg_match('/8240/', $row['oltmodel']))
					$list[$idx]['gponoltnumportf'] = $this->OLT8240_format($row['gponoltnumport']);

		return $list;
	}

	public function GetGponOnu2Customers($gpononuid) {
		return $this->DB->GetAll("SELECT g2c.id,c.id as customersid,
				(" . $this->DB->Concat('c.lastname', "' '", 'c.name') . ") as customersname
			FROM " . self::SQL_TABLE_GPONONU2CUSTOMERS . " g2c
			JOIN customers c On c.id = g2c.customersid
			WHERE g2c.gpononuid = ? ORDER BY g2c.id ASC", array($gpononuid));
	}

	public function GponOnuClearCustomers($gpononuid) {
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONU2CUSTOMERS
			. ' WHERE gpononuid = ?', array($gpononuid));
		$this->Log(4, 'gpononu', $gpononuid, 'customers removed');
	}

	public function GponOnuAddCustomer($gpononuid, $customerid) {
		$gpononuid = intval($gpononuid);
		$customerid = intval($customerid);
		if ($gpononuid && $customerid && !($this->DB->GetOne('SELECT COUNT(id) AS liczba FROM ' . self::SQL_TABLE_GPONONU2CUSTOMERS
				. ' WHERE gpononuid=? AND customersid=?', array($gpononuid, $customerid)))) {
				$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' (gpononuid, customersid)
					VALUES (?, ?)', array($gpononuid, $customerid));
				$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'customers added: ' . $customerid);
			}
	}

	public function GetGponOnuForCustomer($ownerid) {
		$result = $this->DB->GetRow('SELECT g.*, gom.name AS model,
				(SELECT sum(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
					. ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports,
					gom.producer,
					(SELECT nd.name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						JOIN netdevices nd ON nd.id = go2o.netdevicesid
						WHERE go2o.gpononuid = g.id) AS gponolt,
					(SELECT go2o.numport FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						WHERE go2o.gpononuid=g.id) AS gponoltnumport,
					(SELECT nd.id AS name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
						JOIN netdevices nd ON nd.id = go2o.netdevicesid
						WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.gpononuid = g.id
			WHERE g2c.customersid = ?', array($ownerid));

		return $result;
	}

	public function GetGponOnuPhoneVoip($gpononuid) {
		$result = $this->DB->GetAll('SELECT v.id, v.login AS phone
			FROM voipaccounts v
			JOIN customers c ON c.id = v.ownerid
			JOIN ' . self::SQL_TABLE_GPONONU2CUSTOMERS . ' g2c ON g2c.customersid = c.id
			WHERE g2c.gpononuid = ?', array($gpononuid));

		return $result;
	}

	public function GetPhoneVoip($id) {
		if (intval($id))
			$result = $this->DB->GetRow('SELECT v.id, v.login, v.passwd, v.login AS phone
			FROM voipaccounts v
			WHERE v.id = ?', array($id));
		else
			$result = array();

		return $result;
	}

	public function GetPhoneVoipForCustomer($ownerid) {
		if (intval($ownerid))
			$result = $this->DB->GetAll('SELECT v.id, v.login AS phone
				FROM voipaccounts v
				JOIN customers c ON c.id = v.ownerid
				WHERE c.id=?', array($ownerid));
		else
			$result = array();
		return $result;
	}

	public function GetHostNameForCustomer($ownerid) {
		if (intval($ownerid))
			$result = $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
				FROM nodes n
				JOIN customers c ON c.id = n.ownerid
				WHERE c.id=?", array($ownerid));
		else
			$result = array();
		return $result;
	}

	public function GetHostForNetdevices() {
		return $this->DB->GetAll("SELECT n.id, (" . $this->DB->Concat('n.name', "' / '", 'INET_NTOA(ipaddr)') . ") AS host
			FROM nodes n
			LEFT JOIN " . self::SQL_TABLE_GPONONU . " g1 ON g1.host_id1 = n.id
			LEFT JOIN " . self::SQL_TABLE_GPONONU . " g2 ON g2.host_id2 = n.id
			WHERE g1.host_id1 IS NULL AND g2.host_id2 IS NULL AND n.ownerid = 0
			ORDER BY host");
	}

	public function IsNodeIdNetDevice($id) {
		if ($this->DB->GetOne("SELECT id FROM nodes WHERE ownerid = 0 AND id = ?", array($id)))
			return true;
		else
			return false;
	}

	public function GetGponOnuCountOnPort($netdeviceid, $numport) {
		$netdeviceid = intval($netdeviceid);
		$numport = intval($numport);
		return $this->DB->GetOne('SELECT COUNT(gpononuid) AS CountOnPort FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
			WHERE go2o.netdevicesid = ? AND go2o.numport = ?', array($netdeviceid, $numport));
	}

	public function GetGponOnuList($order = 'name,asc') {
		list ($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
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
		$where = ' WHERE 1=1 ';

		if ($netdevlist = $this->DB->GetAll('
			SELECT g.*, gom.name AS model,gom.producer,
				(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
					. ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports, gp.name AS profil,
				(SELECT nd.name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponolt,
				(SELECT go.id FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					JOIN ' . self::SQL_TABLE_GPONOLT . ' go ON go.netdeviceid = nd.id
					WHERE go2o.gpononuid = g.id) AS gponoltid,
				(SELECT nd.model FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponoltmodel,
				(SELECT go2o.numport FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
					WHERE go2o.gpononuid = g.id) AS gponoltnumport,
				(SELECT nd.id AS name FROM ' . self::SQL_TABLE_GPONONU2OLT . ' go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid=g.id) AS gponoltnetdevicesid
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			LEFT JOIN ' . self::SQL_TABLE_GPONOLTPROFILES . ' gp ON gp.id = g.gponoltprofilesid
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid ' . $where
			. ($sqlord != '' ? $sqlord . ' ' . $direction : ''))) {
			foreach ($netdevlist as $idx => $row)
				if (preg_match('/8240/', $row['gponoltmodel'])) //jesli duzy olt to formatujemy 1/1,...
					$netdevlist[$idx]['gponoltnumportf'] = $this->OLT8240_format($row['gponoltnumport']);
		}

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;
		return $netdevlist;
	}

	public function OLT8240_format($port) {
		$a = ceil(intval($port) / 4);
		$b = intval($port) % 4;
		if (!$b)
			$b = 4;

		return $a . '/'. $b;
	}

	public function GetNotConnectedOnu() {
		return $this->DB->GetAll('SELECT g.*, gom.name AS model, gom.producer,
				(SELECT SUM(portscount) FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
					. ' WHERE gpononumodelsid = g.gpononumodelsid) AS ports
			FROM ' . self::SQL_TABLE_GPONONU . ' g
			JOIN ' . self::SQL_TABLE_GPONONUMODELS . ' gom ON gom.id = g.gpononumodelsid
			WHERE g.id NOT IN (SELECT DISTINCT gpononuid FROM ' . self::SQL_TABLE_GPONONU2OLT . ')
			ORDER BY name');
	}

	public function GetGponOnu($id) {
		$result = $this->DB->GetRow("SELECT g.*, d.name AS netdevname, gom.name AS model,
				(SELECT SUM(portscount) FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
					. " WHERE gpononumodelsid = g.gpononumodelsid) AS ports, gom.producer,
				(SELECT nd.name FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponolt,
				(SELECT nd.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid,
				(SELECT go2o.numport FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					WHERE go2o.gpononuid = g.id) AS gponoltnumport,
				(SELECT go.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					JOIN " . self::SQL_TABLE_GPONOLT . " go ON go.netdeviceid = nd.id
					WHERE go2o.gpononuid = g.id) AS gponoltid,
				(SELECT gop.name FROM " . self::SQL_TABLE_GPONOLTPROFILES . " gop
					WHERE gop.id = g.gponoltprofilesid) AS profil_olt,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id = g.voipaccountsid1) AS voipaccountsid1_phone,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id = g.voipaccountsid2) AS voipaccountsid2_phone,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id1) AS host_id1_host,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id2) AS host_id2_host
			FROM " . self::SQL_TABLE_GPONONU . " g
			JOIN " . self::SQL_TABLE_GPONONUMODELS . " gom ON gom.id = g.gpononumodelsid
			LEFT JOIN netdevices d ON d.id = g.netdevid
			WHERE g.id = ?", array($id));
		$result['portdetails'] = $this->DB->GetAllByKey("SELECT pt.name, portscount FROM " . self::SQL_TABLE_GPONONU . " o
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " t2m ON o.gpononumodelsid = t2m.gpononumodelsid
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . "  pt ON pt.id = gpononuportstypeid
			WHERE o.id = ?", 'name', array($id));

		$result['properties'] = unserialize($result['properties']);
		$users_table = $this->DB->ResourceExists('vusers', LMSDB::RESOURCE_TYPE_VIEW)
			? 'vusers' : 'users';
		$result['createdby'] = $this->DB->GetOne('SELECT name FROM ' . $users_table . ' WHERE id=?', array($result['creatorid']));
		$result['modifiedby'] = $this->DB->GetOne('SELECT name FROM ' . $users_table . ' WHERE id=?', array($result['modid']));
		$result['creationdateh'] = date('Y/m/d, H:i', $result['creationdate']);
		$result['moddateh'] = date('Y/m/d, H:i', $result['moddate']);

		return $result;
	}

	public function GetGponOnuProperties($id) {
		$properties = $this->DB->GetOne("SELECT properties FROM " . self::SQL_TABLE_GPONONU
			. " WHERE id = ?", array($id));
		return unserialize($properties);
	}

	public function GetGponOnuFromName($name) {
		$result = $this->DB->GetRow("SELECT g.*,
				(SELECT SUM(portscount) FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
					. " WHERE gpononumodelsid = g.gpononumodelsid) AS ports,
				(SELECT nd.name FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponolt,
				(SELECT nd.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					WHERE go2o.gpononuid = g.id) AS gponoltnetdevicesid,
				(SELECT go2o.numport FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					WHERE go2o.gpononuid = g.id) AS gponoltnumport,
				(SELECT go.id FROM " . self::SQL_TABLE_GPONONU2OLT . " go2o
					JOIN netdevices nd ON nd.id = go2o.netdevicesid
					JOIN " . self::SQL_TABLE_GPONOLT . " go ON go.netdeviceid = nd.id
					WHERE go2o.gpononuid=g.id) AS gponoltid,
				(SELECT gop.name FROM " . self::SQL_TABLE_GPONOLTPROFILES . " gop
					WHERE gop.id = g.gponoltprofilesid) AS profil_olt,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id=g.voipaccountsid1) AS voipaccountsid1_phone,
				(SELECT va.login AS phone FROM voipaccounts va
					WHERE va.id=g.voipaccountsid2) AS voipaccountsid2_phone,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id1) AS host_id1_host,
				(SELECT (" . $this->DB->Concat('no.name', "' / '", 'INET_NTOA(ipaddr)') . ") FROM nodes no
					WHERE no.id=g.host_id2) AS host_id2_host
			FROM " . self::SQL_TABLE_GPONONU . " g
			WHERE g.name = ?", array($name));
		if (!empty($result))
			$result['portdetails'] = $this->DB->GetAllByKey("
				SELECT pt.name, portscount FROM " . self::SQL_TABLE_GPONONU . " o
				JOIN " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " t2m ON o.gpononumodelsid = t2m.gpononumodelsid
				JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " pt ON pt.id = gpononuportstypeid
				WHERE o.id = ?", 'name', array($result['id']));

		return $result;
	}

	public function GponOnuNameExists($name) {
		return ($this->DB->GetOne("SELECT * FROM " . self::SQL_TABLE_GPONONU
			. " WHERE name = ?", array($name)) ? true : false);
	}

	public function GponOnuExists($id) {
		return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONU
			. ' WHERE id = ?', array($id)) ? true : false);
	}

	public function GponOnuAdd($gpononudata) {
		$gpononudata['onu_description'] = iconv('UTF-8', 'ASCII//TRANSLIT', $gpononudata['onu_description']);
		$gpononudata['gpononumodelsid'] = intval($gpononudata['gpononumodelid']);
		$gpononumodelid = 1;
		if (empty($gpononudata['gpononumodelsid'])) {
			$gpononudata['onu_model'] = trim($gpononudata['onu_model']);
			if (strlen($gpononudata['onu_model'])) {
				$result = $this->DB->GetRow("SELECT id FROM " . self::SQL_TABLE_GPONONUMODELS
				. " WHERE name = ?", array($gpononudata['onu_model']));
				$gpononudata['gpononumodelsid'] = intval($result['id']);
				if (empty($gpononudata['gpononumodelsid'])) {
					if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUMODELS . ' (name)
						VALUES (?)', array($gpononudata['onu_model']))) {
						$gpononudata['gpononumodelsid'] = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUMODELS);
						$this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononudata['gpononumodelsid'], 'model added via onuadd: ' . $gpononudata['onu_model']);
					}
				}
			}
		}
		$gpononudata['gpononumodelsid'] = intval($gpononudata['gpononumodelsid']);
		if (empty($gpononudata['gpononumodelsid']))
			$gpononudata['gpononumodelsid'] = 1;
		$gpononudata['gponoltprofilesid'] = intval($gpononudata['gponoltprofilesid']) ? $gpononudata['gponoltprofilesid']: NULL;
		$gpononudata['voipaccountsid1'] = intval($gpononudata['voipaccountsid1']) ? $gpononudata['voipaccountsid1']: NULL;
		$gpononudata['voipaccountsid2'] = intval($gpononudata['voipaccountsid2']) ? $gpononudata['voipaccountsid2']: NULL;
		$gpononudata['host_id1'] = intval($gpononudata['host_id1']) ? $gpononudata['host_id1']: NULL;
		$gpononudata['host_id2'] = intval($gpononudata['host_id2']) ? $gpononudata['host_id2']: NULL;
		if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONU . ' (name, gpononumodelsid, password, autoprovisioning, onudescription, gponoltprofilesid,
			voipaccountsid1, voipaccountsid2, host_id1, host_id2, creatorid, creationdate, netdevid, xmlprovisioning, properties)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?NOW?, ?, ?, ?)',
				array(
					$gpononudata['name'],
					$gpononudata['gpononumodelsid'],
					$gpononudata['password'],
					$gpononudata['autoprovisioning'],
					$gpononudata['onu_description'],
					$gpononudata['gponoltprofilesid'],
					$gpononudata['voipaccountsid1'],
					$gpononudata['voipaccountsid2'],
					$gpononudata['host_id1'],
					$gpononudata['host_id2'],
					$this->AUTH->id,
					empty($gpononudata['netdevid']) ? null : $gpononudata['netdevid'],
					$gpononudata['xmlprovisioning'],
					$gpononudata['properties'],
		))) {
			$id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONU);
			$dump = var_export($gpononudata, true);
			$this->Log(4, self::SQL_TABLE_GPONONU, $id, 'added '.$gpononudata['name'], $dump);
			return $id;
		} else
			return false;
	}

	public function GponOnuDescriptionUpdate($id, $onudescription) {
		$onudescription = iconv('UTF-8', 'ASCII//TRANSLIT', $onudescription);
		if(intval($id)) {
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET onudescription = ?
				WHERE id = ?', array($onudescription, $id));
			$this->Log(4, self::SQL_TABLE_GPONONU, $id, 'description set: ' . $onudescription);
		}
	}

	public function GponOnuVoipUpdate($id, $port, $voipid) {
		if (intval($port)) {
			$colname = 'voipaccountsid' . $port;
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET '. $colname .' = ?
				WHERE id = ?', array($voipid, $id));
			$this->Log(4, self::SQL_TABLE_GPONONU, $id, 'voip '.$port.' set: '.$voipid);
		}
	}

	public function GponOnuProfileUpdateByName($id, $profile) {
		if (intval($id) && ($pid = $this->DB->GetOne('SELECT id FROM ' . self::SQL_TABLE_GPONOLTPROFILES
			. ' WHERE name = ?', array($profile)))) {
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET gponoltprofilesid = ?
				WHERE id=?', array($pid, $id));
			$this->Log(4, self::SQL_TABLE_GPONONU, $id, 'profile set: '.$profile);
		}
	}

	public function GponOnuUpdate($gpononudata) {
		$gpononudata['onudescription'] = iconv('UTF-8', 'ASCII//TRANSLIT', $gpononudata['onudescription']);
		$gpononudata['gponoltprofilesid'] = intval($gpononudata['gponoltprofilesid']) ? $gpononudata['gponoltprofilesid']: NULL;
		$gpononudata['voipaccountsid1'] = intval($gpononudata['voipaccountsid1']) ? $gpononudata['voipaccountsid1']: NULL;
		$gpononudata['voipaccountsid2'] = intval($gpononudata['voipaccountsid2']) ? $gpononudata['voipaccountsid2']: NULL;
		$gpononudata['host_id1'] = intval($gpononudata['host_id1']) ? $gpononudata['host_id1']: NULL;
		$gpononudata['host_id2'] = intval($gpononudata['host_id2']) ? $gpononudata['host_id2']: NULL;
		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET gpononumodelsid=?, password=?, autoprovisioning=?,
				onudescription=?, gponoltprofilesid=?, voipaccountsid1=?, voipaccountsid2=?,
				host_id1=?, host_id2=?, netdevid=?, xmlprovisioning=?, properties=?, modid=?, moddate=?NOW?
				WHERE id=?',
				array(
					intval($gpononudata['gpononumodelsid']),
					$gpononudata['password'],
					$gpononudata['autoprovisioning'],
					$gpononudata['onudescription'],
					$gpononudata['gponoltprofilesid'],
					$gpononudata['voipaccountsid1'],
					$gpononudata['voipaccountsid2'],
					$gpononudata['host_id1'],
					$gpononudata['host_id2'],
					empty($gpononudata['netdevid']) ? null : $gpononudata['netdevid'],
					$gpononudata['xmlprovisioning'],
					$gpononudata['properties'],
					$this->AUTH->id,
					$gpononudata['id']
				));
		$dump = var_export($gpononudata, true);
		$this->Log(4, self::SQL_TABLE_GPONONU, $gpononudata['id'], 'updated '.$gpononudata['name'], $dump);
	}

	public function GetGponOnuCheckList($devid = 0) {
		$onu_list = array();
		$olts = $this->GetGponAllOlt($devid);
		if (is_array($olts) && !empty($olts)) {
			$i = 0;
			foreach ($olts as $k => $v) {
				$this->snmp->clear_options();
				$gponoltid = null;
				if (is_array($v) && !empty($v)) {
					$this->snmp->set_options($v);
					$olt_name = $v['name'];
					$olt_netdevicesid = $v['netdevicesid'];
					$gponoltid = $v['id'];
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
							$this->AddGponOltProfile($v_p, $gponoltid);
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
												$gponoltprofiles_temp = $this->GetGponOltProfiles($gponoltid);
												$this->snmp->ONU_SetProfile($olt_port,$onu_id, $gponoltprofiles_temp[$gpon_onu_in_db['gponoltprofilesid']]['name']);
												
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

	public function GetDuplicateOnu($devid = 0) {
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

	public function GetGponAutoScript($debug) {
		$output = '';
		$output .= $this->GetDuplicateOnu();
		$onu_list = array();
		$olts = $this->GetGponAllOlt();

		$podlaczam = 0;
		if ($debug == 1)
			$output .= '<br />OLT## Wczytanie wszystkich OLT z bazy danych';
		if (is_array($olts) && !empty($olts)) {
			$i = 0;
			foreach ($olts as $k => $v) {
				$this->snmp->clear_options();
				$gponoltid = null;
				if (is_array($v) && !empty($v)) {
					$this->snmp->set_options($v);
					$olt_name = $v['name'];
					$olt_netdevicesid = $v['netdevicesid'];
					$gponoltid = $v['id'];
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
							$this->AddGponOltProfile($v_p, $gponoltid);
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

	public function GetSNMPresultMsg($result_array = array()) {
		$result = '<b>OK</b>';
		if (is_array($result_array) && count($result_array))
			foreach ($result_array as $k => $v)
				if ($v == false)
					$result = '<font color="red"><b>ERROR</b></font>';
		return $result;
	}

	public function GponOnuSetAutoScript($gpononuid, $autoscript = 1) {
		if (intval($gpononuid)) {
			$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONU . ' SET autoscript = ?
				WHERE id = ?', array($autoscript, $gpononuid));
			$this->Log(4, self::SQL_TABLE_GPONONU, $gpononuid, 'autoscript set to '.$autoscript);
		}
	}

	public function GetGponAllOlt($olt = 0) {
		$where = ' WHERE 1=1';
		if (intval($olt))
			$where .= ' AND d.id = ' . $olt;

		$result = $this->DB->GetAll('SELECT g.*, d.name, d.id AS netdevicesid
			FROM ' . self::SQL_TABLE_GPONOLT . ' g
			JOIN netdevices d ON d.id = g.netdeviceid' . $where);
		return $result;
	}

	public function IsGponOnuSerialConected($gponoltid, $olt_port, $onu_id, $onu_serial) {
		return ($this->DB->GetOne("SELECT g2o.* FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id = g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU . " go ON go.id = g2o.gpononuid
			WHERE g.id = ? AND g2o.numport = ? AND go.onuid = ? AND go.name = ?",
			array($gponoltid, $olt_port, $onu_id, $onu_serial)) ? true : false);
	}

	public function IsGponOnuSerialConectedOtherOlt($gponoltid, $onu_serial) {
		return ($this->DB->GetOne("SELECT g2o.* FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id=g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU . " go ON go.id=g2o.gpononuid
			WHERE g.id <> ? AND go.name = ?",
			array($gponoltid, $onu_serial)) ? true : false);
	}

	public function GponGetOnuNameFromOltOnuId($gponoltid, $olt_port, $onu_id) {
		$result = $this->DB->GetRow("SELECT go.name FROM " . self::SQL_TABLE_GPONONU2OLT . " g2o
			JOIN netdevices n ON n.id = g2o.netdevicesid
			JOIN " . self::SQL_TABLE_GPONOLT . " g ON g.netdeviceid = n.id
			JOIN " . self::SQL_TABLE_GPONONU2OLT . " go ON go.id = g2o.gpononuid
			WHERE g.id = ? AND g2o.numport = ? AND go.onuid = ?",
			array($gponoltid, $olt_port, $onu_id));
		return $result;
	}

	public function GetNotGponOnuDevices($gpononuid = null) {
		return $this->DB->GetAll('SELECT d.id, d.name FROM netdevices d
			LEFT JOIN ' . self::SQL_TABLE_GPONONU . ' o ON o.netdevid = d.id
			WHERE o.netdevid IS NULL OR o.id = ?
			ORDER BY name', array($gpononuid));
	}

	public function GponOnuRadiusDisconnect($id) {
		$rdata = $this->DB->GetRow("SELECT INET_NTOA(ipaddr) AS nas, numport AS oltport, g.name, d.secret
				FROM " . self::SQL_TABLE_GPONONU . " g
			JOIN " . self::SQL_TABLE_GPONONU2OLT . " go ON go.gpononuid = g.id
			JOIN netdevices d ON go.netdevicesid = d.id
			JOIN nodes n ON n.netdev = d.id
			WHERE g.id = ? AND ownerid = 0", array($id));

		$cmd = ConfigHelper::getConfig('gpon-dasan.radius_disconnect_helper',
			"echo \"Dasan-Gpon-Olt-Id=%port%,Dasan-Gpon-Onu-Serial-Num=%sn%\"| radclient -r 1 %nas% disconnect %secret%");
		$cmd = str_replace(array('%port%', '%sn%', '%nas%', '%secret%'),
			array($rdata['oltport'], $rdata['name'], $rdata['nas'], $rdata['secret']), $cmd);
		$res = 0;
		system($cmd . " >/dev/null", $res);

		return $res;
	}

	//--------------ONU_MODELS----------------
	public function GetGponOnuModelsList($order = 'name,asc') {
		list ($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
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
		$where = ' WHERE 1=1 ';
		$netdevlist = $this->DB->GetAllByKey('SELECT *
			FROM ' . self::SQL_TABLE_GPONONUMODELS . ' g ' . $where
			. ($sqlord != '' ? $sqlord . ' ' . $direction : ''), 'id');

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}

	public function GponOnuModelsExists($id) {
		return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUMODELS
			. ' WHERE id = ?', array($id)) ? true : false);
	}

	public function CountGponOnuModelsLinks($id) {
		return $this->DB->GetOne('SELECT COUNT(*) FROM ' . self::SQL_TABLE_GPONONU
			. ' WHERE gpononumodelsid = ?', array($id));
	}

	public function GetGponOnuModels($id) {
		return $this->DB->GetRow('SELECT g.* FROM ' . self::SQL_TABLE_GPONONUMODELS . ' g
			WHERE g.id = ?', array($id));
	}

	public function GponOnuModelsUpdate($gpononumodelsdata) {
		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONUMODELS . ' SET name=?, description=?, producer=?, xmltemplate=?
			WHERE id=?', array($gpononumodelsdata['name'], $gpononumodelsdata['description'],
				$gpononumodelsdata['producer'], $gpononumodelsdata['xmltemplate'], $gpononumodelsdata['id']));
		$dump = var_export($gpononumodelsdata, true);
		$this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononumodelsdata['id'], 'updated ' . $gpononudata['name'], $dump);
	}

	public function GponOnuModelsAdd($gpononumodelsdata) {
		if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUMODELS
			. ' (name, description, producer, xmltemplate) VALUES (?, ?, ?, ?)',
			array($gpononumodelsdata['name'], $gpononumodelsdata['description'],
				$gpononumodelsdata['producer'], $gpononumodelsdata['xmltemplate']))) {
			$id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUMODELS);
			$dump = var_export($gpononumodelsdata, true);
			$this->Log(4, self::SQL_TABLE_GPONONUMODELS, $id, 'added ' . $gpononudata['name'], $dump);
			return $id;
		} else
			return false;
	}

	public function DeleteGponOnuModels($id) {
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUMODELS . ' WHERE id=?', array($id));
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' WHERE gpononumodelsid=?', array($id));
		$this->Log(4, self::SQL_TABLE_GPONONUMODELS, $id, 'model removed');
		$this->DB->CommitTrans();
	}

	public function GetGponOnuModelPorts($model) {
		return $this->DB->GetAllByKey("SELECT p.id, p.name, portscount
			FROM " . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . " p2m
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " p ON p.id = p2m.gpononuportstypeid
			JOIN " . self::SQL_TABLE_GPONONUMODELS . " m ON m.id = p2m.gpononumodelsid
			WHERE m.id = ? ORDER BY name", 'name', array($model));
	}

	public function GetGponOnuPorts($id) {
		return $this->DB->GetAllByKey("SELECT p.*, t.name, " . $this->DB->Concat('t.name', "'.'", 'p.portid') . " AS portname
			FROM " . self::SQL_TABLE_GPONONUPORTS . " p
			JOIN " . self::SQL_TABLE_GPONONUPORTTYPES . " t ON t.id = p.typeid
			WHERE p.onuid = ?
			ORDER BY p.typeid, p.portid", 'portname', array($id));
	}

	public function GetGponOnuAllPorts($modelports, $onuports) {
		$portsettings = array();
		foreach ($modelports as $porttype => $portdetails)
			for ($i = 1; $i <= $portdetails['portscount']; $i++) {
				$portname = $porttype . '.' . $i;
				if (isset($onuports[$portname]))
					$portsettings[$portname] = $onuports[$portname];
				else {
					$portsettings[$portname] = array(
						'onuid' => $_GET['id'],
						'typeid' => $portdetails['id'],
						'portid' => $i,
						'portdisable' => 0,
						'name' => $porttype,
						'portname' => $portname,
					);
				}
			}
		return $portsettings;
	}

	public function UpdateGponOnuPorts($onu, $portsettings) {
		$this->DB->Execute("DELETE FROM " . self::SQL_TABLE_GPONONUPORTS . " WHERE onuid = ?", array($onu));
		$porttypes = $this->DB->GetAllByKey("SELECT * FROM " . self::SQL_TABLE_GPONONUPORTTYPES, 'name');
		foreach ($portsettings as $portname => $port) {
			list ($porttype, $portid) = explode('.', $portname);
			$porttypeid = $porttypes[$porttype]['id'];
			$dbfields = array();
			foreach ($port as $property => $value) {
				switch ($property) {
					case 'portdisable':
						if ($value == 1)
							$dbfields['portdisable'] = 1;
						break;
				}
			}
			if (!empty($dbfields)) {
				$args = array($onu, $porttypeid, $portid);
				$args = array_merge($args, array_values($dbfields));
				$this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS
					. " (onuid, typeid, portid, " . implode(', ', array_keys($dbfields)) . ")
					VALUES (?, ?, ?, " . implode(', ', array_fill(0, count($dbfields), '?')) . ")", $args);
			}
		}
	}

	public function EnableGponOnuPortDB($onu, $porttype, $port) {
		if (!($rows = $this->DB->Execute("UPDATE " . self::SQL_TABLE_GPONONUPORTS . " SET portdisable = 0
			WHERE onuid = ? AND typeid = ? AND portid = ?",
			array($onu, $porttype, $port))))
			$rows = $this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS . " (onuid, typeid, portid, portdisable)
				VALUES(?, ?, ?, 0)", array($onu, $porttype, $port));
		if ($rows)
			$this->Log(4, self::SQL_TABLE_GPONONU, $onu, 'port enabled: '.$port.', typ: '.$porttype);
	}

	public function DisableGponOnuPortDB($onu, $porttype, $port) {
		if (!($rows = $this->DB->Execute("UPDATE " . self::SQL_TABLE_GPONONUPORTS . " SET portdisable = 1
			WHERE onuid = ? AND typeid = ? AND portid = ?",
			array($onu, $porttype, $port))))
			$rows = $this->DB->Execute("INSERT INTO " . self::SQL_TABLE_GPONONUPORTS . " (onuid, typeid, portid, portdisable)
				VALUES(?, ?, ?, 1)", array($onu, $porttype, $port));
		if ($rows)
			$this->Log(4, self::SQL_TABLE_GPONONU, $onu, 'port disabled: '.$port.', typ: '.$porttype);
	}

	public function GetGponOnuPortsType() {
		return $this->DB->GetAll('SELECT gpt.* FROM ' . self::SQL_TABLE_GPONONUPORTTYPES . ' gpt
			ORDER BY gpt.id ASC');
	}

	public function GetGponOnuPortsType2Models($gpononumodelid) {
		return $this->DB->GetAllByKey('SELECT gpt2m.* FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS . ' gpt2m
			WHERE gpt2m.gpononumodelsid = ?
			ORDER BY gpt2m.gpononuportstypeid ASC', 'gpononuportstypeid',
			array($gpononumodelid));
	}

	public function SetGponOnuPortsType2Models($gpononumodelid,$porttypes) {
		if (intval($gpononumodelid)) {
			$this->DB->BeginTrans();
			$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
				. ' WHERE gpononumodelsid=?', array($gpononumodelid));
			if (is_array($porttypes) && count($porttypes))
				foreach ($porttypes as $k => $v)
					if (intval($v))
						$this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUPORTTYPE2MODELS
							. ' (gpononuportstypeid, gpononumodelsid, portscount)
							VALUES (?, ?, ?)', array(intval($k), $gpononumodelid, $v));
			$dump = var_export($porttypes, true);
			$this->Log(4, self::SQL_TABLE_GPONONUMODELS, $gpononumodelid, 'ports type updated', $dump);
			$this->DB->CommitTrans();
		}
	}

	//--------------GPON_TV----------------
	public function GetGponOnuTvList($order='name,asc') {
		list ($order, $direction) = sscanf($order, '%[^,],%s');

		($direction == 'desc') ? $direction = 'desc' : $direction = 'asc';

		switch ($order) {
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
		$where = ' WHERE 1=1 ';
		$netdevlist = $this->DB->GetAll('SELECT g.id,inet_ntoa(g.ipaddr) AS ipaddr, g.channel
			FROM ' . self::SQL_TABLE_GPONONUTV . ' g ' . $where
			.($sqlord != '' ? $sqlord.' '.$direction : ''));

		$netdevlist['total'] = sizeof($netdevlist);
		$netdevlist['order'] = $order;
		$netdevlist['direction'] = $direction;

		return $netdevlist;
	}

	public function GetGponOnuTv($id) {
		$result = $this->DB->GetRow('SELECT g.id, INET_NTOA(g.ipaddr) AS ipaddr, g.channel
			FROM ' . self::SQL_TABLE_GPONONUTV . ' g
			WHERE g.id = ?', array($id));
		return $result;
	}

	public function GponOnuTvUpdate($gpononutvdata) {
		$this->DB->Execute('UPDATE ' . self::SQL_TABLE_GPONONUTV . ' SET ipaddr = INET_ATON(?), channel = ?
				WHERE id = ?', array($gpononutvdata['ipaddr'], $gpononutvdata['channel'], $gpononutvdata['id']));
		$this->Log(4, self::SQL_TABLE_GPONONUTV, $gpononutvdata['id'], 'updated: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
	}

	public function GponOnuTvAdd($gpononutvdata) {
		if ($this->DB->Execute('INSERT INTO ' . self::SQL_TABLE_GPONONUTV . ' (ipaddr,channel)
				VALUES (INET_ATON(?), ?)', array($gpononutvdata['ipaddr'], $gpononutvdata['channel']))) {
			$id = $this->DB->GetLastInsertID(self::SQL_TABLE_GPONONUTV);
			$this->Log(4, self::SQL_TABLE_GPONONUTV, $id, 'added: '.$gpononutvdata['channel'].' - '.$gpononutvdata['ipaddr']);
			return $id;
		} else
			return false;
	}

	public function DeleteGponOnuTv($id) {
		$this->DB->BeginTrans();
		$this->DB->Execute('DELETE FROM ' . self::SQL_TABLE_GPONONUTV . ' WHERE id = ?', array($id));
		$this->Log(4, self::SQL_TABLE_GPONONUTV, $id, 'deleted');
		$this->DB->CommitTrans();
	}

	public function GponOnuTvIpExists($ip,$id=0) {
		if (!intval($id))
			return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
				. ' WHERE ipaddr = INET_ATON(?)', array($ip)) ? true : false);
		else
			return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
				. ' WHERE ipaddr = INET_ATON(?) AND id <> ?', array($ip, $id)) ? true : false);
	}

	public function GponOnuTvExists($id) {
		return ($this->DB->GetOne('SELECT * FROM ' . self::SQL_TABLE_GPONONUTV
			. ' WHERE id=?', array($id)) ? true : false);
	}

	public function GetGponOnuTvChannel($ipaddr) {
		$ipaddr = trim($ipaddr);
		$result = $this->DB->GetRow("SELECT g.channel
			FROM " . self::SQL_TABLE_GPONONUTV . " g
			WHERE g.ipaddr = INET_ATON(?)", array($ipaddr));
		return $result;
	}

	public function IsGponOnuTvMulticast($ipaddr) {
		$address = explode('.',$ipaddr);
		return is_array($address) && count($address) && intval($address[0]);
	}

	public function IsNotOldOnuModel($model) {
		$oldonu = array('H640V',
			'H640GV',
			'H640R',
			'H640GR',
			'H640RW',
			'H645A',
			'H645B',
			'H640GW');
		return !in_array($model, $oldonu);
	}

	public function OnuModelWithRF($model) {
		$onurf = array('H640GR',
			'H640GR-02',
			'H640RW',
			'H640RW-02');
		return in_array($model, $onurf);
	}

	public function GetGponOnuLastAuth($onuid) {
		return $this->DB->GetAll("SELECT * FROM " . self::SQL_TABLE_GPONAUTHLOG
			. " WHERE onuid = ? ORDER BY time DESC", array($onuid));
	}

	public function GponOnuXmlProvisioning($params) {
		if (!isset($params['id']))
			return -1;

		$id = intval($params['id']);
		if (empty($id))
			return -1;

		$cmd = ConfigHelper::getConfig('gpon-dasan.xml_provisioning_helper', SYS_DIR . DIRECTORY_SEPARATOR . 'plugins'
			. DIRECTORY_SEPARATOR . LMSGponDasanPlugin::plugin_directory_name . DIRECTORY_SEPARATOR
			. 'bin' . DIRECTORY_SEPARATOR . 'lms-xml-provisioning.php -i %id%');
		$cmd = str_replace('%id%', $id, $cmd);
		$res = 0;
		system($cmd . " >/dev/null", $res);
		return $res;
	}
}

?>
