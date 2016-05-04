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
  tv_suspend_billing smallint DEFAULT 0 NOT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE INDEX hash ON tv_billingevent (hash);

DROP VIEW customerview;
DROP VIEW contractorview;
DROP VIEW customeraddressview;
ALTER TABLE customers ADD COLUMN tv_cust_number varchar(12) DEFAULT NULL;
ALTER TABLE customers ADD COLUMN tv_suspend_billing smallint DEFAULT 0 NOT NULL;
CREATE VIEW customerview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a 
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid) 
			WHERE e.userid = lms_current_user() AND a.customerid = c.id) 
		AND c.type < 2;
CREATE VIEW contractorview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE c.type = 2;
CREATE VIEW customeraddressview AS
	SELECT c.*,
		(CASE WHEN building IS NULL THEN street ELSE (CASE WHEN apartment IS NULL THEN street || ' ' || building
			ELSE street || ' ' || building || '/' || apartment END) END) AS address,
		(CASE WHEN post_street IS NULL THEN '' ELSE
			(CASE WHEN post_building IS NULL THEN post_street ELSE (CASE WHEN post_apartment IS NULL THEN post_street || ' ' || post_building
				ELSE post_street || ' ' || post_building || '/' || post_apartment END)
			END)
		END) AS post_address
	FROM customers c
	WHERE c.type < 2;

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSJamboxPlugin', '2016050400');
