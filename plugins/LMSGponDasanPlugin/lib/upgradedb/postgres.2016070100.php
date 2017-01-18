<?php

$this->BeginTrans();

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_admin_login', 'admin', 'domyślny login administracyjny ustawiany przy provisioningu xml końcówek', 0)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_admin_password', 'password', 'domyślne hasło administracyjne ustawiane przy provisioningu xml końcówek', 0)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_web_port', '80', 'domyślny port interfejsu www końcówek ustawiany przy provisioningu xml końcówek', 0)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_filename', '', 'szablon nazwy pliku (możliwa pełna ścieżka) w którym zapisywane są konfiguracje końcówek używane przy ich provisioningu', 1)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_template', '', 'ścieżka do pliku z szablonem xml używanym przy provisioningu xml wszystkich serii końcówek', 1)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('gpon-dasan', 'xml_provisioning_default_enabled', 'true', 'czy w interfejsie użytkownika pole provisioningu xml jest automatycznie zaznaczone', 1)");

$this->Execute("ALTER TABLE gpononu ADD COLUMN xmlprovisioning smallint DEFAULT 0");
$this->Execute("ALTER TABLE gpononu ADD COLUMN properties text DEFAULT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2016070100', 'dbversion_LMSGponDasanPlugin'));

$this->CommitTrans();

?>
