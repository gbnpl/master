<?php

$this->BeginTrans();

$this->Execute("UPDATE uiconfig SET var = ?, description = ? WHERE section = ? AND var = ?",
	array('rx_power_weak', 'Niski poziom odbieranej mocy optycznej', 'gpon-dasan', 'tx_output_power_weak'));

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('gpon-dasan', 'rx_power_overload', '-4', 'Wysoki poziom odbieranej mocy optycznej', 0)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016080800', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
