<?php

$this->BeginTrans();

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('gpon-dasan', 'xml_provisioning_user_password', '%14random%',
		'domyślne hasło użytkownika na końcówce używane podczas provisioningu xml', 0)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072001', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
