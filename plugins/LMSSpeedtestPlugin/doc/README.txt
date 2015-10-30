Instalacja:
1. Zawartość paczki umieścić w katalogu plugins w LMS.
2. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
3. W katalogu www utworzyć plik simple.download.test o rozmiarze 100 MiB:
   dd if=/dev/urandom of=simple.download.test bs=1M count=100

Konfiguracja:
1. Podkatalog www powinien zostać wskazany jako katalog główny wirtualnego
   hosta np. speedtest.firma.pl
2. Dostosować ustawienia konfiguracji LMS w sekcji 'speedtest'.
3. Uaktywnić moduł speedtest panelu abonenckiego w LMS.
4. Ustawić adres URL http://speedtest.firma.pl w ustawieniach modułu
   speedtest z poziomu LMS.

Działanie:
   W LMS zapisywane są zarówno wyniki testów wykonywanych przez klientów
bezpośrednio w witrynie speedtest.firma.pl jak i z panelu abonenckiego.
Należy pamiętać o tym, żeby dostęp do speedtest.firma.pl odbywał się
z adresów źródłowych klientów bez NAT.

Wtyczka powstała we współpracy ze http://speedtest.pl.
Możliwe jest dostosowanie aplikacji wykonującej test do własnych wymagań
- więcej informacji znajduje się pod adresem:
http://www.speedtest.pl/testery/
