LMS Jambox git

Wymagania: php-dom, php-calendar

WDROŻENIE

Dane rejestracyjne platformy Jambox są przechowywane w ustawieniach LMS:
	jambox.username
	jambox.password
	jambox.server (domyślnie: https://sms.sgtsa.pl/sms/xmlrpc)

Paczkę z pluginem rozpakowujemy do katalogu <lms-path>/plugins/LMSJamboxPlugin
Tworzymy dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSJamboxPlugin
do katalogu ../plugins/LMSJamboxPlugin/img

Ładujemy definicje tabel i rekordy z plików doc/jambox.{mysql,pgsql} do bazy danych
w oparciu o którą działa LMS (wybieramy plik pasujący do silnika bazy danych przez nas używanego).

Dodajemy ustawienie konfiguracyjne:
	phpui.plugins=LMSJamboxPlugin
