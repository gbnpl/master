<?php

$this->BeginTrans();

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016072100', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
