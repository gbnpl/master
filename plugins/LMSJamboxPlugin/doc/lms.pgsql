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

ALTER TABLE customers ADD COLUMN tv_cust_number varchar(12) DEFAULT NULL;

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSJamboxPlugin', '2015112200');
