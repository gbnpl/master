<?php

$this->BeginTrans();

$this->Execute("DELETE FROM uiconfig WHERE section = ? AND var = ?", array('gpon-dasan', 'xml_provisioning_template'));
$this->Execute("ALTER TABLE gpononumodels ADD COLUMN xmltemplate text DEFAULT ''");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016070101', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
