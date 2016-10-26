Wtyczka Jambox do LMS

WYMAGANIA

Paczki systemowe php-dom, php-calendar

INSTALACJA

1) Wtyczkę umieszczamy w katalogu <lms-path>/plugins/LMSJamboxPlugin
2) Tworzymy dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSJamboxPlugin
do katalogu ../plugins/LMSJamboxPlugin/img
3) Aktywujemy wtyczkę z poziomu menu "Konfiguracja"/"Wtyczki" LMS-a.
4) Katalog <lms-path>/plugins/LMSJamboxPlugin/tmp uczynić zapisywalnych dla serwera
www, np.
	chmod u+w <lms-path>/plugins/LMSJamboxPlugin/tmp
	chown apache <lms-path>/plugins/LMSJamboxPlugin/tmp

KONFIGURACJA

Dane rejestracyjne platformy Jambox są przechowywane w ustawieniach LMS:
jambox.username
jambox.password (hasło może być wpisane jawnym tekstem lub powinno być zakodowane MD5
	w formacie tekstowym, szesnastkowym, np. poleceniem: echo -ne "hasło" |md5sum)
jambox.server (domyślnie: https://sms.sgtsa.pl/sms/xmlrpc)

Inne ustawienia:
jambox.http_timeout (domyślnie: 10 [sekund])

SKRYPTY

tvbillingimport.php - importuje wszystkie zdarzenia billingowe od 1-szego dnia
	poprzedniego miesiąca do chwili obecnej (chwilę obecną możemy zmienić parametrem
	skryptu --fakedate=YYYY/MM/DD).

tvbilling.php - zależnie od ustawienia jambox.tvbilling_addinvoices
	dodaje pozycje do już istniejących faktur (false - domyślnie) lub tworzy nowe
	faktury z obciążeniami za telewizję (jeśli true).
	Uwaga! Ten skrypt wyszukuje faktury jedynie w dniu bieżącym, chyba, że zmienimy
	dzień parametrem skryptu --fakedate=YYYY/MM/DD. Skrypt ten odszukuje niefakturowane
	zdarzenia billingowe tv pobrane skryptem tvbillingimport.php od 1-szego dnia
	poprzedniego miesiąca do chwili obecnej.
