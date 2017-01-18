<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponoltprofiles ADD COLUMN gponoltid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE gponoltprofiles ADD INDEX gponoltid (gponoltid)");
$this->Execute("ALTER TABLE gponoltprofiles ADD FOREIGN KEY (gponoltid)
	REFERENCES gponolt (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016041200', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
