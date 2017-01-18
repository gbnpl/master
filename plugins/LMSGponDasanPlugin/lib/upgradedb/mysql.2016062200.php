<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE gpononu ADD COLUMN netdevid int(11) DEFAULT NULL");
$this->Execute("ALTER TABLE gpononu ADD FOREIGN KEY (netdevid)
	REFERENCES netdevices (id) ON DELETE SET NULL ON UPDATE CASCADE");

$onus = $this->GetAllByKey("SELECT id, name, location, description, serialnumber, purchasetime, guaranteeperiod
	FROM gpononu
	WHERE location <> '' OR description <> '' OR serialnumber <> ''
		OR purchasetime > 0 OR guaranteeperiod > 0", 'id');
if (!empty($onus))
	foreach ($onus as $id => $onu) {
		$this->Execute("INSERT INTO netdevices (name, location, description, serialnumber,
			purchasetime, guaranteeperiod)
			VALUES (?, ?, ?, ?, ?, ?)",
			array($onu['name'], empty($onu['location']) ? '' : $onu['location'],
				empty($onu['description']) ? '' : $onu['description'],
				empty($onu['serialnumber']) ? '' : $onu['serialnumber'],
				$onu['purchasetime'], $onu['guaranteeperiod']));
		$netdevid = $this->GetLastInsertID('netdevices');
		$this->Execute("UPDATE gpononu SET netdevid = ? WHERE id = ?",
			array($netdevid, $id));
	}

$this->Execute("
	ALTER TABLE gpononu DROP COLUMN location;
	ALTER TABLE gpononu DROP COLUMN description;
	ALTER TABLE gpononu DROP COLUMN serialnumber;
	ALTER TABLE gpononu DROP COLUMN purchasetime;
	ALTER TABLE gpononu DROP COLUMN guaranteeperiod;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016062200', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
