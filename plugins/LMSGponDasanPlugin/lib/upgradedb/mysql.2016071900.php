<?php

$this->BeginTrans();

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
	VALUES ('gpon-dasan', 'xml_provisioning_lan_networks', '192.168.10.100-254/24|lan_podstawowy,172.16.1.100-254/24|lan_zapasowy,10.1.2.100-254/24|lan_awaryjnie_zapasowy',
		'lista sieci ip oddzielonych spacjami, przecinkami lub średnikami proponowanych w ramach możliwości szybkiego skonfigurowania na końcówce sieci lokalnej abonenta', 0)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016071900', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
