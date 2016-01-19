LMS Hiperus C5 git

Wymagania: php-soap, php-openssl, php-zlib

WDROŻENIE

Dane rejestracyjne platformy Hiperus są przechowywane w ustawieniach LMS:
	hiperus_c5.username
	hiperus_c5.password
	hiperus_c5.domain

Paczkę z pluginem rozpakowujemy do katalogu <lms-path>/plugins/LMSHiperusPlugin.
Tworzymy dowiązanie symboliczne w katalogu img LMS-a o nazwie LMSHiperusPlugin
do katalogu ../plugins/LMSHiperusPlugin/img

Dodajemy ustawienie konfiguracyjne:
	phpui.plugins=LMSHiperusPlugin

Pobranie danych z zew. serwisu do LMSa
    
    Pobieramy najpierw niezbędne info bez bilingów :
    ./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --import --customers --terminals --numbers --end-users --price-lists --wlr --subscriptions
    lub
    ./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --import --all
    
    na końcu imporujemy listę bilingów, niestety może to trochę potrwać, długi czas pobierania danych jest uzależniony od zew. serwisu
    zalecane jest pobieranie danych z okresu max 6 miesięcy, 
    w przykładzie poniżej zakładam że bilingi istnieją od 2010-01-01 do dzisiaj (np. 2012-12-31),
    z testów wyszło że zew. seriws pozwala na pobranie bilingów z okresu max 9 miesięcy.
    
    Przełączniki:

	--billing-date=okres
		okres -> currday(dzisiaj), lastday(dzień poprzedni), currmonth(miesiąc bieżący), lastmonth(miesiąc poprzedni), currweek(tydzień bieżący)

	 LUB

	--billing-from=YYYY/MM/DD -> data początkowa 
	--billing-to=YYYY/MM/DD -> data końcowa 
	
	!!! Użycie przełącznika --billing-date powoduje zignorowanie przełączników --billing-from i --billing-to !!!
	
	pozostałe przełączniki:
	--billing-type=all,incoming,outgoing,disa,forwarded,internal,vpbx -> typ dokonanych połączeń, domyślna wartość: outgoing

	--billing-success=all,yes,no -> pobieranie bilingów o konkretnym statusie zrealizowanego połączenia lub wszystkich, domyślnie: yes
	
	
	PRZYKŁAD, pobieramy bilingi z 3 lat, jeżeli mamy dość sporo klientów warto by było nieco bardziej
	rozdrobnić ramy czasowe pobieranych bilingów
	
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2010/01/01 --billing-to=2010/06/30
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2010/07/01 --billing-to=2010/12/31
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2011/01/01 --billing-to=2011/06/30
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2011/07/01 --billing-to=2011/12/31
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2012/01/01 --billing-to=2012/06/30
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-from=2012/07/01 --billing-to=2012/12/30
	./lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --billing --billing-type=all --billing-success=all --billing-date=currday
	
	
	Istnieje również przełącznik --import, który powoduje że najpierw zostaną usunięte wszystkie dane z bazy LMS.
	dotyczące tylko VoIP'a, dla konkretnego przełącznika.
	Przełącznik --import działa tak samo ze wszystkimi pozostałymi przełącznikami.


	do cron'a dopisujemy pobieranie bilingów z dnia poprzedniego, ważne jest aby pobieranie bilingów w cron było wcześniej niż 
	wystawianie faktur za VoIP i wysyłką faktur VAT do klientów (jeżeli ktoś ma tak fajnie ustawione)
	
	PRZYKŁAD - pobieranie bilingów, zakładam że binarki LMSa są w /var/www/lms/bin
	
	01 01 * * *	root	/usr/bin/php /var/www/lms/bin/lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --quiet --billing --billing-type=all --billing-success=all --billing-date=lastday >/dev/null
	

Wystawianie faktur 
    
	Data wystawiania faktury VAT za VoIP, nie jest zależna od ustawień w karcie klienta.
	Abonament i koszt rozmów poza abonamentem jest wystawiany za pełny miesiąć od dnia 1 do ostatniego.
	Ważne jest, aby skrypt był odpalany po pobraniu bilingów a przed wysłaniem faktur do klientów
	do cron'a dodajemy kolejny wpis:
	
	15 3 1 * *	root	/usr/bin/php /var/www/lms/bin/lms_hiperus_c5_invoice.php --config-file=/etc/lms/lms.ini --quiet --leftmonth=1 >/dev/null
	
	Faktury będą wystawione każdego pierwszego danego miesiąca, za miesiąc poprzedni o godzinie 3:15
	Na fakturze będą znajwować się pozycje:
	    1 - Abonament XYZ
	    2 - koszt połączeń poza abonamentem XYZ
	dla każdego terminala osobno !!!

	Możemy również użyć przełącznika --fakedate=YYYY/MM/DD, który powoduje, że skrypt zakłada datę bieżącą taką jak wskazana tym przełącznikiem.
	
	Warunki które muszą być spełnione aby była wystawiona faktura:
	a) - konto VoIP musi być powiązane z klientem w LMSie,
	b) - wartość faktury nie może być zerowa, jeżeli wartość = 0 zł to faktura nie jest wystawiana,
	c) - pozycje o wartości 0 zł nie są umieszczane na fakturze.

	Skrypt domyślnie tworzy nowe faktury dla usług VoIP. Jeśli chcemy, żeby dopisywał nowe pozycje za usługi VoIP
	do istniejących faktur z bieżącego miesiąca, używamy ustawienia konfiguracyjnego hiperus_c5.add_new_invoices=false
	

Ogólny opis jak to działa.

    Wszystkie odczyty danych są dokonywane bezpośrednio z bazy danych LMSa,
    dodawanie, aktualizacja czy kasowanie kont,terminali,informacji itd są robione w trybie LIVE,
    np.
	jeżeli dodajemy nowe konto VoIP, to najpierw konto jest dodawane w zew. serwisie a następnie w LMS, jeżeli operacja przebiegła bez błędów.
	w przypadku gdy serwer zwróci info o błędzie to dane w LMS nie będą zmienione, lub zostaną automatycznie zaktualizowane do stanu faktycznego.
	
	w przypadku aktualizacji danych czy ich kasowania, schemat postępowania jest dokładnie taki sam.
	
    Nie zaleca się używania w kilku LMS'ach obsługi VoIP dotyczącej tego samego konta resellera.
    Jeżeli kiedyś Telekomunikacja Bliżej wprowadzi callback informujący jakie zmiany zostały przeprowadzone itd,
    gdzie będzie można wpisać kilka adresów z namiarami do LMSa gdzie jest ten moduł to taki myk będzie można zrobić.
    
    Co zrobić jeżeli ktoś dokonał zmian w kontach VoIP przez panel zewnętrzny, a w LMS nie widać zmian ?
    Należy z poziomu shell zaktualizować dane, np. dane zostały zmienione w kilku kontach, informacje o koncie VoIP, np. adres użytkownika
    
    root@debian:/#/usr/bin/php /var/www/lms/bin/lms_hiperus_c5_import.php --config-file=/etc/lms/lms.ini --customers
    
    zostaną dane zaktualizowane !!! nie używamy przełącznika --import !!!
    
    podobnie postępujemy w przypadku innych zmian.

Uwaga! Skrypt lms-plicbd-localisation.php jest adaptacją skryptu o tej samej nazwie, pochodzącego z wtyczki LMSPlicbdPlugin
  i został dostosowany do działania z platformą Hiperus.

