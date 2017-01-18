Instalacja:
1. Zawartość paczki umieścić w katalogu plugins w LMS.
2. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
3. Zainstalować pakiet narzędziowy rrdtool (narzędzia powłoki).

Konfiguracja:
1. Ustawienie konfiguracyjne rrdstats.rrd_directory wskazuje katalog w którym są składowane
   statystyki w postaci plików rrd (domyślnie podkatalog rrd w katalogu głównym wtyczki)
2. Ustawienie konfiguracyjne rrdstats.rrdtool_binary powinno wskazywać pełną ścieżkę
   do pliku rrdtool (domyślnie /usr/bin/rrdtool).
3. Ustawienie konfiguracyjne rrdstats.online_freq powinno zawierać liczbę sekund co jaką
   aktualizowany jest wykres liczby aktywnych komputerów (domyślnie dziedziczone z
   rrdstats.stat_freq, a następnie z phpui.stat_freq).
4. Ustawienie konfiguracyjne rrdstats.stat_freq powinno zawierać liczbę sekund co jaką
   aktualizowane są wykresy ruchu komputerów (domyślnie dziedziczone z phpui.stat_freq).
5. Ustawienie konfiguracyjne rrdstats.online_update decyduje o tym, czy skrypty opisane
   dalej uaktualniają czas ostatniej dostępności komputera (domyślnie wyłączone).
6. Ustawienia konfiguracyjne rrdstats.{connect,update,disconnect}_pattern określają
   poprzez wyrażenia regularne wzorce komunikatów z pliku linelog

Działanie:
   Pliki ze statystykami w formacie rrd zbierane są w katalogu rrdstats.rrd_directory.
Do wypełniania danymi plików rrd trzeba uruchamiać cyklicznie, któreś ze skryptów z
podkatalogu bin katalogu głównego wtyczki. Przeznaczenie skryptów w tym katalogu jest
następujące:
1. lms-online.php - gromadzenie statystyk dostępności komputerów w pliku online.rrd
   w oparciu o pole bazy danych nodes.lastonline.
2. lms-radiusaccounting.php - zbieranie statystyk ruchu komputerów w plikach <node-id>.rrd
   (wersja dedykowana do zbierania statystyk z serwera radius w formacie linelog).
   Plik doc/freeradius/modules/linelog zawiera konfigurację freeradius formatu gromadzonych
   informacji o ruchu w postaci pliku tekstowego zgodnego z tym skryptem.
3. lms-traffic.php - zbieranie statystyk ruchu komputerów w plikach <node-id>.rrd
   (wersja dedykowana do zbierania statystyk z pliku tekstowego traffic.log o formacie
   identycznym jak używany przez skrypt perlowy lms-traffic).
