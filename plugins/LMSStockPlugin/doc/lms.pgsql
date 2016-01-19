/*
DROP VIEW IF EXISTS stck_vpstock;

DROP TABLE IF EXISTS stck_stockassignments;
DROP SEQUENCE IF EXISTS stck_stockassignments_id_seq;
DROP TABLE IF EXISTS stck_stock;
DROP SEQUENCE IF EXISTS stck_stock_id_seq;
DROP TABLE IF EXISTS stck_warehouses;
DROP SEQUENCE IF EXISTS stck_warehouses_id_seq;
DROP TABLE IF EXISTS stck_vpstock;
DROP TABLE IF EXISTS stck_products;
DROP SEQUENCE IF EXISTS stck_products_id_seq;
DROP TABLE IF EXISTS stck_types;
DROP SEQUENCE IF EXISTS stck_types_id_seq;
DROP TABLE IF EXISTS stck_supplierassignments;
DROP SEQUENCE IF EXISTS stck_supplierassignments_id_seq;
DROP TABLE IF EXISTS stck_receivenotes;
DROP SEQUENCE IF EXISTS stck_receivenotes_id_seq;
DROP TABLE IF EXISTS stck_quantities;
DROP SEQUENCE IF EXISTS stck_quantities_id_seq;
DROP TABLE IF EXISTS stck_manufacturers;
DROP SEQUENCE IF EXISTS stck_manufacturers_id_seq;
DROP TABLE IF EXISTS stck_groups;
DROP SEQUENCE IF EXISTS stck_groups_id_seq;

ALTER TABLE cash DROP COLUMN IF EXISTS stockid;
ALTER TABLE invoicecontents DROP COLUMN IF EXISTS stockid;
*/

DELETE FROM dbinfo WHERE keytype = 'dbversion_LMSStockPlugin';

/*
 structure of table stck_groups
*/
CREATE SEQUENCE stck_groups_id_seq;
CREATE TABLE stck_groups (
  id integer DEFAULT nextval('stck_groups_id_seq'::text) NOT NULL,
  quantityid integer NOT NULL,
  quantitycheck smallint NOT NULL DEFAULT 1,
  name varchar(100) NOT NULL,
  creatorid integer NOT NULL DEFAULT 0,
  modid integer NOT NULL DEFAULT 0,
  creationdate integer NOT NULL DEFAULT 0,
  moddate integer NOT NULL DEFAULT 0,
  comment text NOT NULL,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE (name)
);

/*
 structure of table stck_manufacturers
*/
CREATE SEQUENCE stck_manufacturers_id_seq;
CREATE TABLE stck_manufacturers (
  id integer DEFAULT nextval('stck_manufacturers_id_seq'::text) NOT NULL,
  creationdate integer NOT NULL,
  moddate integer NOT NULL DEFAULT 0,
  creatorid integer NOT NULL,
  modid integer NOT NULL DEFAULT 0,
  name varchar(100) NOT NULL,
  comment text,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE (name)
);

/*
 structure of table stck_quantities
*/
CREATE SEQUENCE stck_quantities_id_seq;
CREATE TABLE stck_quantities (
  id integer DEFAULT nextval('stck_quantities_id_seq'::text) NOT NULL,
  def smallint NOT NULL DEFAULT 0,
  name varchar(10) NOT NULL,
  comment text NOT NULL,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);

/*
 structure of table stck_receivenotes
*/
CREATE SEQUENCE stck_receivenotes_id_seq;
CREATE TABLE stck_receivenotes (
  id integer DEFAULT nextval('stck_receivenotes_id_seq'::text) NOT NULL,
  supplierid integer
		REFERENCES customers (id) ON UPDATE CASCADE ON DELETE SET NULL,
  number varchar(255) NOT NULL,
  creatorid integer NOT NULL,
  modid integer NOT NULL DEFAULT 0,
  creationdate integer NOT NULL,
  moddate integer NOT NULL DEFAULT 0,
  netvalue numeric(9,2) NOT NULL,
  grossvalue numeric(9,2) NOT NULL,
  paytype integer NOT NULL,
  datesettlement integer NOT NULL,
  datesale integer NOT NULL,
  deadline integer NOT NULL,
  paid integer DEFAULT NULL,
  comment text,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);
CREATE INDEX stck_receivenotes_supplierid_idx ON stck_receivenotes (supplierid);

