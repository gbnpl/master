<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gpononuportstype2models ALTER COLUMN gpononuportstypeid DROP NOT NULL");
$this->Execute("ALTER TABLE gpononuportstype2models ALTER COLUMN gpononuportstypeid SET DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016012000', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
