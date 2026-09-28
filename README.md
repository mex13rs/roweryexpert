# RoweryExpert — Panel Serwisowy

Panel serwisowy dla serwisu rowerowego: przyjmowanie zgłoszeń, zdjęcia usterki,
kalendarz terminów, wydruk potwierdzenia zlecenia A4 z kodem QR, katalog usług
i wydanie roweru z kartą na wydruku. **PHP 8 + MySQL**, bez builda i frameworka —
wrzucasz pliki na hosting i działasz.

## Wymagania

- PHP **8.0** lub nowszy,
- rozszerzenia: `pdo_mysql`, `mbstring`,
- MySQL / MariaDB,
- hosting z zapisem do katalogu projektu (klasyczny hosting FTP wystarczy —
  SSH nie jest potrzebne).

## Instalacja (5 minut)

1. **Pobierz kod** — z tej strony GitHuba: *Code → Download ZIP* (albo `git clone`).
2. **Wgraj pliki** na hosting przez FTP do katalogu publicznego
   (np. `public_html/serwis/`) — uwaga: w katalogach ukrytych jest plik
   `.user.ini` z limitami wgrywania zdjęć, upewnij się, że Twój klient FTP
   pokazuje pliki ukryte.
3. **Załóż bazę danych** w panelu swojego hostingu (zwykle: szukaj fraz
   „baza danych”, „MySQL”, „SQL”). Zapisz cztery dane: host, nazwę bazy,
   użytkownika i hasło. Baza może być pusta — tabele utworzy instalator.
4. **Otwórz instalator** w przeglądarce: `https://twojadomena.pl/…/install.php`
   (jeśli wejdziesz na `serwis.php` bez konfiguracji, instalator uruchomi się
   sam). Krok po kroku wpiszesz:
   - dane bazy (z testem połączenia),
   - adres i telefon serwisu (trafiają na potwierdzenie zlecenia),
   - link do wizytówki Google (z niego powstanie QR „Oceń nas” na wydruku),
   - hasło konta administratora (login: `admin`).
5. **Po instalacji usuń plik `install.php` z serwera (FTP)** — to konieczne
   dla bezpieczeństwa. Zrobione: wejdź na `serwis.php` i zaloguj się.

> Instalator uruchamia się tylko raz — gdy nie istnieje plik `config.php`.
> Po instalacji jego ponowne otwarcie wyświetli wyłącznie przypomnienie
> o usunięciu pliku.

## Aktualizacja (ręczna)

Wersje są publikowane jako *Release* z gotowym ZIP-em (zakładka *Releases*).
Aktualizacja nie wymaga instalatora:

1. Pobierz ZIP najnowszej wersji z *Releases*.
2. Wgraj jego zawartość **na stare pliki** przez FTP (nadpisanie).
   **Nigdy nie ruszaj** plików `config.php` i katalogu `uploads/` —
   to Twoja konfiguracja i zdjęcia klientów.
3. Wejdź na stronę panelu — schemat bazy zaktualizuje się automatycznie
   (migracje uruchamiają się przy pierwszym uruchomieniu).

## Konfiguracja

Cała konfiguracja mieszka w `config.php` (generowany przez instalator).
Wzorzec dla ludzi instalujących ręcznie: `config.example.php` — skopiuj na
`config.php` i uzupełnij wartości `UZUPELNIJ`.

Stałe dotyczące Twojego serwisu:

| Stała | Opis |
|---|---|
| `SERVICE_ADDRESS` | ulica (stopka wydruku) |
| `SERVICE_CITY` | kod pocztowy i miasto (stopka wydruku) |
| `SERVICE_PHONE` | telefon serwisu (stopka wydruku) |
| `GOOGLE_MAPS_URL` | link do wizytówki Google (QR „Oceń nas”) |
| `SITE_URL` | opcjonalny pełny adres URL panelu |

Nazwa **RoweryExpert** jest stała — projekt jest serią serwisów jednej marki
i nie jest konfigurowalny.

**`config.php` zawiera hasła — nigdy nie udostępniaj go nikomu i nie wrzucaj
do gita** (jest na liście `.gitignore`).

## Budowa projektu

- `serwis.php` — cały frontend (wejście, routing, logowanie),
- `config.php` — konfiguracja + funkcje wspólne (`db()`, `auth_*`, walidacje),
  sam tworzy bazę, tabele i migracje przy pierwszym połączeniu,
- `install.php` — instalator (nie jest potrzebny po instalacji),
- `api/*.php` — cienkie endpointy JSON,
- `partials/` — fragmenty widoku, `assets/` — CSS i JS,
- `instrukcja.html` — instrukcja obsługi panelu (link w stopce).

## Rozwój

Kod źródłowy: `git clone`, zmiany na gałąź `main`, wersjonowanie `X.Y`
(`APP_VERSION` w `config.php` + wpis w `CHANGELOG.md`). Komunikaty UI i
komentarze po polsku, `declare(strict_types=1)`, PDO z prepared statements.

## Licencja

[MIT](LICENSE) — zobacz plik `LICENSE`.
