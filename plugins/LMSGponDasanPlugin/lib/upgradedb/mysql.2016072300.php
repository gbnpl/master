<?php

$this->BeginTrans();

$this->Execute("
	CREATE TABLE gpondasanauthlog (
		id int(11) NOT NULL AUTO_INCREMENT,
		time datetime NULL DEFAULT NULL,
		onuid int(11) NOT NULL,
		nas varchar(15) NOT NULL DEFAULT '',
		oltport int(11),
		onuoltid int(11),
		version varchar(20),
		PRIMARY KEY (id),
		KEY gpondasanauthlog_onuid_time (onuid, time DESC)
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanolts (
		id int(11) NOT NULL AUTO_INCREMENT,
		snmp_version tinyint(4) NOT NULL,
		snmp_description varchar(255) NOT NULL,
		snmp_host varchar(100) NOT NULL,
		snmp_community varchar(100) NOT NULL,
		snmp_auth_protocol enum('MD5','SHA','') NOT NULL,
		snmp_username varchar(255) NOT NULL,
		snmp_password varchar(255) NOT NULL,
		snmp_sec_level enum('noAuthNoPriv','authNoPriv','authPriv','') NOT NULL,
		snmp_privacy_passphrase varchar(255) NOT NULL,
		snmp_privacy_protocol enum('DES','AES','') NOT NULL,
		netdeviceid int(11) NOT NULL,
		PRIMARY KEY (id),
		FOREIGN KEY (netdeviceid) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanoltports (
		id int(11) NOT NULL AUTO_INCREMENT,
		gponoltid int(11) NOT NULL,
		numport int(11) NOT NULL,
		maxonu int(11) NOT NULL,
		PRIMARY KEY (id),
		KEY gponoltid (gponoltid)
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanoltprofiles (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		gponoltid int(11) DEFAULT NULL,
		PRIMARY KEY (id),
		FOREIGN KEY (gponoltid) REFERENCES gpondasanolts (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonus (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		gpononumodelsid int(11) NOT NULL,
		password varchar(100) NOT NULL,
		onuid smallint(11) NOT NULL DEFAULT '0',
		autoprovisioning tinyint(4) DEFAULT NULL,
		onudescription varchar(32) DEFAULT NULL,
		gponoltprofilesid int(11) DEFAULT NULL,
		voipaccountsid1 int(11) DEFAULT NULL,
		voipaccountsid2 int(11) DEFAULT NULL,
		autoscript tinyint(4) NOT NULL DEFAULT '0',
		host_id1 int(11),
		host_id2 int(11),
		creationdate int(11) NOT NULL DEFAULT '0',
		moddate int(11) NOT NULL DEFAULT '0',
		creatorid int(11) NOT NULL DEFAULT '0',
		modid int(11) NOT NULL DEFAULT '0',
		netdevid int(11) DEFAULT NULL,
		xmlprovisioning tinyint(4) DEFAULT 0,
		properties text DEFAULT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY name (name),
		KEY gpononumodelsid (gpononumodelsid),
		FOREIGN KEY (host_id1) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		FOREIGN KEY (host_id2) REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonu2customers (
		id int(11) NOT NULL AUTO_INCREMENT,
		gpononuid int(11) NOT NULL,
		customersid int(11) NOT NULL,
		PRIMARY KEY (id),
		KEY IXgpononuid (gpononuid),
		KEY IXcustomersid (customersid),
		FOREIGN KEY (gpononuid) REFERENCES gpondasanonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
		FOREIGN KEY (customersid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonu2olts (
		id int(11) NOT NULL AUTO_INCREMENT,
		netdevicesid int(11) NOT NULL,
		gpononuid int(11) NOT NULL,
		numport smallint(6) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY gpononuid (gpononuid),
		KEY gponoltid (netdevicesid)
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonumodels (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(32) NOT NULL,
		description text,
		producer varchar(64) DEFAULT NULL,
		xmltemplate mediumtext DEFAULT '',
		PRIMARY KEY (id)
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonuporttypes (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonuports (
		id int(11) NOT NULL AUTO_INCREMENT,
		onuid int(11) NOT NULL,
		typeid int(11) DEFAULT NULL,
		portid int(11) DEFAULT NULL,
		portdisable tinyint(4),
		PRIMARY KEY (id),
		UNIQUE KEY onu_type_port (onuid, typeid, portid),
		FOREIGN KEY (onuid) REFERENCES gpondasanonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
		FOREIGN KEY (typeid) REFERENCES gpondasanonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonuporttype2models (
		gpononuportstypeid int(11) DEFAULT NULL,
		gpononumodelsid int(11) NOT NULL,
		portscount int(11) NOT NULL,
		KEY gpononuportstypeid (gpononuportstypeid, gpononumodelsid),
		FOREIGN KEY (gpononuportstypeid) REFERENCES gpondasanonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		FOREIGN KEY (gpononumodelsid) REFERENCES gpondasanonumodels (id) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB
");

$this->Execute("
	CREATE TABLE gpondasanonutv (
		id int(11) NOT NULL AUTO_INCREMENT,
		ipaddr int(16) unsigned NOT NULL,
		channel varchar(100) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY ipaddr (ipaddr)
	) ENGINE=InnoDB
");

$this->Execute("DROP PROCEDURE IF EXISTS log_onu_auth");

$this->Execute("
DELIMITER $$
CREATE PROCEDURE log_onu_auth (username varchar(100), nas_ip varchar(15), olt int(11), onu int(11), ver char(20))
	BEGIN
		DECLARE  dev_id, onu_id int ;
		SELECT netdev INTO dev_id FROM nodes n WHERE inet_ntoa(ipaddr) = nas_ip AND ownerid = 0;
		SELECT id INTO onu_id FROM gpondasanonus WHERE name = username;
		INSERT INTO gpondasanauthlog (time, onuid, nas, oltport, onuoltid, version) VALUES (NOW(), onu_id, nas_ip, olt, onu, ver);
		UPDATE gpondasanonus SET onuid = onu WHERE id = onu_id;

		REPLACE INTO gpondasanonu2olts (netdevicesid, gpononuid, numport) VALUES (dev_id, onu_id, olt);
	END;
$$
DELIMITER ;
");

$this->Execute("INSERT INTO gpondasanauthlog (id, time, onuid, nas, oltport, onuoltid, version)
	(SELECT id, time, onuid, nas, oltport, onuoltid, version FROM gponauthlog)");
$this->Execute("INSERT INTO gpondasanolts (id, snmp_version, snmp_description, snmp_host, snmp_community,
	snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level, snmp_privacy_passphrase,
	snmp_privacy_protocol, netdeviceid)
	(SELECT id, snmp_version, snmp_description, snmp_host, snmp_community,
		snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level, snmp_privacy_passphrase,
		snmp_privacy_protocol, netdeviceid FROM gponolt)");
$this->Execute("INSERT INTO gpondasanoltports (id, gponoltid, numport, maxonu)
	(SELECT id, gponoltid, numport, maxonu FROM gponoltports)");
$this->Execute("INSERT INTO gpondasanoltprofiles (id, name, gponoltid)
	(SELECT id, name, gponoltid FROM gponoltprofiles)");
$this->Execute("INSERT INTO gpondasanonus (id, name, gpononumodelsid, password, onuid, autoprovisioning,
	onudescription, gponoltprofilesid, voipaccountsid1, voipaccountsid2, autoscript,
	host_id1, host_id2, creationdate, moddate, creatorid, modid, netdevid, xmlprovisioning,
	properties)
	(SELECT id, name, gpononumodelsid, password, onuid, autoprovisioning,
		onudescription, gponoltprofilesid, voipaccountsid1, voipaccountsid2, autoscript,
		host_id1, host_id2, creationdate, moddate, creatorid, modid, netdevid, xmlprovisioning,
		properties FROM gpononu)");
$this->Execute("INSERT INTO gpondasanonu2customers (id, gpononuid, customersid)
	(SELECT id, gpononuid, customersid FROM gpononu2customers)");
$this->Execute("INSERT INTO gpondasanonu2olts (id, netdevicesid, gpononuid, numport)
	(SELECT id, netdevicesid, gpononuid, numport FROM gpononu2olt)");
$this->Execute("INSERT INTO gpondasanonumodels (id, name, description, producer, xmltemplate)
	(SELECT id, name, description, producer, xmltemplate FROM gpononumodels)");
$this->Execute("INSERT INTO gpondasanonuporttypes (id, name) (SELECT id, name FROM gpononuportstype)");
$this->Execute("INSERT INTO gpondasanonuports (id, onuid, typeid, portid, portdisable)
	(SELECT id, onuid, typeid, portid, portdisable FROM gpononuport)");
$this->Execute("INSERT INTO gpondasanonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount)
	(SELECT gpononuportstypeid, gpononumodelsid, portscount FROM gpononuportstype2models)");
$this->Execute("INSERT INTO gpondasanonutv (id, ipaddr, channel)
	(SELECT id, ipaddr, channel FROM gpononutv)");

$this->Execute("DROP TABLE gpononutv");
$this->Execute("DROP TABLE gpononuportstype2models");
$this->Execute("DROP TABLE gpononuport");
$this->Execute("DROP TABLE gpononuportstype");
$this->Execute("DROP TABLE gpononumodels");
$this->Execute("DROP TABLE gpononu2olt");
$this->Execute("DROP TABLE gpononu2customers");
$this->Execute("DROP TABLE gpononu");
$this->Execute("DROP TABLE gponoltprofiles");
$this->Execute("DROP TABLE gponoltports");
$this->Execute("DROP TABLE gponolt");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072300', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
