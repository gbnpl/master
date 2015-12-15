CREATE TABLE tv_billingevent (
  id serial NOT NULL,
  customerid integer NOT NULL,
  account_id integer NOT NULL,
  be_selling_date date NOT NULL,
  be_desc text NOT NULL,
  be_vat numeric(5,2) NOT NULL,
  be_gross numeric(5,2) NOT NULL,
  group_id integer NOT NULL,
  cust_number varchar(10) NOT NULL,
  package_id integer NOT NULL,
  hash varchar(32) NOT NULL,
  beid integer NOT NULL,
  be_b2b_netto numeric(5,2) DEFAULT NULL,
  docid integer DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX hash ON tv_billingevent (hash);

DROP VIEW customersview;
DROP VIEW contractorview;
ALTER TABLE customers ADD COLUMN tv_cust_number varchar(12) DEFAULT NULL;
ALTER TABLE customers ADD COLUMN tv_suspend_billing smallint DEFAULT 0 NOT NULL;
CREATE VIEW customersview AS
	SELECT c.* FROM customers c
	WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = c.id)
		AND c.type < 2;
CREATE VIEW contractorview AS
	SELECT c.* FROM customers c
	WHERE c.type = 2;

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSJamboxPlugin', '2015121500');
