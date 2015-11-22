LMS Jambox git

Wymagania: php-dom, php-calendar

WDROŻENIE

Dane rejestracyjne platformy Jambox są przechowywane w ustawieniach LMS:
	jambox.username
	jambox.password (hasło powinno być zakodowane MD5 w formacie tekstowym szesnastkowym)
	jambox.server (domyślnie: https://sms.sgtsa.pl/sms/xmlrpc)

Paczkę z pluginem rozpakowujemy do katalogu <lms-path>/plugins/LMSJamboxPlugin
Tworzymy dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSJamboxPlugin
do katalogu ../plugins/LMSJamboxPlugin/img

Dodajemy ustawienie konfiguracyjne:
	phpui.plugins=LMSJamboxPlugin
