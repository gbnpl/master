<?php

$this->BeginTrans();

$this->Execute("UPDATE uiconfig SET value = REPLACE(value, '/', '|') WHERE section = 'gpon-dasan' AND var = 'xml_provisioning_lan_networks'");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072000', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
