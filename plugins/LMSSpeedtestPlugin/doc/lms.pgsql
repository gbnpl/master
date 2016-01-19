DELETE FROM uiconfig WHERE section='userpanel' AND var LIKE 'speedtest_%';
DELETE FROM uiconfig WHERE section='speedtest';
DELETE FROM dbinfo WHERE keytype='dbversion_LMSSpeedtestPlugin';

DROP TABLE IF EXISTS speedtests;
CREATE TABLE speedtests (
	dt integer DEFAULT 0 NOT NULL,
	download integer DEFAULT 0 NOT NULL,
	upload integer DEFAULT 0 NOT NULL,
	latency numeric(5,2) DEFAULT 0.0 NOT NULL,
	nodeid integer NOT NULL
		REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX speedtests_nodeid_idx ON speedtests (nodeid);

INSERT INTO uiconfig (section, var, value, disabled) VALUES ('userpanel', 'speedtest_url', 'http://speedtest.firma.pl', 0);
INSERT INTO uiconfig (section, var, value, disabled) VALUES ('speedtest', 'display_limit', '20', 0);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSSpeedtestPlugin', '2015102800');
