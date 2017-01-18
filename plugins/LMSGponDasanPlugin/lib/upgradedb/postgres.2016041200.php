<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponoltprofiles ADD COLUMN gponoltid integer DEFAULT NULL");
$this->Execute("ALTER TABLE gponoltprofiles ADD CONSTRAINT gponoltprofiles_gponoltid_fkey
	FOREIGN KEY (gponoltid) REFERENCES gponolt (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016041200', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
