<?php

$this->BeginTrans();

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('gpon-dasan', 'xml_provisioning_telnet_password', 'password', 'domyślne hasło dostępu przez telnet ustawiane przy provisioningu xml końcówek', 0)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016071500', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
