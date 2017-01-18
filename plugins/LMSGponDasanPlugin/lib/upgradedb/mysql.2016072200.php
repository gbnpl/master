<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponolt ADD COLUMN netdeviceid int(11) DEFAULT NULL");
$olts = $this->GetAll("SELECT id AS netdeviceid, gponoltid FROM netdevices WHERE gponoltid > 0");
if (!empty($olts))
	foreach ($olts as $olt)
		$this->Execute("UPDATE gponolt SET netdeviceid = ? WHERE id = ?",
			array($olt['netdeviceid'], $olt['gponoltid']));
$this->Execute("ALTER TABLE netdevices DROP COLUMN gponoltid");
$this->Execute("ALTER TABLE gponolt CHANGE netdeviceid netdeviceid int(11) NOT NULL");
$this->Execute("ALTER TABLE gponolt ADD FOREIGN KEY (netdeviceid) ON netdevices (id)
	ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072200', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
