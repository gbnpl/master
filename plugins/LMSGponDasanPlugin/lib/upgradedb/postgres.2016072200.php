<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gponolt ADD COLUMN netdeviceid integer DEFAULT NULL
	REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE");
$olts = $this->GetAll("SELECT id AS netdeviceid, gponoltid FROM netdevices WHERE gponoltid > 0");
if (!empty($olts))
	foreach ($olts as $olt)
		$this->Execute("UPDATE gponolt SET netdeviceid = ? WHERE id = ?",
			array($olt['netdeviceid'], $olt['gponoltid']));
$this->Execute("ALTER TABLE netdevices DROP COLUMN gponoltid");
$this->Execute("ALTER TABLE gponolt ALTER COLUMN netdeviceid DROP DEFAULT");
$this->Execute("ALTER TABLE gponolt ALTER COLUMN netdeviceid SET NOT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072200', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
