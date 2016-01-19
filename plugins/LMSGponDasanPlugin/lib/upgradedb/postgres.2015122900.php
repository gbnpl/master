<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gpononu ALTER COLUMN onuid SET DEFAULT 0");
$this->Execute("ALTER TABLE gpononu ALTER COLUMN autoscript SET DEFAULT 0");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2015122900', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
