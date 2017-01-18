DO $$
BEGIN
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'auth_protocol') THEN
		CREATE TYPE auth_protocol AS ENUM ('MD5','SHA','');
	END IF;
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'sec_level') THEN
		CREATE TYPE sec_level AS ENUM ('noAuthNoPriv','authNoPriv','authPriv','');
	END IF;
	IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'privacy_protocol') THEN
		CREATE TYPE privacy_protocol AS ENUM ('DES','AES','');
	END IF;
END
$$;

/* gpondasanauthlog */
CREATE SEQUENCE gpondasanauthlog_id_seq;
CREATE TABLE gpondasanauthlog (
	id integer DEFAULT nextval('gpondasanauthlog_id_seq'::text) NOT NULL,
	time timestamp with time zone,
	onuid integer NOT NULL,
	nas varchar(15) NOT NULL DEFAULT '',
	oltport integer,
	onuoltid integer,
	version varchar(20),
	PRIMARY KEY (id)
);
CREATE INDEX gpondasanauthlog_onuid_time ON gpondasanauthlog (onuid, time DESC);

/* gpondasanolt */
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

/* gpondasanoltports */
CREATE SEQUENCE gpondasanoltports_id_seq;
CREATE TABLE gpondasanoltports (
	id integer DEFAULT nextval('gpondasanoltports_id_seq'::text) NOT NULL,
	gponoltid integer NOT NULL,
	numport integer NOT NULL,
	maxonu integer NOT NULL,
	PRIMARY KEY (id)
);
CREATE INDEX gpondasanoltports_gponoltid_idx ON gpondasanoltports (gponoltid);

/* gpondasanoltprofiles */
CREATE SEQUENCE gpondasanoltprofiles_id_seq;
CREATE TABLE gpondasanoltprofiles (
	id integer DEFAULT nextval('gpondasanoltprofiles_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	gponoltid integer DEFAULT NULL
		REFERENCES gpondasanolts (id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (id)
);

/* gpondasanonus */
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

/* gpondasanonu2customers */
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

/* gpondasanonu2olts */
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

/* gpondasanonumodels */
CREATE SEQUENCE gpondasanonumodels_id_seq;
CREATE TABLE gpondasanonumodels (
	id integer DEFAULT nextval('gpondasanonumodels_id_seq'::text) NOT NULL,
	name varchar(32) NOT NULL,
	description text,
	producer varchar(64) DEFAULT NULL,
	xmltemplate text DEFAULT '',
	PRIMARY KEY (id)
);

/* gpondasanonuporttypes */
CREATE SEQUENCE gpondasanonuporttypes_id_seq;
CREATE TABLE gpondasanonuporttypes (
	id integer DEFAULT nextval('gpondasanonuporttypes_id_seq'::text) NOT NULL,
	name varchar(100) NOT NULL,
	PRIMARY KEY (id)
);

/* gpondasanonuports */
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

/* gpondasanonuporttype2models */
CREATE TABLE gpondasanonuporttype2models (
	gpononuportstypeid integer DEFAULT NULL
		REFERENCES gpondasanonuporttypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
	gpononumodelsid integer NOT NULL
		REFERENCES gpondasanonumodels (id) ON DELETE CASCADE ON UPDATE CASCADE,
	portscount integer NOT NULL
);
CREATE INDEX gpondasanonuporttype2models_gpononuportstypeid_gpononumodelsid_idx ON gpondasanonuporttype2models (gpononuportstypeid, gpononumodelsid);

/* gpondasanonutv */
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
		SELECT INTO onu_id id FROM gpondasanonus WHERE name = username;
		INSERT INTO gpondasanauthlog (time, onuid, nas, oltport, onuoltid, version) VALUES(NOW(), onu_id, nas_ip::inet, olt, onu, ver);
		UPDATE gpondasanonus SET onuid = onu WHERE id = onu_id;

		UPDATE gpondasanonu2olts SET numport = olt WHERE gpononuid = onu_id AND netdevicesid = dev_id;
		IF NOT FOUND THEN
			INSERT INTO gpondasanonu2olts (netdevicesid, gpononuid, numport)
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
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'rx_power_weak', '-26', 'Niski poziom odbieranej mocy optycznej', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'rx_power_overload', '-4', 'Wysoki poziom odbieranej mocy optycznej', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'onu_autoscript_debug', '1', '', 1);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'use_radius', 0, 'Czy gpon (olty) mają używać radiusa', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'syslog', 0, 'Jeśli mamy tabele syslog to możemy logować zdarzenia (custom lms).  syslog(time integer, userid integer, level smallint, what character varying(128), xid integer, message text, detail text)', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_admin_login', 'admin', 'domyślny login administracyjny ustawiany przy provisioningu xml końcówek', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_admin_password', 'password', 'domyślne hasło administracyjne ustawiane przy provisioningu xml końcówek', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_web_port', '80', 'domyślny port interfejsu www końcówek ustawiany przy provisioningu xml końcówek', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_filename', '', 'szablon nazwy pliku (możliwa pełna ścieżka) w którym zapisywane są konfiguracje końcówek używane przy ich provisioningu', 1);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_default_enabled', 'true', 'czy w interfejsie użytkownika pole provisioningu xml jest automatycznie zaznaczone', 1);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_telnet_password', 'password', 'domyślne hasło dostępu przez telnet ustawiane przy provisioningu xml końcówek', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_lan_networks', '192.168.10.100|192.168.10.101-254|24|lan_podstawowy,172.16.1.100-254|24|lan_zapasowy,10.1.2.100-254|24|lan_awaryjnie_zapasowy',
	'lista sieci ip oddzielonych spacjami, przecinkami lub średnikami proponowanych w ramach możliwości szybkiego skonfigurowania na końcówce sieci lokalnej abonenta', 0);
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_user_password', '%14random%',
	'domyślne hasło użytkownika na końcówce używane podczas provisioningu xml', 0);

INSERT INTO gpondasanonuporttypes (name) VALUES ('eth'), ('pots'), ('ces'), ('video'), ('virtual-eth'), ('wifi');

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSGponDasanPlugin', '2016080800');
