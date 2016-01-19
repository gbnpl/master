<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gpononu CHANGE onuid onuid smallint(11) DEFAULT '0' NOT NULL");
$this->Execute("ALTER TABLE gpononu CHANGE autoscript autoscript tinyint(4) DEFAULT '0' NOT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015122900', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
