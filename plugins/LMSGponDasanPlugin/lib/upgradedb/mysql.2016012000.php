<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gpononuportstype2models CHANGE gpononuportstypeid gpononuportstypeid int(11) DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016012000', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
