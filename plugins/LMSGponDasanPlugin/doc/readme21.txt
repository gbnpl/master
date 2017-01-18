CHANGELOG
v2.1, 04.09.2014
 - poprawione wyszkiwanie onu
 - filr,sortowanie na liscie podlaczonych onu
 - nazwy onu-profile na oltach sa wgrywane do bazy przy kliknieciu "Wykryj ONU"
 - poprawki do wersji git z 08.2014 (zmiana $CONFIG -> ConfigHelper::checkConfig)
 - inne poprawki
v2.0, 18.07.2014
 - odczyt dodatkowych parametrow (parametry optycznie, czas nieaktywnosci, mac na konkretym porcie onu
   odczyt i wykres sygnału dla na OLT - 1310nm, odczyt sygnału 1550nm)
 - konfiguracja i podlglad hasel (router mode) - dodatkowe uprawnienie
 - konfiguracja radiusa, czasu niektywnosci, service profile na OLT
 - autentykacja onu za pomoca serwera radius. brak menu autopodlaczanie jesli
   ustawiona jest zmienna gpon_use_radius
 - definicja ilosci portow w modelu ONU na potrzeby serwera radius
 - poprawka wyłączenia portu wifi przez snmp
 - provisioning dla onu (tylko wersje > 6.xx (V8240, V5812G) oraz > 1.04 (V5824G))
 - zapis konfiguracji na olcie (write memory)
 - mozliwosc wykrycia koncowek onu tylko na danym urzadzeniu OLT
 - format portow olta jest x/y dla modelu 8240 (model musi byc wpisany w lms)
v1.9, 11.03.2014
 - poprawki
v1.8, 14.09.2013
 - poprawki
v1.7, 17.05.2013
 - Dodano historię sygnału odbieranego na ONU - wykres:
    - wymagania:
	- rrdtool : http://oss.oetiker.ch/rrdtool/
	- modul GD dla php - php5-gd
    - w lms.ini w sekcji [directories] nalezy zefiniowac opcje rrd_dir - katalog gdzie trzymane
      sa pliki .rrd np: rrd_dir = /var/www/lms/rrd/
      katalog musi sie konczyc '/'!
    - utworzyc tenze katalog, np: lms/rrd
    - w konfiguracji UI w sekcji 'phpui' nalezy dodac opcje 'rrdtool' a jej wartosc to sciezka do pliku rrdtool
      domyslnie: /usr/bin/rrdtool
    - skonfigurowac crontab aby co godzine uruchamial skrypt 'php path_to_lms/bin/gponsignalrrd.php'
 - Drobne poprawki
v1.6
- 28.02.2013 tylko plik GPON_SNMP.class.php
  drobna poprawka w konfiguracji portow onu (speed,duplex - configured vs read)
v1.5
- wyswietlana jest informacja o duplikatach onu - ten sam onu na roznych
  portach/oltach - menu 'auto podlaczanie onu'
v1.4
- Poprawione menu wykryj onu - onu istniejace juz w bazie a wykryte na olt sa
  automatycznie podlaczne pod olt i wyswietlana jest tylko informacja o tym.
- Poprawione dodawanie nowego onu. (sprawdzanie). Przy dodawaniu nowego onu
  przez autowykrywanie trzeba wybrac profil [bedzie to zmienione]
v1.3
- wyswietlanie / zmienianie onu description...

Współpraca z serwerem RADIUS:

1. W przypadku uzywania serwera radius do autoryzacji onu skonfigurowac 
   serwer radius - przykladowa konfiguracja w pliku radius.conf.
   Domyslnie radius przy autoryzacji sprawdza numer DSNW i _model_
   Do onu wysyłane sa parametry zgodnie z opisem z pliku "ELMAT FreeRADIUS - autentykacja ONT 10-09-2013.pdf"
   - konfiguracja Voip 1 i 2 sluzy do konfiguracji kont sip na portach pots[12] - dla serwera radius
     jesli serwer radius nie jest stosowany to zapis nastepuje przy "autopodlaczeniu"
   - konfiguracja Host 1. i 2. (ponizej voip) sluzy do konfiguracji adresu ip-host-[12] na podstawie
     wybranego komputera klienta/urzadzenia - tylko dla profili ze statycznym IP - dane dla serwera radius

2. W menu modele ONU nalezy zdefiniowac poprawna ilosc portow dla kazdego
   modelu onu (np: H640GW-02 eth:4, pots:2, wifi: 1). - wymagane do
   konfiguracji voip oraz wylaczania portow przez serwer radius.

===========================================================

Konfiguracja:

Ustawienia w sekcji konfiguracyjnej gpon-dasan

enabled 		- czy uzywać modułu gpon
onumodels_pagelimit	- analogicznie jak w lms inne wartości *pagelimit
onu_pagelimit		- jw
olt_pagelimit		- jw
max_onu_to_olt		- domyślna wartość nakładająca limit onu na port olt
onu_customerlimit	- do ilu użytkowników może byc przypisany onu (np
			przypadek gdzie jest jedno onu na dom bliźniak)
rx_power_weak	- poniżej jakiej wartości rx power jest sygnalizowane
			na czerwono
rx_power_overload	- powyżej jakiej wartości rx power jest sygnalizowane
			na czerwono
use_radius		- czy autoryzacja onu w oparciu o serwer radius

===========================================================

Opis [ deprecated ]

### Menu ###

#Lista OLT 
lista OLT ktore posiadamy. Olt jest powiazane z urzadzeniami w Osprzet 
sieciowy i rowniez tam widoczne.

Wybierajac OLT mamy podglad na jego parametry i podlaczone ONU. Kon jaki jest
kazdy widzi ;)

#Nowy OLT
procz standartowych elementow jak w "Nowe urzadzenie" trzeba wypelnic pola:
 GPON-OLT-Ilosc portow: - ile portow GPON posiada olt raz ile mozemy
podlaczyc onu na dany port.
 SNMP* - dane do protokolu snmp przez ktory bedzie komunikacja. Nalezy podac
uzytkownika ktory posiada prawo zapisu po snmp. Do wyboru jest wersja 1 2 lub 3.

#Wykryj ONU
Sprawdza wszystkie olty i pokazuje ONU ktorych nie ma w bazie LMS. Wybrane onu
mozna dodac do bazy. Przydatne gdy mamu juz jakies onu podlaczone pod olt a
dopiero zaczelismy uzywac lms.

#Nowy onu
Nowy onu mozna przypisac do konkretnego olt na konkretny port, lub mozna tylko
przypisac do klietna i zastowoac autoprovisioning:

Autoprovisioning polega na dodanu nowego ONU do bazy lms z flaga 'wydany do
klienta'. ONU musi byc przypisany do klienta i musi byc przypisany jakis profil.
Opcjonalnie do skonfigurowania sa konta voip.

Plik gponautoscript.php sluzy do automatycznego wykrywania i dodawania
(podlaczanie pod odpowiedni port olt z odpowiednim id) wprowadzonych 
wczesniej do bazy ONU do OLT.
Przy podlaczaniu konfigurowany jest zadany wczesniej profil i (jezeli sa) dane voip
Skrypt mozna uruchamiac przez przegladarke www (czy potrzebny link w menu?) lub
moze byc wywolywany okresowo z crona.

Wybierajac ONU mozemy podladac jego ustawienia a edytujac go mozemy zapisac
niektore ustawienia na ONU (konta sip, wylaczac porty eth itp itd ble ble bla)

#Kanaly TV
mozna przypisywac nazwy kanalow TV na podstawie adresu multicastowego. W
informacji o ONU widoczna jest informacja ogladanego kanalu TV