/*
 structure of table stck_supplierassignments
*/
CREATE SEQUENCE stck_supplierassignments_id_seq;
CREATE TABLE stck_supplierassignments (
  IDSupplierAssignment integer DEFAULT nextval('stck_supplierassignments_id_seq'::text) NOT NULL,
  IDCustomer integer NOT NULL,
  IDSupplier integer DEFAULT NULL,
  PRIMARY KEY (IDSupplierAssignment)
);

/*
 structure of table stck_types
*/
CREATE SEQUENCE stck_types_id_seq;
CREATE TABLE stck_types (
  id integer DEFAULT nextval('stck_types_id_seq'::text) NOT NULL,
  def smallint NOT NULL DEFAULT 0,
  name varchar(100) NOT NULL,
  quantitycheck smallint NOT NULL,
  creationdate integer NOT NULL DEFAULT 0,
  moddate integer NOT NULL DEFAULT 0,
  creatorid integer NOT NULL DEFAULT 0,
  modid integer NOT NULL DEFAULT 0,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);

/*
 structure of table stck_products
*/
CREATE SEQUENCE stck_products_id_seq;
CREATE TABLE stck_products (
  id integer DEFAULT nextval('stck_products_id_seq'::text) NOT NULL,
  manufacturerid integer NOT NULL DEFAULT 0
		REFERENCES stck_manufacturers (id) ON UPDATE CASCADE,
  groupid integer NOT NULL DEFAULT 0
		REFERENCES stck_groups (id) ON UPDATE CASCADE,
  taxid integer NOT NULL DEFAULT 0,
  typeid integer NOT NULL DEFAULT 0
		REFERENCES stck_types (id) ON UPDATE CASCADE,
  quantityid integer NOT NULL
		REFERENCES stck_quantities (id) ON UPDATE CASCADE,
  quantitycheck smallint NOT NULL DEFAULT 1,
  ean varchar(30) DEFAULT NULL,
  quantity integer NOT NULL DEFAULT 0,
  name text NOT NULL,
  creationdate integer NOT NULL DEFAULT 0,
  creatorid integer NOT NULL DEFAULT 0,
  moddate integer NOT NULL DEFAULT 0,
  modid integer NOT NULL DEFAULT 0,
  comment text,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);
CREATE INDEX stck_products_manufacturerid_idx ON stck_products (manufacturerid);
CREATE INDEX stck_products_groupid_idx ON stck_products (groupid);
CREATE INDEX stck_products_taxid_idx ON stck_products (taxid);
CREATE INDEX stck_products_typeid_idx ON stck_products (typeid);
CREATE INDEX stck_products_quantityid_idx ON stck_products (quantityid);

/*
 structure of table stck_vpstock; replaced later by stck_vpstock view
*/
DROP TABLE IF EXISTS stck_vpstock;
CREATE TABLE stck_vpstock (
	id integer,
	warehouseid integer,
	groupid integer,
	productid integer,
	supplierid integer,
	enterdocumentid integer,
	quitdocumentid integer,
	creationdate integer,
	bdate integer,
	moddate integer DEFAULT 0,
	leavedate integer,
	creatorid integer,
	modid integer DEFAULT 0,
	serialnumber varchar(255),
	warranty integer,
	pricebuynet numeric(9,2),
	taxid integer,
	pricebuygross numeric(9,2),
	pricesell numeric(9,2),
	deleted smallint DEFAULT 0,
	comment text,
	pid integer,
	pname text,
	manufacturerid integer,
	pcomment text,
	pdeleted smallint DEFAULT 0,
	gid integer,
	gname varchar(100),
	gcomment text,
	gdeleted smallint DEFAULT 0
);

/*
 structure of table stck_warehouses
*/
CREATE SEQUENCE stck_warehouses_id_seq;
CREATE TABLE stck_warehouses (
  id integer DEFAULT nextval('stck_warehouses_id_seq'::text) NOT NULL,
  name varchar(100) NOT NULL,
  comment text,
  def smallint NOT NULL DEFAULT 0,
  commerce smallint NOT NULL DEFAULT 1,
  creationdate integer NOT NULL DEFAULT 0,
  moddate integer NOT NULL DEFAULT 0,
  creatorid integer NOT NULL DEFAULT 0,
  modid integer NOT NULL DEFAULT 0,
  deleted smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE (name)
);

