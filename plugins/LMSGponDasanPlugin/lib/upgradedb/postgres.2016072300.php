<?php

$this->BeginTrans();

$this->Execute("
	CREATE SEQUENCE gpondasanauthlog_id_seq;
	CREATE TABLE gpondasanauthlog (
		id integer DEFAULT nextval('gponauthlog_id_seq'::text) NOT NULL,
		time timestamp with time zone,
		onuid integer NOT NULL,
		nas varchar(15) NOT NULL DEFAULT '',
		oltport integer,
		onuoltid integer,
		version varchar(20),
		PRIMARY KEY (id)
	);
	CREATE INDEX gpondasanauthlog_onuid_time ON gpondasanauthlog (onuid, time DESC);

	CREATE SEQUENCE gpondasanolts_id_seq;
	CREATE TABLE gpondasanolts (
		id integer DEFAULT nextval('gpondasanolts_id_seq'::text) NOT NULL,
		snmp_version smallint NOT NULL,
		snmp_description varchar(255) NOT NULL,
		snmp_host varchar(100) NOT NULL,
		snmp_community varchar(100) NOT NULL,
		snmp_auth_protocol auth_protocol NOT NULL,
		snmp_username varchar(255) NOT NULL,
		snmp_password varchar(255) NOT NULL,
		snmp_sec_level sec_level NOT NULL,
		snmp_privacy_passphrase varchar(255) NOT NULL,
		snmp_privacy_protocol privacy_protocol NOT NULL,
		netdeviceid integer NOT NULL
			REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE gpondasanoltports_id_seq;
	CREATE TABLE gpondasanoltports (
		id integer DEFAULT nextval('gpondasanoltports_id_seq'::text) NOT NULL,
		gponoltid integer NOT NULL,
		numport integer NOT NULL,
		maxonu integer NOT NULL,
		PRIMARY KEY (id)
	);
	CREATE INDEX gpondasanoltports_gponoltid_idx ON gpondasanoltports (gponoltid);

	CREATE SEQUENCE gpondasanoltprofiles_id_seq;
	CREATE TABLE gpondasanoltprofiles (
		id integer DEFAULT nextval('gpondasanoltprofiles_id_seq'::text) NOT NULL,
		name varchar(100) NOT NULL,
		gponoltid integer DEFAULT NULL
			REFERENCES gpondasanolts (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE gpondasanonus_id_seq;
	CREATE TABLE gpondasanonus (
		id integer DEFAULT nextval('gpondasanonus_id_seq'::text) NOT NULL,
		name varchar(100) NOT NULL,
		gpononumodelsid integer NOT NULL,
		password varchar(100) NOT NULL,
		onuid smallint NOT NULL DEFAULT 0,
		autoprovisioning smallint DEFAULT NULL,
		onudescription varchar(32) DEFAULT NULL,
		gponoltprofilesid integer DEFAULT NULL,
		voipaccountsid1 integer DEFAULT NULL,
		voipaccountsid2 integer DEFAULT NULL,
		autoscript smallint NOT NULL DEFAULT 0,
		host_id1 integer DEFAULT NULL
			REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		host_id2 integer DEFAULT NULL
			REFERENCES nodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		creationdate integer NOT NULL DEFAULT 0,
		moddate integer NOT NULL DEFAULT 0,
		creatorid integer NOT NULL DEFAULT 0,
		modid integer NOT NULL DEFAULT 0,
		netdevid integer DEFAULT NULL
			REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE,
		xmlprovisioning smallint DEFAULT 0,
		properties text DEFAULT NULL,
		PRIMARY KEY (id),
		UNIQUE (name)
	);
	CREATE INDEX gpondasanonus_gpononumodelsid_idx ON gpondasanonus (gpononumodelsid);

	CREATE SEQUENCE gpondasanonu2customers_id_seq;
	CREATE TABLE gpondasanonu2customers (
		id integer DEFAULT nextval('gpondasanonu2customers_id_seq'::text) NOT NULL,
		gpononuid integer NOT NULL
			REFERENCES gpondasanonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
		customersid integer NOT NULL
			REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY (id)
	);
	CREATE INDEX gpondasanonu2customers_gpononuid_idx ON gpondasanonu2customers (gpononuid);
	CREATE INDEX gpondasanonu2customers_customersid_idx ON gpondasanonu2customers (customersid);

	CREATE SEQUENCE gpondasanonu2olts_id_seq;
	CREATE TABLE gpondasanonu2olts (
		id integer DEFAULT nextval('gpondasanonu2olts_id_seq'::text) NOT NULL,
		netdevicesid integer NOT NULL,
		gpononuid integer NOT NULL,
		numport smallint NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (gpononuid)
	);
	CREATE INDEX gpondasanonu2olts_netdevicesid_idx ON gpondasanonu2olts (netdevicesid);

	CREATE SEQUENCE gpondasanonumodels_id_seq;
	CREATE TABLE gpondasanonumodels (
		id integer DEFAULT nextval('gpondasanonumodels_id_seq'::text) NOT NULL,
		name varchar(32) NOT NULL,
		description text,
		producer varchar(64) DEFAULT NULL,
		xmltemplate text DEFAULT '',
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE gpondasanonuporttypes_id_seq;
	CREATE TABLE gpondasanonuporttypes (
		id integer DEFAULT nextval('gpondasanonuporttypes_id_seq'::text) NOT NULL,
		name varchar(100) NOT NULL,
		PRIMARY KEY (id)
	);

	CREATE SEQUENCE gpondasanonuports_id_seq;
	CREATE TABLE gpondasanonuports (
		id integer DEFAULT nextval('gpondasanonuports_id_seq'::text) NOT NULL,
		onuid integer NOT NULL
			REFERENCES gpondasanonus (id) ON DELETE CASCADE ON UPDATE CASCADE,
		typeid integer DEFAULT NULL
			REFERENCES gpondasanonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		portid integer DEFAULT NULL,
		portdisable smallint,
		PRIMARY KEY (id),
		UNIQUE (onuid, typeid, portid)
	);

	CREATE TABLE gpondasanonuporttype2models (
		gpononuportstypeid integer DEFAULT NULL
			REFERENCES gpondasanonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
		gpononumodelsid integer NOT NULL
			REFERENCES gpondasanonumodels (id) ON DELETE CASCADE ON UPDATE CASCADE,
		portscount integer NOT NULL
	);
	CREATE INDEX gpondasanonuporttype2models_gpononuportstypeid_gpononumodelsid_idx ON gpondasanonuporttype2models (gpononuportstypeid, gpononumodelsid);

	CREATE SEQUENCE gpondasanonutv_id_seq;
	CREATE TABLE gpondasanonutv (
		id integer DEFAULT nextval('gpondasanonutv_id_seq'::text) NOT NULL,
		ipaddr bigint NOT NULL,
		channel varchar(100) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (ipaddr)
	);

	CREATE OR REPLACE FUNCTION log_onu_auth (username varchar(100), nas_ip varchar(15), olt integer, onu integer, ver varchar(20))
		RETURNS void AS $$
		DECLARE
			dev_id integer;
			onu_id integer;
		BEGIN
			SELECT INTO dev_id netdev FROM nodes n WHERE inet_ntoa(ipaddr) = nas_ip AND ownerid = 0;
			SELECT INTO onu_id id FROM gpononus WHERE name = username;
			INSERT INTO gpondasanauthlog (time, onuid, nas, oltport, onuoltid, version) VALUES(NOW(), onu_id, nas_ip::inet, olt, onu, ver);
			UPDATE gpondasanonus SET onuid = onu WHERE id = onu_id;

			UPDATE gpondasanonu2olts SET numport = olt WHERE gpononuid = onu_id AND netdevicesid = dev_id;
			IF NOT FOUND THEN
				INSERT INTO gpondasanonu2olts (netdevicesid, gpononuid, numport)
					VALUES(dev_id, onu_id, olt);
			END IF;
		END;
	$$ LANGUAGE plpgsql;
");

$this->Execute("
	INSERT INTO gpondasanauthlog (id, time, onuid, nas, oltport, onuoltid, version)
		(SELECT id, time, onuid, nas, oltport, onuoltid, version FROM gponauthlog);
	SELECT setval('gpondasanauthlog_id_seq'::text, (SELECT MAX(id) FROM gpondasanauthlog));
	INSERT INTO gpondasanolts (id, snmp_version, snmp_description, snmp_host, snmp_community,
		snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level, snmp_privacy_passphrase,
		snmp_privacy_protocol, netdeviceid)
		(SELECT id, snmp_version, snmp_description, snmp_host, snmp_community,
			snmp_auth_protocol, snmp_username, snmp_password, snmp_sec_level, snmp_privacy_passphrase,
			snmp_privacy_protocol, netdeviceid FROM gponolt);
	SELECT setval('gpondasanolts_id_seq'::text, (SELECT MAX(id) FROM gpondasanolts));
	INSERT INTO gpondasanoltports (id, gponoltid, numport, maxonu)
		(SELECT id, gponoltid, numport, maxonu FROM gponoltports);
	SELECT setval('gpondasanoltports_id_seq'::text, (SELECT MAX(id) FROM gpondasanoltports));
	INSERT INTO gpondasanoltprofiles (id, name, gponoltid)
		(SELECT id, name, gponoltid FROM gponoltprofiles);
	SELECT setval('gpondasanoltprofiles_id_seq'::text, (SELECT MAX(id) FROM gpondasanoltprofiles));
	INSERT INTO gpondasanonus (id, name, gpononumodelsid, password, onuid, autoprovisioning,
		onudescription, gponoltprofilesid, voipaccountsid1, voipaccountsid2, autoscript,
		host_id1, host_id2, creationdate, moddate, creatorid, modid, netdevid, xmlprovisioning,
		properties)
		(SELECT id, name, gpononumodelsid, password, onuid, autoprovisioning,
			onudescription, gponoltprofilesid, voipaccountsid1, voipaccountsid2, autoscript,
			host_id1, host_id2, creationdate, moddate, creatorid, modid, netdevid, xmlprovisioning,
			properties FROM gpononu);
	SELECT setval('gpondasanonus_id_seq'::text, (SELECT MAX(id) FROM gpondasanonus));
	INSERT INTO gpondasanonu2customers (id, gpononuid, customersid)
		(SELECT id, gpononuid, customersid FROM gpononu2customers);
	SELECT setval('gpondasanonu2customers_id_seq'::text, (SELECT MAX(id) FROM gpondasanonu2customers));
	INSERT INTO gpondasanonu2olts (id, netdevicesid, gpononuid, numport)
		(SELECT id, netdevicesid, gpononuid, numport FROM gpononu2olt);
	SELECT setval('gpondasanonu2olts_id_seq'::text, (SELECT MAX(id) FROM gpondasanonu2olts));
	INSERT INTO gpondasanonumodels (id, name, description, producer, xmltemplate)
		(SELECT id, name, description, producer, xmltemplate FROM gpononumodels);
	SELECT setval('gpondasanonumodels_id_seq'::text, (SELECT MAX(id) FROM gpondasanonumodels));
	INSERT INTO gpondasanonuporttypes (id, name) (SELECT id, name FROM gpononuportstype);
	SELECT setval('gpondasanonuporttypes_id_seq'::text, (SELECT MAX(id) FROM gpondasanonuporttypes));
	INSERT INTO gpondasanonuports (id, onuid, typeid, portid, portdisable)
		(SELECT id, onuid, typeid, portid, portdisable FROM gpononuport);
	SELECT setval('gpondasanonuports_id_seq'::text, (SELECT MAX(id) FROM gpondasanonuports));
	INSERT INTO gpondasanonuporttype2models (gpononuportstypeid, gpononumodelsid, portscount)
		(SELECT gpononuportstypeid, gpononumodelsid, portscount FROM gpononuportstype2models);
	INSERT INTO gpondasanonutv (id, ipaddr, channel)
		(SELECT id, ipaddr, channel FROM gpononutv);
	SELECT setval('gpondasanonutv_id_seq'::text, (SELECT MAX(id) FROM gpondasanonutv));
");

$this->Execute("
	DROP TABLE gpononutv;
	DROP SEQUENCE gpononutv_id_seq;
	DROP TABLE gpononuportstype2models;
	DROP TABLE gpononuport;
	DROP SEQUENCE gpononuport_id_seq;
	DROP TABLE gpononuportstype;
	DROP SEQUENCE gpononuportstype_id_seq;
	DROP TABLE gpononumodels;
	DROP SEQUENCE gpononumodels_id_seq;
	DROP TABLE gpononu2olt;
	DROP SEQUENCE gpononu2olt_id_seq;
	DROP TABLE gpononu2customers;
	DROP SEQUENCE gpononu2customers_id_seq;
	DROP TABLE gpononu;
	DROP SEQUENCE gpononu_id_seq;
	DROP TABLE gponoltprofiles;
	DROP SEQUENCE gponoltprofiles_id_seq;
	DROP TABLE gponoltports;
	DROP SEQUENCE gponoltports_id_seq;
	DROP TABLE gponolt;
	DROP SEQUENCE gponolt_id_seq;
	DROP TABLE gponauthlog;
	DROP SEQUENCE gponauthlog_id_seq;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072300', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
