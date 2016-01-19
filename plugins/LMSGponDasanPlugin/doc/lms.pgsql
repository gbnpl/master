/* netdevices */
ALTER TABLE netdevices ADD gponoltid integer DEFAULT NULL;
CREATE INDEX netdevices_gponoltid_idx ON netdevices (gponoltid);

/* gponauthlog */
CREATE SEQUENCE gponauthlog_id_seq;
CREATE table gponauthlog (
	id integer DEFAULT nextval('gponauthlog_id_seq'::text) NOT NULL,
	time timestamp with time zone,
	onuid integer NOT NULL,
	nas varchar(15) NOT NULL DEFAULT '',
	oltport integer,
	onuoltid integer,
	version varchar(20),
	PRIMARY KEY (id)
);
CREATE INDEX gponauthlog_onuid_time ON gponauthlog (onuid, time DESC);

/* gponolt */
CREATE TYPE auth_protocol AS ENUM ('MD5','SHA','');
CREATE TYPE sec_level AS ENUM ('noAuthNoPriv','authNoPriv','authPriv','');
CREATE TYPE privacy_protocol AS ENUM ('DES','AES','');
CREATE SEQUENCE gponolt_id_seq;
CREATE TABLE gponolt (
	id integer DEFAULT nextval('gponolt_id_seq'::text) NOT NULL,
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
	PRIMARY KEY (id)
);

/* gponoltports */
CREATE SEQUENCE gponoltports_id_seq;
CREATE TABLE gponoltports (
	id integer DEFAULT nextval('gponoltports_id_seq'::text) NOT NULL,
	gponoltid integer NOT NULL,
	numport integer NOT NULL,
	maxonu integer NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX gponoltports_gponoltid_idx ON gponoltports (gponoltid);

/* gponoltprofiles */
CREATE SEQUENCE gponoltprofiles_id_seq;
CREATE TABLE gponoltprofiles (
	id integer DEFAULT nextval('gponoltprofiles_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	PRIMARY KEY (id)
);

/* gpononu */
CREATE SEQUENCE gpononu_id_seq;
CREATE TABLE gpononu (
	id integer DEFAULT nextval('gpononu_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	location varchar(255) NOT NULL,
	gpononumodelsid integer NOT NULL,
	description text NOT NULL,
	serialnumber varchar(32) NOT NULL,
	purchasetime integer NOT NULL DEFAULT '0',
	guaranteeperiod smallint NOT NULL DEFAULT '0',
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
	PRIMARY KEY (id),
	UNIQUE (name)
);
CREATE INDEX gpononu_gpononumodelsid_idx ON gpononu (gpononumodelsid);

/* gpononu2customers */
CREATE SEQUENCE gpononu2customers_id_seq;
CREATE TABLE gpononu2customers (
	id integer DEFAULT nextval('gpononu2customers_id_seq'::text) NOT NULL,
	gpononuid integer NOT NULL
		REFERENCES gpononu (id) ON DELETE CASCADE ON UPDATE CASCADE,
	customersid integer NOT NULL
		REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);
CREATE INDEX gpononu2customers_gpononuid_idx ON gpononu2customers (gpononuid);
CREATE INDEX gpononu2customers_customersid_idx ON gpononu2customers (customersid);

/* gpononu2olt */
CREATE SEQUENCE gpononu2olt_id_seq;
CREATE TABLE gpononu2olt (
	id integer DEFAULT nextval('gpononu2olt_id_seq'::text) NOT NULL,
	netdevicesid integer NOT NULL,
	gpononuid integer NOT NULL,
	numport smallint NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (gpononuid)
);
CREATE INDEX gpononu2olt_netdevicesid_idx ON gpononu2olt (netdevicesid);

/* gpononumodels */
CREATE SEQUENCE gpononumodels_id_seq;
CREATE TABLE gpononumodels (
	id integer DEFAULT nextval('gpononumodels_id_seq'::text) NOT NULL,
	name varchar(32) NOT NULL,
	description text,
	producer varchar(64) DEFAULT NULL,
	PRIMARY KEY (id)
);

/* gpononuportstype */
CREATE SEQUENCE gpononuportstype_id_seq;
CREATE TABLE gpononuportstype (
	id integer DEFAULT nextval('gpononuportstype_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	PRIMARY KEY (id)
);

/* gpononuport */
CREATE SEQUENCE gpononuport_id_seq;
CREATE TABLE gpononuport (
	id integer DEFAULT nextval('gpononuport_id_seq'::text) NOT NULL,
	onuid integer NOT NULL
		REFERENCES gpononu (id) ON DELETE CASCADE ON UPDATE CASCADE,
	typeid integer DEFAULT NULL
		REFERENCES gpononuportstype (id) ON DELETE SET NULL ON UPDATE CASCADE,
	portid integer DEFAULT NULL,
	portdisable smallint,
	PRIMARY KEY (id),
	UNIQUE (onuid, typeid, portid)
);

/* gpononuportstype2models */
CREATE TABLE gpononuportstype2models (
	gpononuportstypeid integer NOT NULL
		REFERENCES gpononuportstype (id) ON DELETE SET NULL ON UPDATE CASCADE,
	gpononumodelsid integer NOT NULL
		REFERENCES gpononumodels (id) ON DELETE CASCADE ON UPDATE CASCADE,
	portscount integer NOT NULL
);
CREATE INDEX gpononuportstype2models_gpononuportstypeid_gpononumodelsid_idx ON gpononuportstype2models (gpononuportstypeid, gpononumodelsid);

/* gpononutv */
CREATE SEQUENCE gpononutv_id_seq;
CREATE TABLE gpononutv (
	id integer DEFAULT nextval('gpononutv_id_seq'::text) NOT NULL,
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
		SELECT INTO onu_id id FROM gpononu WHERE name = username;
		INSERT INTO gponauthlog(time, onuid, nas, oltport, onuoltid, version) VALUES(NOW(), onu_id, nas_ip::inet, olt, onu, ver);
		UPDATE gpononu SET onuid = onu WHERE id = onu_id;

		UPDATE gpononu2olt SET numport = olt WHERE gpononuid = onu_id AND netdevicesid = dev_id;
		IF NOT FOUND THEN
			INSERT INTO gpononu2olt(netdevicesid, gpononuid, numport)
				VALUES(dev_id, onu_id, olt);
		END IF;
	END;
$$ LANGUAGE plpgsql;

/* uiconfig */
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'max_onu_to_olt', '64', 'GPON - Domyślna maksymalna liczba ONU przypisanych do portu OLT', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'onumodels_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy modeli ONU.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'onu_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy ONU.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'olt_pagelimit', '100', 'Limit wyświetlanych rekordów na jednej stronie listy OLT.', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'onu_customerlimit', '5', 'Maksymalna liczba Klientów przypisanych do ONU', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'tx_output_power_weak', '-26', 'Niski poziom mocy optycznej RX Output Power', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'onu_autoscript_debug', '1', '', 1);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'use_radius', 0, 'Czy gpon (olty) mają używać radiusa', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'syslog', 0, 'Jeśli mamy tabele syslog to możemy logować zdarzenia (custom lms).  syslog(time integer, userid integer, level smallint, what character varying(128), xid integer, message text, detail text)', 0);

INSERT INTO gpononuportstype (name) VALUES ('eth'), ('pots'), ('ces'), ('video'), ('virtual-eth'), ('wifi');

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSGponDasanPlugin', '2015122900');