/*
 structure of table stck_stock
*/
CREATE SEQUENCE stck_stock_id_seq;
CREATE TABLE stck_stock (
  id integer DEFAULT nextval('stck_stock_id_seq'::text) NOT NULL,
  warehouseid integer NOT NULL
		REFERENCES stck_warehouses (id) ON UPDATE CASCADE,
  groupid integer NOT NULL,
  productid integer NOT NULL,
  supplierid integer
		REFERENCES customers (id) ON UPDATE CASCADE ON DELETE SET NULL,
  enterdocumentid integer NOT NULL
		REFERENCES stck_receivenotes (id) ON UPDATE CASCADE,
  quitdocumentid integer DEFAULT NULL,
  creationdate integer NOT NULL DEFAULT 0,
  bdate integer NOT NULL,
  moddate integer NOT NULL DEFAULT 0,
  leavedate integer NOT NULL DEFAULT 0,
  creatorid integer NOT NULL DEFAULT 0,
  modid integer NOT NULL DEFAULT 0,
  serialnumber varchar(255) DEFAULT NULL,
  warranty integer DEFAULT NULL,
  pricebuynet numeric(9,2) NOT NULL DEFAULT '0.00',
  taxid integer NOT NULL,
  pricebuygross numeric(9,2) NOT NULL,
  pricesell numeric(9,2) DEFAULT NULL,
  deleted smallint NOT NULL DEFAULT 1,
  comment text,
  PRIMARY KEY (id)
);
CREATE INDEX stck_stock_enterdocumentid_idx ON stck_stock (enterdocumentid);
CREATE INDEX stck_stock_supplierid_idx ON stck_stock (supplierid);
CREATE INDEX stck_stock_warehouseid_idx ON stck_stock (warehouseid);
CREATE INDEX stck_stock_pricebuynet_idx ON stck_stock (pricebuynet, pricebuygross);

/*
 structure of table stck_stockassignments
*/
CREATE SEQUENCE stck_stockassignments_id_seq;
CREATE TABLE stck_stockassignments (
  id integer DEFAULT nextval('stck_stockassignments_id_seq'::text) NOT NULL,
  type integer NOT NULL,
  stockid integer NOT NULL
		REFERENCES stck_stock (id) ON UPDATE CASCADE,
  assigmentid integer NOT NULL,
  complete smallint NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
);
CREATE INDEX stck_stockassignments_stockid_idx ON stck_stockassignments (stockid);

/*
 structure of view stck_vpstock
*/
DROP TABLE IF EXISTS stck_vpstock;

CREATE VIEW stck_vpstock AS
	SELECT 
		s.id AS id,s.warehouseid AS warehouseid,s.groupid AS groupid,s.productid AS productid,
		s.supplierid AS supplierid,s.enterdocumentid AS enterdocumentid,s.quitdocumentid AS quitdocumentid,
		s.creationdate AS creationdate,s.bdate AS bdate,s.moddate AS moddate,s.leavedate AS leavedate,
		s.creatorid AS creatorid,s.modid AS modid,s.serialnumber AS serialnumber,s.warranty AS warranty,
		s.pricebuynet AS pricebuynet,s.taxid AS taxid,s.pricebuygross AS pricebuygross,
		s.pricesell AS pricesell,s.deleted AS deleted,s.comment AS comment,
		p.id AS pid,
		p.name AS pname,p.manufacturerid AS manufacturerid,p.comment AS pcomment,p.deleted AS pdeleted,
		g.id AS gid,g.name AS gname,g.comment AS gcomment,g.deleted AS gdeleted 
	FROM stck_stock s
	LEFT JOIN stck_products p ON s.productid = p.id
	LEFT JOIN stck_groups g ON p.id = g.id;

/*
 add fields to existing lms tables
*/
ALTER TABLE cash ADD COLUMN stockid integer DEFAULT NULL;
ALTER TABLE invoicecontents ADD COLUMN stockid integer DEFAULT NULL;

/*
 basic data records
*/
INSERT INTO stck_quantities (id, def, name, comment, deleted) VALUES
	(1, 1, 'szt.', '', 0),
	(2, 0, 'm.', '', 0),
	(3, 0, 'g.', '', 0),
	(4, 0, 'op.', 'Opakowanie', 0),
	(5, 0, 'kpl.', 'Komplet', 0);

INSERT INTO stck_types (id, def, name, quantitycheck, creationdate, moddate, creatorid, modid, deleted) VALUES
	(1, 0, 'Towar', 0, 0, 0, 0, 0, 0),
	(2, 0, 'Usluga', 0, 0, 0, 0, 0, 0);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSStockPlugin', '2015091400');
