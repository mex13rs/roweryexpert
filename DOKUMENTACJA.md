# RoweryExpert — pełna dokumentacja aplikacji panelu serwisu rowerowego

**Wersja:** 3.8.4 (2026-09-29) · **Stack:** PHP 8 (PDO/MySQL), vanilla JS, CSS · **Licencja:** MIT

Kompletna dokumentacja aplikacji **RoweryExpert — Panel Serwisowy** znajdującej się w katalogu
`/rower/` na serwerze FTP. Powstała na podstawie analizy całego kodu źródłowego, istniejącej
instrukcji (`instrukcja.html`) oraz changelogu (`CHANGELOG.md`).

**Spis treści:**

1. Część I — Przegląd, wymagania, instalacja, konfiguracja
2. Część II — Katalog funkcjonalności
3. Część III — Kod: punkt wejścia, instalator, widoki, CSS
4. Część IV — Backend: `config.php`, API, bezpieczeństwo
5. Część V — Frontend JS (1/2): rdzeń, lista, formularz, druk
6. Część VI — Frontend JS (2/2): karta, kalendarz, ustawienia, skaner
7. Część VII — Historia zmian (changelog)

---

# Część I — Przegląd, wymagania, instalacja, konfiguracja

**RoweryExpert — Panel Serwisowy** to aplikacja dla serwisu rowerowego: przyjmowanie zgłoszeń,
zdjęcia usterki, kalendarz terminów, wydruk potwierdzenia zlecenia A4 z kodem QR, katalog usług
i wydanie roweru z kartą na wydruku. **PHP 8 + MySQL**, bez builda i frameworka — wrzucasz pliki
na hosting i działasz.

## Wymagania

- PHP **8.0** lub nowszy,
- rozszerzenia: `pdo_mysql`, `mbstring`,
- MySQL / MariaDB,
- hosting z zapisem do katalogu projektu (klasyczny hosting FTP wystarczy — SSH nie jest potrzebne).

## Instalacja (5 minut)

1. **Pobierz kod** (GitHub: *Code → Download ZIP* albo `git clone`).
2. **Wgraj pliki** na hosting przez FTP do katalogu publicznego (np. `public_html/serwis/`) —
   w katalogach ukrytych jest plik `.user.ini` z limitami wgrywania zdjęć, upewnij się, że
   klient FTP pokazuje pliki ukryte.
3. **Załóż bazę danych** w panelu hostingu. Zapisz cztery dane: host, nazwę bazy,
   użytkownika i hasło. Baza może być pusta — tabele utworzy instalator.
4. **Otwórz instalator** w przeglądarce: `https://twojadomena.pl/…/install.php` (jeśli
   wejdziesz na `serwis.php` bez konfiguracji, instalator uruchomi się sam). Krok po kroku
   wpiszesz: dane bazy (z testem połączenia), adres i telefon serwisu, link do wizytówki
   Google (QR „Oceń nas”), hasło konta administratora (login: `admin`).
5. **Po instalacji usuń plik `install.php` z serwera (FTP)** — to konieczne dla bezpieczeństwa.
   Wejdź na `serwis.php` i zaloguj się.

> Instalator uruchamia się tylko raz — gdy nie istnieje plik `config.php`. Po instalacji
> jego ponowne otwarcie wyświetli wyłącznie przypomnienie o usunięciu pliku.

## Aktualizacja (ręczna)

Wersje są publikowane jako *Release* z gotowym ZIP-em (zakładka *Releases*). Aktualizacja
nie wymaga instalatora:

1. Pobierz ZIP najnowszej wersji z *Releases*.
2. Wgraj jego zawartość **na stare pliki** przez FTP (nadpisanie). **Nigdy nie ruszaj** plików
   `config.php` i katalogu `uploads/` — to Twoja konfiguracja i zdjęcia klientów.
3. Wejdź na stronę panelu — schemat bazy zaktualizuje się automatycznie (migracje uruchamiają
   się przy pierwszym uruchomieniu).

Panel ma też wbudowany mechanizm sprawdzania aktualizacji na GitHubie
(`check_update()` / `do_update()` w `config.php`, baner + okno „Aktualizacja panelu” w UI —
szczegóły w Częściach IV i V).

## Konfiguracja

Cała konfiguracja mieszka w `config.php` (generowany przez instalator). Wzorzec dla ludzi
instalujących ręcznie: `config.example.php` — skopiuj na `config.php` i uzupełnij wartości
`UZUPELNIJ`.

Stałe dotyczące serwisu:

| Stała | Opis |
|---|---|
| `SERVICE_ADDRESS` | ulica (stopka wydruku) |
| `SERVICE_CITY` | kod pocztowy i miasto (stopka wydruku) |
| `SERVICE_PHONE` | telefon serwisu (stopka wydruku) |
| `GOOGLE_MAPS_URL` | link do wizytówki Google (QR „Oceń nas”) |
| `SITE_URL` | opcjonalny pełny adres URL panelu |

Nazwa **RoweryExpert** jest stała — projekt jest serią serwisów jednej marki i nie jest
konfigurowalna. **`config.php` zawiera hasła — nigdy nie udostępniaj go nikomu i nie wrzucaj
do gita** (jest na liście `.gitignore`).

## Struktura projektu

```
rower/
├── serwis.php            # punkt wejścia (routing, logowanie, składanie strony)
├── config.php            # konfiguracja + funkcje wspólne (NIE udostępniać!)
├── config.example.php    # wzorzec konfiguracji do instalacji ręcznej
├── install.php           # instalator (usunąć po instalacji)
├── api/                  # cienkie endpointy JSON
│   ├── zgloszenia.php    # zgłoszenia (CRUD, statusy, kosz, usługi, notatki)
│   ├── zdjecia.php       # zdjęcia (upload, lista, usuwanie)
│   ├── uslugi.php        # katalog usług
│   ├── uzytkownicy.php   # konta użytkowników
│   ├── ustawienia.php    # ustawienia ogólne, dane serwisu, moduły
│   └── konto.php         # konto bieżącego użytkownika (m.in. zmiana hasła)
├── partials/             # fragmenty widoku
│   ├── head.php          # <head>: CSS, QRious, APP_CFG
│   ├── login.php         # ekran logowania
│   ├── panel.php         # główny układ panelu (nagłówek, zakładki, lista, formularz)
│   ├── scripts.php       # <script>: ładuje assets/js + zmienne inline
│   ├── modaly.php        # modale systemowe (potwierdzenie, status, kosz, powitanie…)
│   ├── modal-karta.php   # karta zgłoszenia (podgląd) i modal edycji
│   ├── modal-ustawienia.php # modal ustawień (zakładki)
│   ├── modal-zdjecia.php # modal zdjęć + lightbox
│   └── wydruk.php        # szablony wydruku A4 (przyjęcie / karta wydania)
├── assets/
│   ├── css/panel.css     # cały styl panelu (motywy, akcenty, responsywność, druk)
│   └── js/               # 12 plików: core, api, lista, formularz, filtry, druk,
│                         # karta, kalendarz, ustawienia, uzytkownicy, motyw, skaner, zdjecia
├── uploads/              # zdjęcia klientów (+ backup/ chroniony .htaccess)
├── instrukcja.html       # instrukcja obsługi panelu (link w stopce)
├── README.md             # opis projektu
└── CHANGELOG.md          # historia zmian
```

## Rozwój

Kod źródłowy: `git clone`, zmiany na gałąź `main`, wersjonowanie `X.Y.Z` (`APP_VERSION`
w `config.php` + wpis w `CHANGELOG.md`). Komunikaty UI i komentarze po polsku,
`declare(strict_types=1)`, PDO z prepared statements.

## Licencja

MIT — zobacz plik `LICENSE`.

---

---

# Część II — Katalog funkcjonalności

Kompletny wykaz **wszystkich funkcji** aplikacji (zgodnie z kodem, instrukcją i changelogiem).
Szczegóły techniczne w Częściach III–VI.

## 1. Logowanie, sesje i konta

- Ekran logowania (`partials/login.php`): pola **login** i **hasło** — nie ma opcji „Zapamiętaj”.
  Domyślny login administratora: `admin`.
- Sesja trwa **14 dni** (cookie `HttpOnly`, `SameSite=Lax`, flaga `Secure` przy HTTPS);
  token sesji przechowywany w bazie jako hash SHA-256, odnawiany przy aktywności (sliding).
- **Ochrona brute-force**: po 5 nieudanych próbach logowania blokada na 15 minut
  (tabela `login_attempts`). Brak resetu hasła e-mailem — resetuje administrator.
- **Dwie role**: `admin` i `pracownik`. Admin zarządza kontami (dodawanie, zmiana roli,
  reset hasła, wyłączanie, usuwanie — tylko kont bez zgłoszeń). Pracownik nie widzi
  zakładki Użytkownicy.
- Nazwa zalogowanego użytkownika w nagłówku panelu; zmiana własnego hasła
  w **Ustawieniach → Ogólne**.
- Po udanym logowaniu (przy włączonym module **Powitanie**, wymaga Kalendarza) — okno
  **„Podsumowanie dnia”**: ile rowerów na dziś/jutro, ostrzeżenie „Po terminie”.
- Wylogowanie: ikona w nagłówku (`?logout`).

## 2. Przyjęcie roweru (nowe zgłoszenie)

- Formularz „Przyjmij nowy rower” w lewej kolumnie panelu (na telefonie — pełny ekran).
- Pola: **Nazwa roweru**, **Data przyjęcia** (domyślnie dziś), **Planowany odbiór**
  (domyślnie +2 dni), **Telefon klienta** (autoformatowanie polskie, min. 9 cyfr, walidacja),
  **Opis usterki**, **Zdjęcia** (do 20 plików na zgłoszenie; limit łączny 100 MB na konto;
  ostrzeżenie od 80% zużycia), **Usługi** (checkboxy z katalogu — przy włączonym module).
- Dwa tryby zapisu: **„Zapisz, Drukuj i Dodaj do Kalendarza”** (PC) oraz **„Zapisz tylko
  w bazie”** (telefon) — etykieta dopasowuje się do włączonych modułów.
- Każde zgłoszenie dostaje **numer serwisowy** formatu `RO-ROK-ID` (np. `RO-2026-0042`).
- Zgłoszenie utworzone z telefonu jest do czasu potwierdzenia **maskowane** („zamazane”)
  na liście; na PC przycisk **„Potwierdź”** odblokowuje je i uruchamia kalendarz + wydruk.
  Autorstwo: `created_by`, potwierdzenie: `confirmed_by`.
- Po zapisie z telefonu: pełnoekranowy monit „Zapisano” (z liczbą zdjęć, gdy były).

## 3. Statusy i przepływ pracy

- Trzy statusy zmieniane **kliknięciem odznaki na karcie**: **W serwisie → Gotowy →
  Odebrany** (kolejne kliknięcie cykluje status, `cycleStatus()`).
- Najszybsze wydanie: przycisk **„Wydaj rower”** w karcie podglądu — ustawia „Odebrany”,
  zapisuje `confirmed_by` (kto wydał), a przy modułach **druk** + **Karta wydania** od razu
  drukuje Kartę Wydania Roweru; potem przycisk gaśnie („Rower już wydany”). Cofnięcie
  wydania nie zostawia trwałej plakietki „kto wydał”.
- Na liście: kółko z inicjałem **kto przyjął** przy dacie przyjęcia i drugie kółko
  **kto wydał** przy odznace statusu.
- Filtr **„Moje”** — tylko zgłoszenia bieżącego użytkownika; rola pracownik nie może
  koszować/przywracać cudzych zgłoszeń (`owner_guard`).

## 4. Lista zgłoszeń, filtry, sortowanie, wyszukiwanie

- **Kafle dashboardu** nad listą („W serwisie”, „Gotowe do odbioru”, „Po terminie”,
  „Odbiory dziś/jutro”) — kliknięcie ustawia filtr (aktywny podświetlony).
- Filtry: **Wszystkie**, **Odebrane** (dzisiaj), **Jutro**, **Po terminie**, **Moje**,
  **Kosz** (z licznikiem).
- **8 wariantów sortowania** (kolejność polska `Intl.Collator('pl')`): termin najbliżej /
  najdalej, przyjęcie najnowsze / najstarsze, nazwa rosnąco / malejąco, status — domyślnie
  „najbliższy termin odbioru”; wybór zapisywany w `localStorage`.
- **Wyszukiwanie** po: nazwie roweru, telefonie, opisie, numerze serwisowym i wykonanych
  czynnościach (debounce 700 ms na PC); **na telefonie min. 4 znaki numeru serwisowego**,
  wynik to jedna karta (przy wielu dopasowaniach prośba o więcej cyfr).
- Na karcie listy: miniatura, status, termin (kolorowanie: po terminie / dziś / jutro),
  plakietki osób, akcje (potwierdź, druk, kalendarz, kosz).
- Brak paginacji — pełny rendering (`renderServicesList()`).
- Zgłoszenia z telefonu przed potwierdzeniem: **maska „pending”**.

## 5. Karta zgłoszenia i edycja

- Otwierana z: kliknięcia karty na liście, wpisu w kalendarzu, skanu QR, wyszukiwarki
  na telefonie. Zawiera: numer serwisowy, nazwę roweru, klikalną odznakę statusu, daty,
  **klikalny telefon** (`tel:`), opis, autora przyjęcia/wydania.
- **Wykonane czynności** (moduł): checkboxy tylko z usług zaznaczonych przy przyjęciu,
  odhaczane z **autozapisem**; na Karcie Wydania drukują się jako ☑ tylko zaznaczone;
  po wydaniu/w koszu — tylko podgląd.
- Pole **„Notatki”** z autozapisem (to samo `service_notes` co w edycji).
- **„Edytuj”** → modal `#edit-modal`: nazwa, daty, telefon, opis, notatki (`action=update`).
- **„Zdjęcia”** → modal zdjęć: lightbox, dodawanie, usuwanie z potwierdzeniem, licznik limitu.
- Info, gdy zgłoszenie wymaga potwierdzenia z telefonu; sekcje karty zależą od modułów.

## 6. Zdjęcia usterki

- Wgrywanie z formularza i z modala (multi-file), miniatury na liście i w karcie, powiększenie
  w **lightboxie**.
- **Limity**: 100 MB łącznego zużycia (`FOTO_LIMIT_MB`), do 20 plików na zgłoszenie,
  ostrzeżenie przy **80%** (`FOTO_WARN_PCT`), podgląd zużycia („12,4 MB / 100 MB”).
- Usuwanie zdjęcia z potwierdzeniem; „Usuń trwale” kasuje też zdjęcia zgłoszenia.
- `uploads/` ma `.user.ini` (limity PHP), `uploads/backup/` chroniony `.htaccess`
  „Require all denied”.

## 7. Kalendarz terminów

- Widok miesięczny (ikona w nagłówku), siatka 42 komórek, nawigacja ‹ ›, dziś podświetlone,
  legenda.
- **Chipy** od daty przyjęcia do planowanego odbioru w **każdym dniu z okresu**; kolory:
  żółty = w serwisie, zielony = gotowy, czerwona ramka = po terminie, szary = odebrany.
- Przepełnienie dnia: **„+N więcej”** z dymkiem pełnej listy (rozwijany „z komórki dnia”).
- Kliknięcie chipu → **karta zgłoszenia**.
- Moduł **Kalendarz** wyłącza: ikonę, pole „Planowany odbiór”, kafle terminowe, sortowanie
  po terminie, linki Kalendarza Google i powitanie.

## 8. Kalendarz Google

- Przycisk **„Kalendarz”** na karcie (PC): link do Google Calendar
  (`generateGoogleCalendarLink()`, daty konwertowane do UTC) z terminem odbioru
  i przypomnieniem — otwiera w nowej karcie.

## 9. Wydruk (potwierdzenie przyjęcia i karta wydania)

- Arkusz **A4 poziomo**, dwie kolumny:
  - **lewa — klient**: nazwa roweru, telefon, numer serwisowy, opis, daty; na Karcie
    Wydania dodatkowo lista wykonanych czynności (☑), **podpis klienta** i **pieczątka
    serwisu** (nad kreską z adresem z Ustawień) oraz **QR z linkiem do oceny w Google**
    („Oceń nas”);
  - **prawa — serwis**: te same dane + „Wykonane czynności” (tylko karta wydania; znika
    gdy puste lub moduł wyłączony) + „Notatki” (tylko po uzupełnieniu) + na samym dole
    **kwadratowy QR z numerem serwisowym** — do naklejenia na ramę.
- Dwa tryby `triggerPrint()`: **przyjęcie** i **wydanie**.
- QR generowane lokalnie przez **QRious** (z `head.php`), zapasowo `api.qrserver.com`;
  treść QR oceny z `APP_CFG.mapsUrl`.
- Opcjonalne automatyczne dodanie terminu do kalendarza przy zapisie z drukiem.
- Reguły `@media print` w `panel.css` (A4 poziomo, ukrycie interfejsu).

## 10. Skaner QR (telefon)

- Ikona aparatu przy wyszukiwarce → `#scan-modal`: podgląd z kamery, pętla `scanTick()`,
  detekcja przez **jsQR** (`detectCodeFromVideo()`), ładowanie z CDN (`loadJsQR()`).
- Poprawny skan: wpisuje numer do wyszukiwarki i **otwiera kartę zgłoszenia**
  (`handleScanResult()`); wymagane **HTTPS** + zgoda na kamerę; przy zablokowanym CDN —
  ręczne przepisanie numeru.
- Moduł **Skaner QR** wyłącza przycisk aparatu (szukanie tekstowe działa dalej).

## 11. Katalog usług

- Własne usługi serwisu: **Ustawienia → Dodaj usługi** (nazwa + cena; `api/uslugi.php`).
- Usługi to checkboxy w formularzu przyjęcia i podstawa sekcji „Wykonane czynności”.
  Moduł **Katalog usług** chowa checkboxy i zakładkę (dane zostają).

## 12. Ustawienia panelu

Ikona zębatki → modal (`partials/modal-ustawienia.php`), zakładki:

- **Ogólne** — statystyki zużycia zdjęć, zmiana hasła bieżącego konta, info o koncie.
- **Dane serwisu** — edycja po instalacji: `SERVICE_ADDRESS`, `SERVICE_CITY`,
  `SERVICE_PHONE`, `GOOGLE_MAPS_URL` (trafiają na wydruk i QR).
- **Dodaj usługi** — zarządzanie katalogiem usług.
- **Użytkownicy** (admin) — lista kont (login, rola, flagi), dodawanie, karta konta
  `#user-modal` (status, ostatnie logowanie, utworzenie konta, stan hasła, zmiana hasła,
  zmiana roli, wyłączanie/włączanie, usuwanie tylko kont bez zgłoszeń).
- **Moduły** — przewijana lista przełączników (patrz niżej).

W nagłówku obok ustawień: **przełącznik motywu** (jasny/ciemny, domyślnie ciemny)
i **paleta akcentu** (żółty Media Expert domyślnie; zielony, czerwony, niebieski,
pomarańczowy — zapamiętywana w przeglądarce; zmienia logo, faviconkę i akcenty UI).

## 13. Moduły (włączanie/wyłączanie funkcji)

Wyłączenie ukrywa i **blokuje po stronie serwera** część panelu (PC i telefon jednocześnie),
**nie kasuje danych**, zmiana zapisuje się natychmiast. 10 modułów (domyślnie wszystkie
włączone):

| Moduł | Skutki wyłączenia |
|---|---|
| **Kalendarz** | znika ikona, pole „Planowany odbiór”, kafle terminowe, sortowanie po terminie, linki Google Calendar, powitanie |
| **Zdjęcia** | brak wgrywania, miniatur i licznika |
| **Skaner QR** | znika przycisk aparatu (tekstowe szukanie działa) |
| **Katalog usług** | brak checkboxów usług i zakładki |
| **Drukowanie** | brak wydruku; etykieta przycisku formularza się zmienia |
| **Kosz** | znika filtr i kasowanie — w tym stanie zgłoszeń nie da się skasować |
| **Kolorystyka** | znika paleta; logo i faviconka wracają do żółtego (wybór zostaje) |
| **Powitanie** | brak okna „Podsumowanie dnia” (wymaga Kalendarza) |
| **Karta wydania** | „Wydaj rower” tylko zmienia status, bez auto-druku (ręczny druk działa) |
| **Wykonane czynności** | chowa checkboxy w karcie i ☑ na wydruku (katalog usług działa dalej) |

Rdzeń (logowanie, przyjęcie, lista, wyszukiwanie, wydanie) jest zawsze włączony.

## 14. Motyw, akcent, toasty

- Motyw **ciemny/jasny** (domyślnie ciemny) — przełącznik w nagłówku, tokeny CSS
  w `panel.css`, `color-scheme` na `body`.
- Akcent: 5 kolorów (żółty, zielony, czerwony, niebieski, pomarańczowy) przez klasy
  `body.accent-*`; zmienia przyciski, logo (`logo-*.png`) i faviconkę (`favicon-*.png`).
- Toasty (komunikaty) — zawsze tylko jeden naraz; nakładka „Zapisywanie” z animacją
  rowerzysty (nie pojawia się na wydruku).
- Pomocniki UI: `escapeHtml`, autoformat telefonu, formatowanie dat po polsku.

## 15. Aktualizacje panelu

- `check_update()` sprawdza GitHub Releases; w panelu baner/okno **„Aktualizacja panelu”**
  z potwierdzeniem (modal, nie okno systemowe).
- `do_update()`: pobranie paczki, **kopia zapasowa** (ZIP w `uploads/backup/`), podmiana
  plików z **pominięciem `config.php` i `uploads/`**, przebudowa `config.php` z nowego
  `config.example.php` (`config_przebuduj()` z twardą walidacją stałych), migracje,
  czyszczenie opcache.
- Katalog backupu chroniony `.htaccess` (od 3.8.4).

## 16. Kosz (soft delete)

- Kosz na karcie **nie kasuje od razu** — zgłoszenie trafia do kosza (`deleted_at`).
- Filtr **Kosz** z licznikiem; akcje: **„Przywróć”** (wraca na listę) i **„Usuń trwale”**
  (kasuje zgłoszenie ze zdjęciami — nieodwracalne, wymaga potwierdzenia).
- Własność: pracownik przywraca/kasuje tylko swoje zgłoszenia (`owner_guard`).

## 17. Model danych (skrót)

Siedem tabel MySQL (tworzonych/modyfikowanych automatycznie przez migracje w `config.php`):

| Tabela | Zawartość |
|---|---|
| `zgloszenia` | zgłoszenia: rower, telefon, opis, daty, status, numer `service_no`, notatki, `services_done`, autorzy, `deleted_at` |
| `zdjecia` | zdjęcia zgłoszeń (nazwy plików, powiązanie) |
| `ustawienia` | ustawienia klucz/wartość (dane serwisu, moduły, wersja) |
| `uslugi` | katalog usług (nazwa, cena) |
| `users` | konta (login, hash hasła, rola, flagi, ostatnie logowanie) |
| `sesje` | sesje (token-hash, wygaśnięcie) |
| `login_attempts` | próby logowania (anti brute-force) |

## 18. Bezpieczeństwo — zestawienie

- PDO z **prepared statements** wszędzie; `declare(strict_types=1)`.
- Hasła: `password_hash()` (bcrypt); sesje: SHA-256 token + sliding TTL.
- Cookie: `HttpOnly`, `SameSite=Lax`, `Secure` przy HTTPS (wykrywanie też przez nagłówek
  reverse proxy).
- Brak CSRF w API — ochrona z `SameSite=Lax`; token CSRF (`hash_equals`) tylko
  w instalatorze.
- Anti brute-force 5 prób / 15 minut; brak funkcji e-mail (aplikacja nie wysyła maili).
- `config.php` wykluczony z kopii zapasowej i podmiany aktualizacji; `uploads/backup/`
  z `.htaccess` „Require all denied”.
- Odpowiedzi API i strony z nagłówkami `no-store/no-cache` (ochrona przed cache LiteSpeed).
- Moduły wyłączone blokowane też po stronie serwera; gate instalacji: brak `config.php`
  → 503 JSON / przekierowanie do instalatora.

---

---

# Część III — Kod: punkt wejścia, instalator, widoki, CSS

Poniższy fragment opisuje dokładnie to, co jest w kodzie źródłowym (`serwis.php`,
`install.php`, `partials/*`, `assets/css/panel.css`, `instrukcja.html`). Wszystkie
identyfikatory, nazwy pól, klas i stałych są cytowane dosłownie z plików — nie dodano
żadnej funkcjonalności, której w kodzie nie ma.

---

## 1. `serwis.php` — punkt wejścia aplikacji

`serwis.php` (51 linii, `declare(strict_types=1)`) to jedyne wejście do panelu. Nie
renderuje samodzielnie HTML — działa jak kontroler, który składa stronę z plików
`partials/*`.

### 1.1 Auto-launch instalatora (brak `config.php`)

```php
if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}
require __DIR__ . '/config.php';
```

Brak pliku `config.php` = panel jeszcze nie skonfigurowany → natychmiastowe
przekierowanie do `install.php` i `exit`. Dopiero po tym warunku ładowany jest
`config.php`, który udostępnia stałe (`APP_VERSION`, `DB_*`, `SESSION_TTL`,
`MAX_PHOTOS_TOTAL_BYTES`, `AUTH_COOKIE`, `SERVICE_ADDRESS`…) oraz funkcje wspólne
(`db()`, `auth_login()`, `auth_logout()`, `auth_is_authenticated()`, `auth_user()`,
`setting_set()`, `dane_instancji()`, `wersja_aplikacji()`).

### 1.2 Znacznik `SERWIS_PANEL`

```php
define('SERWIS_PANEL', true);
```

Znacznik ustawiany **przed** załadowaniem partiali. Każdy plik w `partials/` zaczyna
się od `<?php defined('SERWIS_PANEL') or exit; ?>` — wejście wprost do
`partials/*.php` (np. przez URL) niczego nie renderuje.

### 1.3 Nagłówki anti-cache

```php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
```

Strona jest dynamiczna (zależna od sesji), więc nie może być cache’owana — komentarz
w kodzie mówi wprost, że bez tego LiteSpeed zwracał starą wersję HTML po każdej
zmianie plików.

### 1.4 Routing: logowanie i wylogowanie

* **Logowanie** — `POST` z polem `action=login`:
  * `$loginError = auth_login((string)$_POST['login'], (string)$_POST['password']);`
  * przy błędzie zmienna `$loginError` trafia do `partials/login.php` i jest
    renderowana w `<p class="login-error">`,
  * przy sukcesie: `header('Location: <ścieżka bez query>?powitanie=1'); exit;` —
    parametr `?powitanie=1` powoduje, że `head.php` dodaje `data-powitanie="1"` do
    `<body>`, a JS otwiera okno „Podsumowanie dnia” (`#welcome-modal`).
* **Wylogowanie** — GET z parametrem `?logout`:
  `auth_logout()` (kasuje sesję w tabeli `sesje` i czyści cookie), następnie
  przekierowanie na `strtok($_SERVER['REQUEST_URI'], '?')` (bez query stringa).

Innych „tras” nie ma — reszta to zwykłe wyświetlenie strony.

### 1.5 Stan sesji

```php
$authenticated = auth_is_authenticated();
$currentUser   = auth_user();
```

Obie zmienne są używane w partialach: `$authenticated` steruje wyświetleniem ekranu
logowania i atrybutem `hidden` na `#app-container`, `$currentUser['rola']`
(`admin` / `pracownik`) decyduje o widoczności zakładek ustawień tylko dla admina,
`$currentUser['login']`/`id` trafiają do nagłówka i do stałej JS `USER_ID`.

### 1.6 Kolejność montowania partiali

```php
require __DIR__ . '/partials/head.php';             // otwiera <html><head><body>
require __DIR__ . '/partials/login.php';            // ekran logowania (gdy niezalogowany)
require __DIR__ . '/partials/panel.php';            // główna siatka panelu
require __DIR__ . '/partials/modal-ustawienia.php'; // ustawienia + karto konta (admin)
require __DIR__ . '/partials/modaly.php';           // modale ogólne
require __DIR__ . '/partials/modal-karta.php';      // podgląd zgłoszenia
require __DIR__ . '/partials/modal-zdjecia.php';    // zdjęcia, lightbox, toasty, overlay
require __DIR__ . '/partials/wydruk.php';           // szablon wydruku A4
require __DIR__ . '/partials/scripts.php';          // stałe JS + <script> + </body></html>
```

Kolejność jest znacząca: `head.php` otwiera dokument, `scripts.php` go zamyka;
pośrednie pliki to fragmenty wtrącane w środek `<body>`.

### 1.7 Struktura wynikowej strony

```
<html lang="pl">
  <head>  Barlow, QRious 4.0.2, window.APP_CFG.mapsUrl, panel.css?v=…
  <body class="dark-theme" [data-powitanie="1"]>
    <div class="login-screen"> …            — tylko gdy brak sesji
    <div id="app-container" [hidden]>
      <div class="container">
        <div id="update-banner">            — baner aktualizacji
        <header> logo + .header-actions </header>
        <div class="dashboard-grid">        — karta formularza + karta listy
        <footer class="app-footer">
      </div>
    </div>
    #settings-modal / #user-modal (ustawienia)
    #confirm-modal, #welcome-modal, #edit-modal, #phone-success-modal,
    #scan-modal, #calendar-modal, #update-modal
    #detail-modal (karta zgłoszenia)
    #photos-modal, #lightbox, #toast-container, #upload-overlay
    <div id="print-receipt"> … szablon wydruku A4
    <script> IS_AUTHENTICATED / FOTO_LIMIT_MB / USER_ID </script>
    assets/js/*.js
  </body>
</html>
```

---

## 2. `install.php` — instalator krok po kroku

Samodzielny plik (621 linii) — działa **bez** `config.php`, na czystym PHP + PDO, i sam
generuje `config.php`. Komentarz nagłówkowy: *po instalacji USUŃ ten plik z serwera
(FTP) — to konieczne.*

### 2.1 Inicjalizacja, zabezpieczenia, helpery

* `session_start();`, `Content-Type: text/html; charset=utf-8`, anti-cache.
* `const INST_MAX_TRIES = 10;` — ile prób testu połączenia z bazą na jedną sesję.
* `h($s)` = `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')`.
* `inst_app_version()` — wersja czytana regexem
  `/const APP_VERSION = '([^']+)'/` z `config.example.php` (bez ładowania configu);
  w razie problemów zwraca `?`.
* `inst_requirements()` — lista kontroli wymagań (patrz krok 1).
* `inst_test_db($d)` — walidacja + test PDO (patrz krok 2).
* `inst_build_config($map)` — budowa treści `config.php` przez podmianę stałych.
* **CSRF**: każdy POST musi zawierać `csrf` zgodny z `$_SESSION['inst_csrf']`
  (`hash_equals`); token = `bin2hex(random_bytes(16))`. Błąd: „Sesja wygasła —
  odśwież stronę i spróbuj ponownie.”

### 2.2 Blokada ponownej instalacji

```php
if (is_file(__DIR__ . '/config.php') && empty($_SESSION['inst_done'])) { … exit; }
```

Gdy `config.php` już istnieje (a sesja nie ma `inst_done`), instalator pokazuje wyłącznie:
box ok „**Panel jest już skonfigurowany.** Instalator nie jest już potrzebny.” oraz box
„Zrób porządek: usuń plik `install.php` z serwera (FTP)…” + przycisk
„Przejdź do panelu”. Znacznik `$_SESSION['inst_done']` pozwala natomiast wyświetlić
krok 6 podczas trwającej instalacji (mimo że `config.php` powstał).

### 2.3 Mechanika kroków (stepper i routing)

Etykiety kroków w `inst_render()`:
**`['Wymagania', 'Baza danych', 'Dane serwisu', 'Konto admin', 'Instalacja', 'Gotowe']`** —
renderowane jako `<ol class="steps">` z numerem w `<span>` i klasami `done` / `active`.

Postęp trzymany jest w `$_SESSION['inst']` (`['max' => N, 'tries' => …, 'db' => …,
'dane' => …, 'pass' => …]`). Aktualny krok: `$postStep` (po POST) → `?krok=N` →
`$inst['max']` (kontynuacja tam, gdzie skończyło się), zawsze ograniczony do `max`.

Domyślne wartości formularzy (`$vals`) poza POST-em:
`db_host=localhost`, `db_port=3306`, oraz puste `db_name/db_user/db_pass`,
`addr/city/phone/maps/site`.

### 2.4 Krok 1/6 — Wymagania serwera (`action=step1`)

`inst_requirements()` zwraca `[nazwa, OK?, podpowiedź]` dla:

1. **PHP w wersji 8.0 lub nowszej** (`PHP_VERSION_ID >= 80000`),
2. **Rozszerzenie pdo_mysql**,
3. **Rozszerzenie mbstring (polskie znaki)**,
4. **Funkcja password_hash (bezpieczne hasła)**,
5. **Katalog zapisywalny (powstanie config.php)** (`is_writable(__DIR__)`),
6. **Wzorzec konfiguracji `config.example.php`** (`is_readable`).

Widok `inst_step1()`: `<h2>Krok 1/6 — Wymagania serwera</h2>`, `<ul class="checks">`
z `✓` (klasa `yes`) / `✗` (klasa `no`) i podpowiedziami typu „Masz PHP x.y — poproś
hosting o aktualizację”. Przycisk **„Dalej — konfiguracja bazy”** (formularz z
`action=step1`) jest widoczny tylko, gdy wszystkie testy przeszły; inaczej box err
„Usuń wskazane problemy i odśwież stronę.” + lista problemów w komunikacie.

### 2.5 Krok 2/6 — Baza danych MySQL (`action=step2`)

Polа formularza: `db_host` (domyślnie `localhost`), `db_port` (domyślnie `3306`,
`min=1 max=65535`), `db_name` (placeholder `np. twoj_prefix_serwis`), `db_user`,
`db_pass` (`type=password`, puste = zachowaj dotychczasowe z sesji; placeholder
„•••••• (wprowadzone — zostaw puste, żeby nie zmieniać)”). Przyciski:
**„Testuj połączenie i dalej”** + „Wstecz”.

Walidacja w `inst_test_db()`:
* host: `^[A-Za-z0-9._-]{1,100}$`,
* port: `^\d{1,5}$` i zakres 1–65535 („np. 3306”),
* nazwa bazy: `^[A-Za-z0-9_]{1,64}$`,
* użytkownik: `^[A-Za-z0-9_]{1,64}$`,
* hasło niepuste („Podaj hasło do bazy danych.”),
* test: `new PDO('mysql:host=…;port=…;charset=utf8mb4', user, pass)` z
  `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION`, `ATTR_TIMEOUT => 8`,
  `ATTR_EMULATE_PREPARES => false` + `SELECT 1`.

Anti-brute-force: licznik `tries` w sesji; okno 15 minut (`tries_at < time()-900`
resetuje licznik), po `INST_MAX_TRIES` (10) — „Zbyt wiele prób testu połączenia —
odczekaj 15 minut…”.

Sukces → `$_SESSION['inst']['db']`, `max=3`, `Location: install.php?krok=3`.

Na dole kroku `<details>` z podsumowaniem **„Jak założyć bazę danych?”** dla hostingów:
**IQHS (iqhs.pl)** — zakładka „Bazy danych” → „Dodaj bazę MySQL”; **home.pl** — „Bazy
danych” → „Dodaj nową bazę MySQL”; **OVH** — „Bazy danych SQL” → „Utwórz bazę”;
**nazwa.pl** — „Bazy MySQL” → „Dodaj”. Info: host to zwykle `localhost`; sam panel
instalatora utworzy tabele — wystarczy pusta baza z kontem użytkownika.

### 2.6 Krok 3/6 — Dane serwisu (`action=step3`)

Polа: `addr` (Ulica, wymagane, `np. Ostrobramska 81`), `city` (Kod pocztowy i miasto,
wymagane, `np. 04-175 Warszawa`), `phone` (Telefon serwisu, wymagane, regex
`^[0-9 +\-().]{6,32}$`), `maps` (Link do wizytówki Google, wymagane,
`FILTER_VALIDATE_URL` + schemat zaczynający się od `http` — „z niego powstanie QR
«Oceń nas» na wydruku”), `site` (Adres URL panelu, opcjonalny).

Normalizacja `site`: zdjęcie zdublowanych schematów (`https://https://…`), dopisanie
brakującego (`http://` utrzymywane, domyślnie `https://`), `rtrim($site, '/')`.

Box informacyjny: dane trafią na **potwierdzenie zlecenia (wydruk A4)** oraz do kodu
QR „Oceń nas w Google”; nazwa RoweryExpert jest stała.

Błędy m.in.: „Podaj adres serwisu (ulica oraz kod i miasto) — trafia na potwierdzenie
zlecenia.”, „Podaj prawidłowy telefon serwisu (6–32 znaki…)”, „Podaj pełny link do
wizytówki Google (zaczyna się od https://…)”. Sukces → `$_SESSION['inst']['dane']`,
`max=4`, `?krok=4`.

### 2.7 Krok 4/6 — Konto administratora (`action=step4`)

* Login jest **stały: `admin`** — formularz pyta wyłącznie o hasło.
* Pola `pass1`, `pass2`: minimum **6 znaków** („zalecane 8+”), muszą być identyczne
  („Hasła nie są identyczne.”).
* Checkbox `pass_show` („Pokaż hasło”) — stan zapisywany w `$_SESSION['pass_show']`,
  przełącza `type="text"` / `type="password"`, `onchange="this.form.submit()"`.
* Box: „Tworzone jest konto **admin** (login stały). Hasło możesz zmienić później w
  panelu. Zapisz je w bezpiecznym miejscu.”
* Przyciski: **„Dalej — podsumowanie”**, „Wstecz”. Sukces → `$_SESSION['inst']['pass']`,
  `max=5`, `?krok=5`.

### 2.8 Krok 5/6 — Instalacja (`action=step5`)

Przed wykonaniem sprawdzana jest kompletność sesji (`inst['db']`, `inst['dane']`,
`inst['pass']`) — brak cofa do kroku 2/3/4.

Widok `inst_step5()`: tabela `.sum` z podsumowaniem — **Host / port bazy, Nazwa bazy,
Użytkownik bazy, Ulica, Kod i miasto, Telefon, Link Google, URL panelu (lub „—”)** oraz
wiersz **„Konto: admin + hasło (nie wyświetlamy)”**. Przycisk **„Rozpocznij instalację”**.

Co się dzieje po kliknięciu:

1. `inst_write_config($db, $dane, $pass)`:
   * `inst_build_config()` czyta `config.example.php` i przez `preg_replace_callback`
     (1 wystąpienie) podmienia stałe: **`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
     `DB_PASS`, `APP_PASSWORD`, `AUTH_COOKIE`** (losowy `'re_sess_' .
     bin2hex(random_bytes(8))`), **`SERVICE_ADDRESS`, `SERVICE_CITY`, `SERVICE_PHONE`,
     `GOOGLE_MAPS_URL`, `SITE_URL`**;
   * komentarz wzorca („UWAGA: to jest WZORZEC…”) zastępuany jest tekstem
     „Plik konfiguracji wygenerowany przez install.php. Zawiera sekrety (hasla) —
     nie udostepniaj nikomu i nie wrzucaj do gita.”;
   * `file_put_contents(config.php)` + `@chmod 0644`; błąd zapisu → RuntimeException
     „Nie udało się zapisać pliku config.php (brak prawa zapisu?).”
2. `require config.php` **na poziomie pliku** (nie w funkcji — wymagane dla `db()`),
3. `db()` — tworzy bazę, tabele, migracje i konto admin,
4. `setting_set('installed_version', APP_VERSION)`,
   `setting_set('installed_at', gmdate('Y-m-d H:i:s'))`,
5. `unset($_SESSION['inst']); $_SESSION['inst_done'] = true;` → render kroku 6.

Przy błędzie `db()`: `@unlink(config.php)` (cofamy, żeby instalator dało się powtórzyć)
+ komunikat: „Konfiguracja została zapisana, ale setup bazy danych nie powiódł się: …
Najczęstsza przyczyna: konto bazy nie ma uprawnień do tworzenia bazy — utwórz bazę w
panelu hostingu i podaj jej dane jeszcze raz.”

### 2.9 Tabele tworzone przez `db()` (`CREATE TABLE IF NOT EXISTS`)

| Tabela | Kolumny / indeksy |
|---|---|
| **`zgloszenia`** | `id INT UNSIGNED AUTO_INCREMENT`, `bike_name VARCHAR(255)`, `date_in DATE`, `date_planned DATE`, `customer_phone VARCHAR(32)`, `fault_description TEXT`, `service_notes TEXT NULL`, `services_done TEXT NULL`, `status ENUM('in_progress','completed','picked_up') DEFAULT 'in_progress'`, `service_no VARCHAR(32)` (UNIQUE `uk_service_no`), `confirmed TINYINT(1) DEFAULT 1`, `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`, `deleted_at TIMESTAMP NULL`; KEY `idx_status`; InnoDB utf8mb4_unicode_ci |
| **`zdjecia`** | `id`, `zgloszenie_id` (FK `fk_zdjecia_zgloszenie` → `zgloszenia(id)` ON DELETE CASCADE), `filename`, `original_name`, `size_bytes BIGINT`, `created_at`; KEY `idx_zgloszenie` |
| **`ustawienia`** | `klucz VARCHAR(64)` PRIMARY, `wartosc VARCHAR(255) DEFAULT ''` |
| **`uslugi`** | `id`, `nazwa VARCHAR(255)` (UNIQUE `uk_nazwa`) |
| **`users`** | `id`, `login VARCHAR(64)` (UNIQUE `uk_login`), `password_hash VARCHAR(255)`, `rola ENUM('admin','pracownik') DEFAULT 'pracownik'`, `aktywny TINYINT(1) DEFAULT 1`, `must_change_password TINYINT(1) DEFAULT 0`, `created_at`, `last_login_at NULL` |
| **`sesje`** | `id`, `token_hash CHAR(64)` (UNIQUE `uk_token`), `user_id` (FK `fk_sesje_user` → `users(id)` ON DELETE CASCADE), `expires_at DATETIME`, `created_at`; KEY `idx_sesje_user` |
| **`login_attempts`** | `id`, `klucz VARCHAR(160)`, `created_at`; KEY `idx_klucz_czas (klucz, created_at)` |

Dodatkowo `db()` robi migracje na istniejących bazach (np. dodanie kolumny `size_bytes`
w `zdjecia` i uzupełnienie jej z dysku).

**Tworzenie konta admin**: dopiero gdy `SELECT COUNT(*) FROM users` = 0, wykonywane jest
`INSERT INTO users (login, password_hash, rola) VALUES ('admin', app_password_hash(), 'admin')`,
gdzie `app_password_hash()` haszuje stałą `APP_PASSWORD` (hasło z kroku 4) przez
`password_hash($hash, PASSWORD_DEFAULT)` i zapisuje wynik w `ustawienia`.

### 2.10 Krok 6/6 — Gotowe (`inst_step6_body()` / `inst_render_step6()`)

* box ok: „**Instalacja zakończona.** Baza, tabele i konto **admin** zostały utworzone.”
* box z instrukcją: **1.** zapisz login `admin` i hasło z kroku 4; **2.** (na czerwono)
  **Usuń plik `install.php` z serwera (FTP)** — to konieczne dla bezpieczeństwa;
  **3.** `config.php` zawiera sekrety — nie udostępniaj go nikomu.
* przycisk **„Przejdź do panelu”** → `rtrim(SITE_URL,'/').'/serwis.php'`
  (albo `serwis.php`, gdy `SITE_URL` pusty).

Stopka instalatora: **„Instalator v{wersja}”** + „Po instalacji usuń plik install.php”.
Nagłówek HTML: `<title>Instalator — RoweryExpert</title>`,
`<meta name="robots" content="noindex, nofollow">`, favicon, font Barlow z Google Fonts;
własny inline CSS: tło `#101216`, karta `#1b1e25`, akcent `#ffdd00`, błędy `#e2001a`,
sukces `#2ea043`.

---

## 3. `partials/` — opis każdego fragmentu

Wszystkie pliki zaczynają się od `<?php defined('SERWIS_PANEL') or exit; ?>`.

### 3.1 `head.php` — początek dokumentu

* `<!DOCTYPE html>`, `<html lang="pl">`, `<head>`:
  * `<meta charset="UTF-8">`,
  * `<meta name="viewport" content="width=device-width, initial-scale=1.0">`,
  * `<title>RoweryExpert - Panel Serwisowy</title>`,
  * `<link rel="icon" type="image/png" href="favicon.png">`,
  * `preconnect` do `fonts.googleapis.com` oraz `fonts.gstatic.com` + font **Barlow**
    (wagi 400;500;600;700),
  * **QRious 4.0.2** z `cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js` —
    komentarz wyjaśnia celowy brak atrybutu `integrity`, żeby uniknąć blokowania przez
    przeglądarkę,
  * `window.APP_CFG = { mapsUrl: dane_instancji()['maps_url'] }`
    (`json_encode` z `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`) — „dane
    instancji dla JS (m.in. link do wizytówki Google dla QR na wydruku)”,
  * `<link rel="stylesheet" href="assets/css/panel.css?v=<?= wersja_aplikacji() ?>">`
    (cache-busting wersją aplikacji).
* Otwarcie `<body class="dark-theme"`; dodatkowy atrybut
  `data-powitanie="1"` pojawia się, gdy `$authenticated && isset($_GET['powitanie'])`.

### 3.2 `login.php` — ekran logowania

Renderowany wyłącznie gdy `!$authenticated` (`<?php if (!$authenticated): ?>`), a
następnie komentarz `<!-- MAIN APP CONTAINER -->`. Struktura:

```html
<div class="login-screen">
  <div class="card login-card">
    <div class="logo-section">
      <img class="logo-img" src="logo.png" alt="RoweryExpert">
      <div class="logo-text"><h1>RoweryExpert</h1><p>Panel Serwisowy</p></div>
    </div>
    <h2>Zaloguj się</h2>
    <p class="login-error">…</p>            <!-- tylko przy błędzie -->
    <form method="post" action="$_SERVER['PHP_SELF']">
      <input type="hidden" name="action" value="login">
      <label for="login-user">Login</label>
      <input type="text" id="login-user" name="login"
             placeholder="np. marek" required autofocus autocomplete="username">
      <label for="login-password">Hasło</label>
      <input type="password" id="login-password" name="password"
             placeholder="******" required autocomplete="current-password">
      <button type="submit" class="btn btn-primary">Zaloguj</button>
    </form>
  </div>
</div>
```

**Uwaga („zapamiętaj”)**: w kodzie **nie ma** checkboxa „Zapamiętaj mnie” ani żadnej
opcji „remember me”. Jedyne pola to `login` i `password`. Trwałość sesji wynika z
mechanizmu sesji w bazie: cookie `AUTH_COOKIE` ustawiane w `auth_login()` na
`SESSION_TTL = 14 * 86400` (14 dni, sesja ślizgająca — odnawiana, gdy pozostała połowa
czasu minęła). Komentarz nad ekranem („hasło podawane raz dziennie”) jest pozostałością
starszej wersji; obecny system to konta + sesje w tabeli `sesje`. Komentarz w kodzie
informuje też, że panel nie wysyła maili — hasło resetuje administrator.

### 3.3 `panel.php` — główny układ panelu

Zawijany w `<div id="app-container">`, z atrybutem `hidden`, gdy użytkownik
niezalogowany; wewnątrz `<div class="container">`.

**a) Baner aktualizacji** `#update-banner` (`hidden`, style inline na żółtym tle):
`#update-banner-text` (domyślnie „Sprawdzanie aktualizacji…”), `#update-check-btn`
(„Sprawdź aktualizacje”, `btn btn-secondary`) i `#update-now-btn` („Zaktualizuj teraz”,
`btn btn-primary`, `hidden`). Komentarz: admin widzi przycisk, pracownik — samo info.

**b) `<header>`**
* `.logo-section`: `logo.png` + `<h1>RoweryExpert</h1><p>Panel Serwisowy</p>`.
* `.header-actions` (kolejność w kodzie):
  * `#open-calendar-btn` — `.btn-icon`, title „Kalendarz terminów” (ikona kalendarza),
  * `#theme-toggle-btn` — „Zmień motyw”, zawiera dwa SVG: `#theme-icon-sun`
    (domyślnie `display:none`) i `#theme-icon-moon`,
  * `#palette-btn` — „Kolor akcentu” (ikona palety),
  * `.accent-picker#accent-picker` — pięć przycisków `.accent-swatch`
    z `data-accent` i `style="--sw: …"`:
    `zolty` „Żółty Media Expert (domyślny)” `#ffdd00`, `zielony` `#4ade80`,
    `czerwony` `#f87171`, `niebieski` `#60a5fa`, `pomaranczowy` `#fb923c`,
  * `#open-settings-btn` — „Ustawienia” (ikona zębatki),
  * `<span class="header-user" id="current-user" role="button" tabindex="0"
    data-rola="…" title="Kliknij, aby otworzyć ustawienia">login</span>` — nazwa
    zalogowanego konta; klik otwiera ustawienia (komentarz 3.7: trybik przeniesiony obok),
  * `#logout-btn` — „Wyloguj się (login)”.

**c) `.dashboard-grid`** (CSS: `grid-template-columns: 460px 1fr`) — dwie karty:

**Karta lewa — „Przyjmij nowy rower”** (`.card`, tytuł z ikoną + `h2.card-title`),
formularz **`#service-form`** z polami:
* `#bike-name` — „Nazwa Roweru”, `type=text`, `required`,
  placeholder `np. Kross Hexagon 5.0, Giant Talon 1`,
* `#date-in` — „Data przyjęcia”, `type=date`, `required`,
* `#date-planned-group` / `#date-planned` — „Planowany odbiór (kalendarz)”, `type=date`,
  `required` (cała grupa ukrywana przez moduł Kalendarz),
* `#customer-phone` — „Telefon Klienta”, `type=tel`, `required`,
  placeholder `np. 500 600 700 lub 500600700`,
* `#fault-description` — „Opis usterki / zakres naprawy”, `<textarea>`
  (placeholder: „Opisz usterkę zgłoszoną przez klienta oraz zakres prac serwisowych…”)
  + kontener **`#service-checkbox-list`** (checkboxy katalogu usług),
* `#photo-upload-group` — „Zdjęcia (opcjonalnie, maks. 10 MB za zdjęcie, łącznie do
  100 MB)”: `.photo-sources` z dwoma etykietami `.photo-source-btn`
  („Wybierz z galerii” → `#photos-input`, „Zrób zdjęcie aparatem” → `#camera-input`
  z `capture="environment"`, oba `accept="image/*" multiple class="file-input-hidden"`)
  + podglądy `#photo-previews`,
* `.button-group`: `#save-print-calendar-btn` (`btn btn-primary`, etykieta
  `#save-btn-label` = „Zapisz, Drukuj i Dodaj do Kalendarza”) oraz
  `#save-only-btn` (`btn btn-secondary`, „Zapis do bazy”).

**Karta prawa — „Historia i statusy rowerów”**:
* `.history-header` z `.card-title` + `.search-box`: `#search-input`
  (placeholder „Szukaj roweru, telefonu, numeru…”) oraz `.scan-btn#scan-qr-btn`
  („Zeskanuj kod QR numeru serwisowego”),
* **kafle podsumowań** `.dash-grid#dash-grid` — każdy `.dash-tile` jest równocześnie
  skrótem do filtry (`data-filter`):
  | kafelek | `data-filter` | licznik | etykieta |
  |---|---|---|---|
  | „W serwisie” | `in_progress` | `#dash-in-progress` | W serwisie |
  | `.tile-ready` | `completed` | `#dash-completed` | Gotowe do odbioru |
  | `.tile-overdue` | `overdue` | `#dash-overdue` | Po terminie |
  | `.tile-today` | `today` | `#dash-today` | Odbiory dziś |
  | `.tile-tomorrow` | `tomorrow` | `#dash-tomorrow` | Odbiory jutro |
  (stany liczników domyślnie `–`),
* `.list-controls` — `.filters` z przyciskami `.filter-btn`:
  `all` „Wszystkie” (`active`), `mine` „Moje”, `picked_up` „Odebrane”,
  `trash` „Kosz” z licznikiem `.filter-count.count-neutral#count-trash` (`hidden`);
* `.sort-box` — „Sortuj” + `#sort-select` (`.sort-select`) z opcjami:
  `planned_asc` „Termin odbioru (najbliższy)”, `planned_desc` „… (odległy)”,
  `dateIn_desc` „Przyjęcia: najnowsze”, `dateIn_asc` „Przyjęcia: najstarsze”,
  `name` „Nazwa roweru A–Z”, `status` „Wg statusu”, `user` „Wg użytkownika (kto
  założył)”, `issuer` „Wg wydającego”,
* `.services-list#services-list-container` — „Items rendered dynamically from JS”.

**d) `<footer class="app-footer">`**: „© 2026 RoweryExpert. Wszystkie prawa
zastrzeżone.” oraz druga linia z `Wersja <strong><?= wersja_aplikacji() ?></strong>`
i linkiem `instrukcja.html` (`.footer-link` „Instrukcja”).

### 3.4 `modaly.php` — modale ogólne

Plik zawiera **siedem** nakładek (`.modal-overlay`), każda z `.modal-card` /
własnym panelem:

1. **`#confirm-modal`** — „MODAL POTWIERDZENIA (zamiast natywnego okna przeglądarki)”.
   Elementy: `#confirm-modal-title` (domyślnie „Potwierdzenie”, ikona ostrzeżenia w
   kolorze `var(--danger)`), `#confirm-modal-close`, `#confirm-modal-message`,
   `#confirm-modal-cancel` („Anuluj”, `btn btn-secondary`), `#confirm-modal-ok`
   (domyślny tekst „Usuń”, `btn btn-danger`). Służy jako wspólny dialog TAK/NIE —
   m.in. potwierdzanie kasowania z kosza („Usuń trwale”) i inne akcje destrukcyjne.
   **Osobnego „modala statusu” nie ma** — status zmienia się kliknięciem odznaki
   statusu na karcie zgłoszenia (logika w JS).
2. **`#welcome-modal`** — „POWITANIE PO ZALOGOWANIU — ile odbiorów dziś / jutro”.
   Nagłówek „Podsumowanie dnia” + `#welcome-modal-close`; tekst „Rowery zaplanowane
   do odbioru:”; `.welcome-grid` z `.welcome-stat`: `#welcome-today` („na dziś”) i
   `#welcome-tomorrow` („na jutro”); `#welcome-overdue` (`hidden`, czerwony, waga 600)
   — ostrzeżenie „Po terminie”; przycisk `#welcome-modal-ok` („OK”). Otwierany po
   `?powitanie=1` / `data-powitanie="1"`, gdy włączony moduł `powitanie`.
3. **`#edit-modal`** — „MODAL EDYCJI ZGŁOSZENIA” (`max-width: 560px`),
   `<form id="edit-form">` z polami: `#edit-bike-name` („Nazwa roweru”, `required`),
   `.form-row` z `#edit-date-in` („Data przyjęcia”) i `#edit-date-planned-group`
   / `#edit-date-planned` („Planowany odbiór”), `#edit-customer-phone` („Telefon
   klienta”, `type=tel`), `#edit-fault` („Opis usterki / zakres naprawy”,
   textarea), `#edit-service-notes` („Notatki”, textarea, placeholder
   „np. Wymiana dętki, regulacja przerzutek, smarowanie łańcucha…”); przyciski
   `Zapisz zmiany` (submit) i `#edit-modal-cancel` („Anuluj”).
4. **`#phone-success-modal`** — „MONIT PO ZAPISIE Z TELEFONU (caly ekran)”: karta
   `.phone-success-card` z `.phone-success-icon` (SVG skrzynki narzędziowej),
   `<h3>Zgłoszenie zapisane</h3>`, `#phone-success-msg`, przycisk
   `#phone-success-ok` („OK, rozumiem”). Treść generuje `showPhoneSuccess(photoCount)`
   w `druk.js`: liczba zdjęć (odmiana: zdjęcie/zdjęcia/zdjęć) + „Zgłoszenie jest
   zamazane na liście do czasu **potwierdzenia na komputerze**”, z ogonem zależnym od
   modułów („— tam uruchomi się też kalendarz i wydruk potwierdzenia dla klienta.”).
5. **`#scan-modal`** — „MODAL SKANERA QR (aparat)”: `.scan-panel` z `.scan-header`
   („Skanuj kod QR” + `#scan-modal-close`), `.scan-video-wrap` z `<video id="scan-video"
   playsinline muted autoplay>` i `.scan-frame`, `.scan-hint#scan-hint`
   („Skieruj aparat na kod QR z numerem serwisowym (naklejka na rowerze).”) oraz
   `#scan-cancel-btn` („Anuluj”).
6. **`#calendar-modal`** — „MODAL KALENDARZA TERMINÓW (widok miesięczny)”:
   `.calendar-panel` z `.calendar-header` (`#cal-prev-btn` ‹, `#cal-title`,
   `#cal-next-btn` ›, `#calendar-modal-close`), `.calendar-weekdays`
   (Pon Wt Śr Czw Pt Sob Nd), `.calendar-grid#cal-grid` oraz `.calendar-legend`
   z punktami: `.dot-progress` „W serwisie (do wykonania)”, `.dot-ready`
   „Gotowe do odbioru”, `.dot-overdue` „Po terminie”, `.dot-done` „Odebrane”.
7. **`#update-modal`** — „MODAL AKTUALIZACJI (v2): potwierdzenie + postęp”:
   `#update-modal-body` („Dostępna jest nowsza wersja panelu. Przed aktualizacją
   zostanie utworzona kopia zapasowa plików.” + `#update-modal-versions`),
   `#update-modal-progress` (`hidden`) z `#update-progress-text` („Pobieranie…”)
   i `#update-progress-bar`; przyciski `#update-modal-cancel` („Później”) oraz
   `#update-modal-ok` („Zaktualizuj”).

### 3.5 `modal-karta.php` — karta (podgląd) zgłoszenia

Pojedynczy modal **`#detail-modal`** — komentarz: „MODAL PODGLĄDU ZGŁOSZENIA (bez
edycji, z wydaniem roweru)”, `max-width: 560px`.

* Nagłówek: „Podgląd zgłoszenia” + `.service-no#detail-service-no`
  (numer serwisowy, np. `RO-2026-0042`) + `#detail-modal-close`.
* `.detail-grid` z parami `d-label` / `d-val`:
  | etykieta | ID pola | uwagi |
  |---|---|---|
  | Rower: | `#detail-bike-name` | |
  | Status: | `#detail-status` | zmiana = kliknięcie odznaki |
  | Przyjęto: | `#detail-date-in` | |
  | Termin: (label `#detail-date-planned-label`) | `#detail-date-planned` | |
  | Telefon: | `#detail-phone` | klikalny w JS |
  | Opis usterki: | `#detail-fault` | |
  | Wykonane czynności: (label `#detail-done-label`) | `#detail-done-list` | checkboxy do odhaczania |
  | Notatki: (label `#detail-notes-label`) | `#detail-notes` | `<textarea class="d-val detail-notes-input" rows="3">`, placeholder „Wpisz swoje uwagi do zgłoszenia…” — autozapis |
  | Założył: (label `#detail-created-label`) | `#detail-created-by` | |
  | Wydanie: (label `#detail-issued-label`) | `#detail-confirmed-by` | |
* `.pending-detail#detail-pending` (`hidden`) — ikona skrzynki + `#detail-pending-text`:
  informacja, że zgłoszenie utworzone na telefonie wymaga potwierdzenia na PC
  (border-left w kolorze `--locked` `#c8f751`).
* `.button-group`: **`#detail-issue-btn` „Wydaj rower”** (`btn btn-primary`) oraz
  `#detail-cancel-btn` „Zamknij” (`btn btn-secondary`).

### 3.6 `modal-ustawienia.php` — ustawienia

Z pliku: `$isAdmin = (($currentUser['rola'] ?? '') === 'admin');` — zakładek adminowskich
nie ma w HTML dla pracowników.

**a) `#settings-modal`** (`max-width: 560px`), nagłówek „Ustawienia” +
`#close-settings-btn`.

Zakładki `.settings-tabs`:
* **dla wszystkich**: `#tab-btn-general` („Ogólne”, `active`, `data-tab="general"`),
* **tylko admin** (`<?php if ($isAdmin): ?>`): `#tab-btn-serwis` („Dane serwisu”),
  `#tab-btn-uslugi` („Dodaj usługi”), `#tab-btn-moduly` („Moduły”),
  `#tab-btn-users` („Użytkownicy”).

**b) Zakładka „Ogólne”** `#tab-content-general`:
* **tylko admin**: `.form-group#photos-stats-group` „Zdjęcia rowerów w bazie” —
  `.stats-box#stats-box` z `#stats-photos` („Liczba zdjęć”) i `#stats-size`
  („Zajęte miejsce”), `#stats-warn` (`hidden`, czerwone ostrzeżenie przy ≥80% limitu)
  oraz `#refresh-stats-btn` „Odśwież statystyki”; pod spodem `<hr>`;
* dla wszystkich: **„Zmiana hasła do konta”** z info „Zalogowano jako **login**
  (administrator/pracownik). Zapomniałeś hasła? Poproś administratora o reset.”,
  pola `#current-password` („Aktualne hasło”), `#new-password` („Nowe hasło (min.
  6 znaków)”), `#confirm-password` („Powtórz nowe hasło”), przycisk
  `#save-password-btn` „Zmień hasło”, podpowiedź `#password-hint`.

**c) Zakładka „Dane serwisu”** `#tab-content-serwis` (admin) — edycja danych po
instalacji, bez przeinstalowywania; tekst: trafiają na potwierdzenie zlecenia (wydruk
A4) i do QR „Oceń nas”; nazwa RoweryExpert stała. Pola:
`#inst-adres` („Ulica”, maxlength 120), `#inst-miasto` („Kod pocztowy i miasto”,
maxlength 120), `#inst-telefon` („Telefon serwisu (na wydruku)”, maxlength 32),
`#inst-maps` („Link do wizytówki Google (źródło QR «Oceń nas»)”, `type=url`,
maxlength 255), `#inst-site` („Adres URL panelu (opcjonalnie)”, `type=url`,
maxlength 255); przycisk `#save-inst-btn` „Zapisz dane serwisu” + `#inst-hint`.

**d) Zakładka „Dodaj usługi”** `#tab-content-uslugi` (admin) — opis: dodane usługi
pojawiają się jako checkboxy w polu „Opis usterki”, zaznaczenia trafiają do zgłoszenia,
Kalendarza Google i wydruku. Elementy: `label[for=new-service-input]` „Nowa usługa”,
`.service-add-row` z `#new-service-input` (placeholder `np. Wymiana dętki`) i
`#add-service-btn` „Dodaj”; lista `ul.service-list#service-list`.

**e) Zakładka „Moduły”** `#tab-content-moduly` (admin) — opis: wyłączenie ukrywa
części panelu na PC i telefonie, a funkcje blokują się też po stronie serwera; zmiana
zapisuje się natychmiast. `.mod-list` z `.mod-row` i `input.mod-toggle[data-mod=…]` —
**10 przełączników**:

| `data-mod` | nazwa | opis w UI |
|---|---|---|
| `kalendarz` | Kalendarz | widok kalendarza, terminy odbioru, kafle „Odbiory” i linki do Kalendarza Google |
| `zdjecia` | Zdjęcia | wgrywanie i podgląd zdjęć z telefonu oraz miniatury na liście |
| `skaner` | Skaner QR | przycisk skanera kodów QR przy wyszukiwarce |
| `uslugi` | Katalog usług | checkboxy usług w formularzu i zakładka „Dodaj usługi” |
| `druk` | Drukowanie | wydruk potwierdzenia przyjęcia dla klienta |
| `kosz` | Kosz | filtr Kosz, przenoszenie do kosza i przywracanie zgłoszeń |
| `kolorystyka` | Kolorystyka | paleta koloru akcentu; po wyłączeniu logo i faviconka wracają do żółtego |
| `powitanie` | Powitanie | okno „Podsumowanie dnia” po zalogowaniu (wymaga modułu Kalendarza) |
| `karta_wydania` | Karta wydania | automatyczny druk Karty Wydania Roweru przy wydaniu; przycisk „Wydaj rower” zostaje |
| `wykonane` | Wykonane czynności | checkboxy w karcie zgłoszenia i ☑ na wydruku Karty Wydania |

Pod listą: `#moduly-hint`.

**f) Zakładka „Użytkownicy”** `#tab-content-users` (admin, „3.1”):
* opis: konta pracowników, każdy loguje się swoim loginem/hasłem; reset hasła wylogowuje
  konto na wszystkich urządzeniach,
* „Nowe konto”: `.form-row` z `#new-user-login` („Login (np. imię)”, placeholder
  `np. marek`) i `#new-user-rola` („Rola”: `pracownik` / `administrator`),
  `#new-user-pass` („Hasło startowe (min. 6 znaków)”), `#add-user-btn` „Dodaj konto”,
  `#users-hint`,
* kontener `#users-list` z atrybutem `data-me="{id zalogowanego}"` — lista kont.

**g) `#user-modal`** — „MODAL KONTA UŻYTKOWNIKA (3.3)”, **tylko dla admina**
(`<?php if ($isAdmin): ?>`, `max-width: 520px`):
* nagłówek: `#user-modal-login` + `#user-modal-badge`, `#user-modal-close`,
* `.detail-grid`: **Status:** `#user-modal-status`, **Ostatnie logowanie:**
  `#user-modal-last`, **Konto utworzone:** `#user-modal-created`, **Hasło:**
  `#user-modal-passflag`, **Założył zgłoszeń:** `#user-modal-zgloszenia`,
  **Wydanych rowerów:** `#user-modal-wydane`, **Rola:** `#user-modal-rola`
  (select `pracownik` / `administrator`),
* „Reset hasła (min. 6 znaków)”: `#user-modal-pass-input` + `#user-modal-pass-save`
  „Zapisz hasło”, `#user-modal-hint`,
* `.button-group`: `#user-modal-toggle` „Wyłącz konto”, `#user-modal-delete`
  „Usuń konto” (`btn btn-danger`), `#user-modal-cancel` „Zamknij”.

### 3.7 `modal-zdjecia.php` — zdjęcia

1. **`#photos-modal`** (`max-width: 720px`) — nagłówek „Zdjęcia –
   `<span id="photos-modal-title">`” + `#close-photos-btn`.
   * `.form-group#photos-modal-input-group` „Dodaj zdjęcia do zgłoszenia” z
     `.photo-sources`: etykiety „Wybierz z galerii” (`for="photos-modal-input"`) i
     „Zrób zdjęcie aparatem” (`for="camera-modal-input"`); ukryte inputy
     `#photos-modal-input` oraz `#camera-modal-input` (`accept="image/*" multiple`,
     drugi z `capture="environment"`, klasa `file-input-hidden`),
   * `.photos-grid#photos-grid` — miniatury (renderowane w JS).
2. **`#lightbox`** (`.lightbox`, `title="Kliknij, aby zamknąć"`) z `<img id="lightbox-img"
   alt="Podgląd zdjęcia">` — pełnoekranowy podgląd.
3. **`.toast-container#toast-container`** — kontener komunikatów toast.
4. **`#upload-overlay`** (`.upload-overlay`) — animacja wgrywania: rozbudowany SVG
   `.upload-cyclist` (koła `.wheel`, linie szybkości `.speed-lines`, rama, kierowca
   `.rider`) + `.upload-label` „Zapisywanie”.

### 3.8 `wydruk.php` — szablon wydruku A4 (poziomo, 2 kolumny)

Kontener **`<div id="print-receipt">`** (na ekranie `display:none`, włączany w
`@media print`). Dwie kolumny `.receipt-column`:

**a) Lewa kolumna `.receipt-column-client` (egzemplarz dla klienta)**
* `.receipt-header` → `<h1>RoweryExpert</h1>`,
* `.receipt-title#print-title-client` — domyślnie **„Potwierdzenie Przyjęcia Roweru”**
  (przy trybie `wydanie` JS podmienia na **„Karta Wydania Roweru”**),
* tabela `.receipt-details` z wierszami `.receipt-row` (`.receipt-label` / `.receipt-value`):
  * „Rower:” → `#print-bike-name` (`font-weight:bold`),
  * „Data przyjęcia:” → `#print-date-in`,
  * „Telefon klienta:” → `#print-customer-phone`,
  * „Numer serwisowy:” → `#print-service-no` (`font-weight:bold`),
  * „Opis usterki / zakres naprawy:” (colspan=2) → `.receipt-value-desc#print-fault-description`,
  * **„Wykonane czynności:”** — wiersz `#print-done-row-client` + komórka
    `#print-done-cell-client` z `#print-done-client` (sekcja **znika**, gdy nic nie
    zaznaczono),
* `.receipt-signatures` z dwoma `.signature-box`: `.signature-line` (kreska) +
  `.signature-label`: **„Podpis klienta”** oraz **„Podpis i pieczątka serwisu”**,
* `.receipt-footer`:
  * `.receipt-address`: `RoweryExpert <?= dane_instancji()['adres'] ?>
    <?= dane_instancji()['miasto'] ?> tel. <?= dane_instancji()['telefon'] ?>`,
  * `.receipt-qr-container`: `<canvas id="qr-code-canvas">` (QR generowany lokalnie
    biblioteką QRious), zapasowy `<img id="qr-code-img" style="display:none">`
    (generowany przez darmowe API online, gdy CDN z QRious zablokowany) oraz
    `.receipt-qr-desc` **„Oceń nas w Google”**.

**b) Prawa kolumna `.receipt-column-service` (egzemplarz serwisu)**
* `.receipt-header` → `<h1>RoweryExpert</h1>`,
* `.receipt-title#print-title-service` — domyślnie **„Zlecenie Serwisowe - Egzemplarz
  Serwisu”**, przy trybie `wydanie`: **„Karta Wydania Roweru - Egzemplarz Serwisu”**,
* tabela `.receipt-details` (te same etykiety, osobne ID-y z sufiksem `-service`):
  `#print-bike-name-service`, `#print-date-in-service`, `#print-customer-phone-service`,
  `#print-service-no-service`, `#print-fault-description-service`,
* `.service-notes-title#print-done-title-service` „Wykonane czynności:” +
  `.receipt-value-desc#print-done-service` (pusta = sekcja znika),
* blok `#print-notes-block`: `.service-notes-title` „Notatki:” +
  `#print-notes-block` wewnątrz `.receipt-value-desc#print-service-notes`
  (domyślnie „—”; **na wydruku pojawia się tylko po uzupełnieniu**),
* `#print-notes-spacer` — wypełniacz wysokości, gdy notatek nie ma (QR trzyma się dołu),
* dolny QR: `<canvas id="qr-service-canvas">` — **etykieta QR z numerem serwisowym**
  do naklejenia na rower.

**Zawartość kodów QR (logika `assets/js/druk.js`, `triggerPrint(item, tryb)`):**
* **QR „Oceń nas w Google”** (lewa kolumna): `value = window.APP_CFG.mapsUrl`
  (czyli `GOOGLE_MAPS_URL` z configu / „Dane serwisu”), QRious `size: 150`,
  `level: 'H'`, czarny `#000000` na białym `#ffffff`; fallback
  `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=…` w `#qr-code-img`.
* **QR numeru serwisowego** (prawa kolumna, `#qr-service-canvas`): `value = item.serviceNo`
  (np. `RO-2026-0042`), te same parametry; canvas chowany, gdy brak numeru.
* Wywołanie `window.print()` następuje 150 ms po wyrenderowaniu QR.
* Tryby: `triggerPrint(item)` = **przyjęcie**, `triggerPrint(item, 'wydanie')` = **karta
  wydania** (uruchamiana z `karta.js`, gdy włączone moduły `druk` i `karta_wydania`
  i nie jest to telefon). Różnica: tytuły obu kolumn + sekcja „Wykonane czynności”
  (`getDoneServices(item)`, pozycje prefixed **„☑ ”**), gdy włączony moduł `wykonane`.
* Notatki: blok `#print-notes-block` dostaje `display:flex` tylko gdy `item.serviceNotes`
  niepuste, inaczej `display:none` i widoczny jest `#print-notes-spacer`.

---

## 4. `assets/css/panel.css` — struktura arkusza (przegląd, nie wykaz reguł)

Plik ma 2637 linii (76 KB) i jest jednym, „płaskim” arkuszem (wcięcia 8-sp.; bez
preprocesora). Kolejność bloków odpowiada kolejności elementów w HTML.

### 4.1 Tokeny i motywy

* **`:root` (linie 1–42)** — dwie palety:
  * *Palette Dark — czerń Media Expert*: `--bg-dark: #0b0b0b`, `--card-dark: #171717`,
    `--text-dark-primary: #f7f7f7`, `--text-dark-secondary: #9d9d9d`,
    `--border-dark: #2c2c2c`,
  * *Palette Light — biel sklepu*: `--bg-light: #f4f4f4`, `--card-light: #ffffff`,
    `--text-light-primary: #0d0d0d`, `--text-light-secondary: #575757`,
    `--border-light: #e4e4e4`,
  * *Shared*: `--primary: #ffdd00` („Media Expert Yellow”), `--primary-hover: #ecc900`,
    `--primary-light`, `--primary-ring`, `--primary-soft`, `--primary-text`,
    `--success: #10b981`, `--danger: #e2001a`, `--calendar-color: #3587ea`,
    `--locked: #c8f751` („hi-viz z kamizelki rowerowej: zgłoszenie zablokowane”),
    `--card-lighter`, promienie `--radius-lg/md/sm` (10/8/6 px), `--transition`,
  * **domyślnie ciemny motyw** (`--bg`/`--card`/`--text-*`/`--border` wskazują na wariant
    Dark) — `body` ma też `color-scheme: dark`.
* **`body.light-theme` (44–53)** — podmiana `--bg`, `--card`, `--text-primary`,
  `--text-secondary`, `--border`, `--card-lighter`, `--primary-text: #8a7300`
  („ciemne złoto — czytelne na białym”) i `color-scheme: light`. Motyw przełączany
  przyciskiem `#theme-toggle-btn`; startowo `<body class="dark-theme">`.

### 4.2 Warianty koloru akcentu

Blok „WARIANTY KOLORU AKCENTU (przycisk palety w nagłówku)”: **domyślnie żółty Media
Expert (bez klasy)**; inny wybór = klasa `body.accent-nazwa` nadpisująca tokeny
`--primary`, `--primary-hover`, `--primary-text`, `--primary-light`, `--primary-ring`,
`--primary-soft`:

| klasa | `--primary` | wariant dla `light-theme` (`--primary-text`) |
|---|---|---|
| `accent-zielony` | `#4ade80` | `#15803d` |
| `accent-czerwony` | `#f87171` | `#b91c1c` |
| `accent-niebieski` | `#60a5fa` | `#1d4ed8` |
| `accent-pomaranczowy` | `#fb923c` | `#c2410c` |

Dodatkowo style `.accent-picker` (panel absolutny pod `#palette-btn`, `z-index: 60`,
`.open` = `display:flex`), `.accent-swatch` (26×26 px, `background: var(--sw)`,
`:hover` `scale(1.15)`, `.active` z obwódką).

### 4.3 Główne bloki tematyczne (wg komentarzy sekcji)

| zakres linii | sekcja |
|---|---|
| 167–240 | Container & Layout (`.container` max-width 1400 px), `header`, `.logo-section`, `.logo-img` (54 px; `body.light-theme .logo-img { filter: brightness(0) }` — czarne logo na jasnym tle), `.header-actions`, `.btn-icon` |
| 241–253 | Grid System — `.dashboard-grid { grid-template-columns: 460px 1fr; gap: 2rem }` + `@media (max-width: 1024px)` → jedna kolumna |
| 255–376 | Cards, Forms, „Mobile-specific fixes”, fixy iOS (overscroll, safe areas), style pól dat |
| 377–465 | Buttons (`.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`) |
| 466–503 | History & List Section |
| 504–598 | **Dashboard podsumowań** — `.dash-grid` (`repeat(auto-fit, minmax(135px,1fr))`), `.dash-tile`, `.active` (żółte tło), `.tile-ready` (zieleń), `.tile-overdue` (czerwień), `.tile-today` (`--primary-text`), `.tile-tomorrow` (błękit) |
| 560–598 | Sortowanie listy (`.sort-box`, `.sort-select`) |
| 599–618 | Liczniki na przyciskach filtrów — `.filter-count` (czerwony) i `.filter-count.count-neutral` (żółty) |
| 619–632 | Numer serwisowy `.service-no` (dashed pill) |
| 633–651 | `.overdue-badge`, `.service-item-card.is-deleted` (opacity .7) |
| 652–662 | `.form-row` (dwa pola w rzędzie) |
| 663–748 | Skaner QR — `.scan-btn`, `.scan-panel`, `.scan-video-wrap`, `.scan-frame` |
| 749–792 | Karta podglądu (`.detail-grid`, `.d-label`, `.d-val`), notatki `.detail-notes-input` |
| 793–1053 | Kalendarz terminów (`.calendar-panel`, `.cal-cell`, `.cal-chip`, `.dot-*`, dymek „więcej”), `.status-badge` |
| 1054–1306 | Service Cards List (`.service-item-card`, `.item-header`, `.item-details`, `.item-actions`, `.btn-action`), pasek przewijania, plakietki osób (3.4) |
| 1307–1392 | **ZGŁOSZENIA OCZEKUJĄCE NA POTWIERDZENIE (utworzone na mobile)** — `.pending-card`, `.pending-mask` (maska „zamazania”), kolor `--locked` |
| 1393–1442 | Footer (`.app-footer`, link do instrukcji), powitanie (`.welcome-grid`, `.welcome-stat`) |
| 1443–1558 | Modal Settings (`.modal-overlay`, `.modal-card`, `.modal-header`, `.modal-close`), Toast Notifications (`.toast-container`) |
| 1559–1743 | **PHOTOS**: upload previews, miniatury, `.photos-grid`, `.lightbox`, `input[type=file]` |
| 1744–1780 | **LOGIN SCREEN (hasło raz dziennie)** — `.login-screen` (fixed, `inset:0`, `z-index: 300`), `.login-card` (max-width 400 px), `.login-error` |
| 1781–1833 | **UPLOAD OVERLAY** — reguła `[hidden] { display: none !important; }`, `.file-input-hidden` (sr-only), `.photo-sources`, `.photo-source-btn` |
| 1834–1935 | Zakładki ustawień (`.settings-tabs`, `.settings-tab`, `.settings-tab-content`), dodawanie usług, wiersz konta w „Użytkownicy” |
| 1936–1995 | Checkboxy usług w formularzu (`.service-checkbox-list`, `accent-color`) |
| 1996–2078 | Zamknięcie overlay uploadu — „rowerzysta odjeżdża za krawędź ekranu”, obracające się `.wheel`, `.speed-lines` |
| 2079–2086 | **PRINT RECEIPT TEMPLATE** — `#print-receipt { display: none }` na ekranie |

### 4.4 Responsywność i ukrywanie modułów

**Breakpointy:**

* `@media (max-width: 1024px)` (249 i 2635): `.dashboard-grid` → jedna kolumna;
  `.header-user` (nazwa konta w nagłówku) → `display: none`.
* `@media (max-width: 768px)` (2242–2378): mniejszy padding `.container`, `<header>`
  w kolumnę, `.header-actions` na całą szerokość, `.card` mniejsze, `input/textarea/select`
  `font-size: 16px` („Prevents iOS zoom”), `.history-header` w kolumnę, `.search-box`
  na całą szerokość, `.filters` z zawijaniem, `.sort-box` pod filtrami, mniejsze
  `.filter-btn`, `.calendar-panel` (`max-height: 92vh`) i `.cal-cell`/`.cal-chip`,
  `.services-list { max-height: 600px }`, `.item-header`/`.item-details` w kolumnę,
  `.btn-action` po 50% (dwa w rzędzie).
* `@media (max-width: 480px)` (2380–2400): `.container` 0.75/0.5 rem, `.logo-img`
  44 px, `.logo-text h1` 1.2 rem, `.btn-action` na całą szerokość (100%).
* `@media (prefers-reduced-motion: reduce)` (2403) — wyłączenie animacji/transycji.
* `@media print` (2411–2614) — patrz niżej.

**Tryb telefonu (`body.is-mobile`, ustawiany z JS):**

```css
body.is-mobile .dash-grid,
body.is-mobile .list-controls,
body.is-mobile .services-list { display: none; }
```

Komentarz: *„telefon służy do przyjęcia i wydania, bez przeglądania listy — ukrywamy
listę, kafle, filtry i sortowanie. Wyszukiwarka/skaner zostają: wynik otwiera kartę
zgłoszenia.”*

**Ukrywanie wyłączonych modułów** — wyłączone moduły dostają na `<body>` klasę
`off-nazwa` (blok „MODUŁY WŁĄCZONE/WYŁĄCZONE (Ustawienia → Moduły)”, 2099–2123):

```css
body.off-kalendarz #open-calendar-btn, #date-planned-group, #edit-date-planned-group,
   #detail-date-planned-label, #detail-date-planned,
   .dash-tile[data-filter="overdue"], [data-filter="today"], [data-filter="tomorrow"]
body.off-zdjecia   #photo-upload-group, #photos-modal-input-group, #photos-stats-group
body.off-skaner    #scan-qr-btn
body.off-uslugi    #service-checkbox-list, #tab-btn-uslugi
body.off-kolorystyka #palette-btn, #accent-picker
body.off-wykonane  #detail-done-label, #detail-done-list
body.off-kosz      .filter-btn[data-filter="trash"]
   → { display: none !important; }
```

Reguły ukrywają elementy statyczne; elementy renderowane przez JS warunkują się
flagami w obiekcie `MODULY` (patrz `core.js`).

W tym samym bloku: `.mod-row` (wiersz przełącznika), `.mod-row input[type=checkbox]`
(`accent-color: var(--primary)`), `.mod-list { max-height: 240px; overflow-y: auto }`
— „box o stałej wysokości ze scrolliem, żeby 10 modułów mieściło się na jednym
ekranie”, `.mod-row:has(input:checked) { border-color: var(--primary) }`.

Dalej: `.pending-detail` (info o zgłoszeniu z telefonu w karcie), `#phone-success-modal`
— `.phone-success-card` staje się **pełnoekranowy** (`.active` → `width:100%`,
`height:100%`, bez ramki/radius), `.phone-success-icon` (60×60, koło w kolorze
`--primary`).

**`@media print` (A4):**
* `@page { size: A4 landscape; margin: 10mm; }`,
* reset: biały `#ffffff`, czarny `#000000`, font Barlow/Helvetica/Arial, 9.5pt,
  bez cieni/animacji,
* ukrycie całego UI: `#app-container, .toast-container, .modal-overlay,
  .upload-overlay, .lightbox, .login-screen { display: none !important; }`,
* `#print-receipt` → `display:flex; flex-direction:row; height: calc(100vh - 16mm);
  gap: 10mm`,
* `.receipt-column` → 50% szerokości, pełna wysokość, obramowanie `2px solid #000`,
  `border-radius: 8px`, padding 8 mm,
* typografia: `.receipt-header h1` 17pt, `.receipt-title` 10.5pt (uppercase),
  `.receipt-label` 8.5pt bold (szer. 32%), `.receipt-value` 9.5pt,
  `.receipt-value-desc` 9pt z ramką i `min-height: 90px`,
* podpisy: `.receipt-signatures` „przypięte w dół, tuż nad grubą linią stopki”
  (`margin-top: auto`), `.signature-box` 45%, `.signature-line` kreska dashed 26 px,
  `.signature-label` 7.5pt uppercase,
* stopka: `.receipt-footer` z `border-top: 2px solid #000`, `.receipt-address` 7.5pt
  (60% szerokości), `.receipt-qr-container` 35%, `.receipt-qr-element` **38×38 px**,
  `.receipt-qr-desc` 6.5pt uppercase,
* `.service-notes-title` 8.5pt bold uppercase.

Na koniec pliku (po `@media print`): `.header-user` (3.0 — zalogowane konto w nagłówku,
`text-overflow: ellipsis`, max-width 170 px) oraz reguła `#open-settings-btn + .header-user`
i ukrycie `.header-user` poniżej 1024 px.

---

## 5. `instrukcja.html` — zawartość istniejącej (niekompletnej) instrukcji

Plik (456 linii, 24 KB) to samodzielna, statyczna strona HTML (`noindex,nofollow`),
własny CSS (zmienne `--bg/--card/--text/--primary: #ffdd00/--primary-text: #8a7300/…`),
font Barlow, `.wrap` max-width 860 px. Elementy architektoniczne:

* `<header>`: **„Instrukcja panelu RoweryExpert”** + podtytuł „Krótki przewodnik po
  obsłudze zgłoszeń serwisowych — krok po kroku.” + przycisk `.back-btn`
  **„← Wróć do panelu”** → `serwis.php`.
* `<nav class="toc">` — spis 15 kotwic (kolumny 2, `columns: 2`):
  `#logowanie`, `#przyjecie`, `#statusy`, `#mobile`, `#lista`, `#skaner`, `#numer`,
  `#karta`, `#edycja`, `#kalendarz`, `#kalendarz-google`, `#wydruk`, `#kosz`,
  `#ustawienia`, `#moduly`.
* 15 sekcji `section` z `h2` z okrągłym numeratorem `.num` (`.tag` wariantów:
  `.ok`, `.warn`, `.info`, `.orange`; bloki `.hint`).
* `<footer>`: „RoweryExpert — panel serwisowy. Wróć do panelu” → `serwis.php`.
* `@media (max-width: 640px)`: TOC w jedną kolumnę, mniejsze sekcje.

### Pełny zarys treści (co już jest opisane)

1. **Logowanie (`#logowanie`)** — login np. `admin` + hasło; **sesja 14 dni**
   (albo wylogowanie ikonką); po 5 nieudanych próbach blokada na kilka minut; brak
   maili — reset przez administratora; zmiana hasła w **Ustawieniach → Ogólne** (tam
   też info o koncie, nazwa konta także w nagłówku); po zalogowaniu okno
   **„Podsumowanie dnia”** (ile rowerów na dziś/jutro, ostrzeżenie „Po terminie”,
   zamykane **OK**), widoczne tylko przy włączonym module Kalendarza.
2. **Przyjęcie roweru (`#przyjecie`)** — formularz „Przyjmij nowy rower” (lewa kolumna);
   pola: Nazwa roweru, Data przyjęcia i planowany odbiór, Telefon klienta
   (autoformatowanie, min. 9 cyfr), Opis usterki, Zdjęcia; dwa przyciski:
   **„Zapisz, Drukuj i Dodaj do Kalendarza”** (PC) i **„Zapisz tylko w bazie”** (telefon).
3. **Statusy zgłoszeń (`#statusy`)** — trzy statusy zmieniane **klikając odznakę na
   karcie**: „W serwisie”, „Gotowy”, „Odebrany”; najszybsze wydanie przyciskiem
   „Wydaj rower” w karcie podglądu.
4. **Zgłoszenia z telefonu (`#mobile`)** — zgłoszenie do czasu potwierdzenia jest
   **zamazane** na liście; po zapisie **pełnoekranowy monit** (z liczbą zdjęć, gdy
   były), zamykany „OK”; na PC przycisk **„Potwierdź”** odblokowuje zgłoszenie i
   uruchamia kalendarz + wydruk.
5. **Lista, filtry i wyszukiwanie (`#lista`)** — kafle podsumowań = skrót do filtrów
   (aktywny kafel podświetlony); przyciski filtrów: **Wszystkie, Odebrane, Kosz**
   (z licznikiem); sortowanie: termin (najbliższy/odległy), przyjęcia
   (najnowsze/najstarsze), nazwa, status — domyślnie najbliższe terminy, wybór
   zapamiętywany w przeglądarce; szukanie po nazwie, telefonie, opisie, numerze
   serwisowym i wykonanych czynnościach; **na telefonie** lista/kafle/filtry/sortowanie
   ukryte — szukanie wymaga **min. 4 znaków numeru** i pokazuje tylko jedno zgłoszenie
   (przy wielu dopasowaniach prośba o więcej cyfr), skan QR wpisuje cały numer.
6. **Skaner kodu QR (`#skaner`)** — ikona aparatu przy wyszukiwarce; panel sam wpisuje
   numer i otwiera kartę; wymagane HTTPS i zgoda na kamerę; przy zablokowanym CDN
   biblioteki — przepisanie numeru ręcznie.
7. **Numer serwisowy i naklejka QR (`#numer`)** — format **RO-ROK-ID**
   (np. `RO-2026-0042`); na wydruku (egzemplarz serwisu) kwadratowy QR z tym numerem —
   wyciąć i nakleić na ramę.
8. **Karta zgłoszenia (`#karta`)** — otwierana: klik karty na liście, wpis w kalendarzu,
   skan QR, wyszukiwarka na telefonie; zawiera numer, rower, status, daty, klikalny
   telefon, opis i **wykonane czynności** (checkboxy wyłącznie z usług zaznaczonych przy
   przyjęciu; odhaczane w chwili wykonania, autozapis; na Karcie Wydania drukują się
   jako ☑ tylko zaznaczone; po wydaniu/w koszu — tylko podgląd; sekcja zależy od modułu
   **Wykonane czynności**); aktywne pole **„Notatki”** (autozapis, ten sam tekst co w
   edycji); info o wymaganiu potwierdzenia z telefonu; na dole **„Wydaj rower”**
   (zmienia status na „Odebrany”, potem „Rower już wydany” i gaśnie); przy modułach
   **druk** + **Karta wydania** wydanie od razu drukuje Kartę Wydania Roweru.
9. **Edycja i zdjęcia (`#edycja`)** — przycisk „Edytuj” (nazwa, daty, telefon, opis,
   „Notatki”); przycisk „Zdjęcia” (podgląd, dodawanie, usuwanie); **limit łączny 100 MB**,
   zużycie w Ustawieniach → Ogólne (np. „12,4 MB / 100 MB”), ostrzeżenie czerwone przy
   **80%** i komunikat o zużyciu po wgrywaniu.
10. **Kalendarz w panelu (`#kalendarz`)** — widok miesięczny; chipy od przyjęcia do
    planowanego odbioru **każdego dnia z okresu**; kolory: żółty = w serwisie, zielony =
    gotowy, czerwona ramka = po terminie, **szary = odebrany**; nawigacja ‹ ›,
    dzisiejsza data podświetlona; klik chipu = karta zgłoszenia; **„+N więcej”** z dymkiem
    pełnej listy dnia.
11. **Kalendarz Google i druk (`#kalendarz-google`)** — przyciski na każdej karcie (PC):
    **„Kalendarz”** (dodaje termin z przypomnieniem) i **„Drukuj”** (potwierdzenie
    przyjęcia, po wydaniu — Kartę Wydania).
12. **Wydruk potwierdzenia i karty wydania (`#wydruk`)** — jedna strona **A4 poziomo**,
    dwie kolumny: **lewa — klient**: dane roweru, telefon, numer, opis, na karcie wydania
    też lista wykonanych czynności, podpis klienta i pieczątka serwisu (nad kreską z
    adresem) + QR z linkiem do oceny w Google; **prawa — serwis**: te same dane +
    „Wykonane czynności” (tylko karta wydania; znika, gdy puste lub moduł wyłączony) +
    „Notatki” (tylko po uzupełnieniu) + QR z numerem serwisowym na samym dole.
13. **Kosz (`#kosz`)** — kosz na karcie nie kasuje od razu; filtr **Kosz** z licznikiem;
    akcje: **„Przywróć”** (wraca na listę) i **„Usuń trwale”** (kasuje zgłoszenie ze
    zdjęciami, nieodwracalne).
14. **Ustawienia (`#ustawienia`)** — ikona zębatki: **Ogólne** (statystyki zdjęć +
    zmiana hasła), **Dodaj usługi** (własna lista usług), **Moduły** (przewijana lista);
    obok: przełącznik motywu (jasny/ciemny, domyślnie ciemny), wybór koloru akcentu
    (żółty Media Expert domyślnie; zielony, czerwony, niebieski, pomarańczowy — wybór
    zapamiętywany na urządzeniu), wylogowanie; razem z akcentem zmienia się kolor roweru
    w logo (także na ekranie logowania) i faviconka.
15. **Moduły (`#moduly`)** — wyłączenie ukrywa części panelu na PC i telefonie jednocześnie,
    **nie kasuje danych**, zmiana zapisuje się natychmiast, lista w przewijanym boxie
    (max-height). Opisane skutki 10 modułów: **Kalendarz** (znika przycisk, pole
    „Planowany odbiór”, kafle „Po terminie/Odbiory dziś/jutro”, sortowanie po terminie,
    linki do Kalendarza Google), **Zdjęcia** (wgrywanie, miniatury, licznik), **Skaner QR**
    (przycisk; tekstowe szukanie działa), **Katalog usług** (checkboxy + zakładka), **Drukowanie**
    (wydruk; etykieta przycisku dopasowuje się, np. „Zapisz i dodaj do kalendarza”), **Kosz**
    (filtr + kasowanie; w tym stanie zgłoszeń nie da się skasować), **Kolorystyka** (paleta;
    logo i faviconka wracają do żółtego, wybór zostaje), **Powitanie** (brak okna
    „Podsumowanie dnia”, wymaga Kalendarza), **Karta wydania** („Wydaj rower” tylko zmienia
    status, bez auto-druku; ręczny „Drukuj” nadal działa), **Wykonane czynności** (chowa
    checkboxy w karcie i ☑ na wydruku; katalog usług działa dalej). Na końcu: wyłączone
    moduły blokowane też **po stronie serwera**; rdzeń (logowanie, przyjęcie, lista,
    wyszukiwanie, wydanie) jest zawsze włączony.

### Luki wobec kodu (do uzupełnienia przy rozszerzaniu)

Na podstawie porównania z kodem, istniejąca instrukcja **nie opisuje** m.in.:
instalatora (`install.php`) i jego 6 kroków, ekranu logowania jako takiego (pól, braku
„Zapamiętaj”), zakładki **Dane serwisu** w Ustawieniach (edycja adresu/telefonu/QR po
instalacji), zakładki **Użytkownicy** i karty konta `#user-modal` (dodawanie, rola,
reset hasła, wyłączanie/usuwanie kont), banera aktualizacji i okna „Aktualizacja
panelu”, okna potwierdzenia `#confirm-modal`, modalu edycji `#edit-modal` jako takiego,
skanera QR na PC, kalendarza Google jako osobnej ścieżki (tylko wzmianka), pełnych
opcji sortowania (8 wariantów), filtra „Moje”, liczników na filtrach, toastów i
overlay uploadu, a także szczegółów wydruku (wymiarów, pozycji QR, treści QR).


---

# Część IV — Backend: `config.php`, API, bezpieczeństwo

Dokumentacja fragmentaryczna (część A) aplikacji **RoweryExpert** — panelu serwisu rowerowego (PHP 8, `declare(strict_types=1)`, MySQL/MariaDB przez PDO, front vanilla JS). Obejmuje pełny opis pliku `config.php` (1377 linii) oraz sześciu endpointów w katalogu `api/`.

---

## 1. Plik `config.php`

`config.php` jest jednocześnie: plikiem konfiguracji (sekrety, dane instancji), warstwą dostępu do bazy (własne „migracje”), modułem uwierzytelniania, biblioteką walidacji, pomocników JSON/fotografii oraz mechanizmem **automatycznych aktualizacji** z GitHuba. Zawiera sekrety (hasła) — zgodnie z komentarzem w nagłówku nie wolno go udostępniać ani wrzucać do gita.

### 1.1. Stałe (stałe konfiguracyjne)

| Stała | Wartość domyślna / przykładowa | Znaczenie |
|---|---|---|
| `APP_VERSION` | `'3.8.4'` | Numer wersji aplikacji pokazywany w stopce strony; punkt odniesienia dla `check_update()`/`do_update()` (fallback, gdy w bazie brak `installed_version`). |
| `DB_HOST` | `'localhost'` | Host serwera MySQL. |
| `DB_PORT` | `'3306'` | Port serwera MySQL. |
| `DB_NAME` | `'host91573_rower'` | Nazwa bazy danych (tworzona automatycznie przez `db()`). |
| `DB_USER` | `'host91573_rower'` | Użytkownik bazy danych. |
| `DB_PASS` | sekret | Hasło do bazy danych (sekret). |
| `SERVICE_ADDRESS` | `'ostra'` | Ulica serwisu — stopka wydruku zlecenia. Ustawiane przez `install.php`, edytowalne w panelu (klucz `service_address`). |
| `SERVICE_CITY` | `'wawa'` | Kod pocztowy i miasto — stopka wydruku (klucz `service_city`). |
| `SERVICE_PHONE` | `'344454554'` | Telefon serwisu — stopka wydruku (klucz `service_phone`). |
| `GOOGLE_MAPS_URL` | `https://maps.app.goo.gl/...` | Link do wizytówki Google; służy do kodu QR „Oceń nas” na wydruku (klucz `google_maps_url`). |
| `SITE_URL` | `''` | Opcjonalny adres URL panelu; puste = bez zmian (klucz `site_url`). |
| `UPLOAD_DIR` | `__DIR__ . '/uploads/zdjecia'` | Katalog fizyczny na wgrane zdjęcia (tworzony przez `db()` z uprawnieniami `0755`). |
| `UPLOAD_URL` | `'uploads/zdjecia'` | Ścieżka URL do zdjęć (składana w polu `url` zdjęcia). |
| `MAX_PHOTO_BYTES` | `10 * 1024 * 1024` (10 MB) | Maksymalny rozmiar jednego zdjęcia w bajtach. |
| `MAX_PHOTOS_TOTAL_BYTES` | `100 * 1024 * 1024` (100 MB) | Łączny limit wszystkich zdjęć **wszystkich** zgłoszeń (sprawdzany przed zapisem partii). |
| `MAX_PHOTOS_PER_REQUEST` | `20` | Maksymalna liczba plików w jednym wgraniu (limit serwera `max_file_uploads`). |
| `ALLOWED_PHOTO_MIME` | `image/jpeg→jpg`, `image/png→png`, `image/webp→webp`, `image/gif→gif` | Biała lista formatów zdjęć wraz z docelowym rozszerzeniem pliku. |
| `STATUSES` | `['in_progress', 'completed', 'picked_up']` | Dozwolone statusy zgłoszenia: w trakcie / zakończone / odebrane. |
| `APP_PASSWORD` | `'mamdostep'` | Hasło konta `admin` tworzonego przy pierwszym uruchomieniu (seed hasła aplikacji — sekret). |
| `AUTH_COOKIE` | `'re_sess_1ad4bdb4d2bd8b04'` | **Własna** nazwa cookie sesji (path `/`) — fork działa równolegle ze starą wersją bez kolidowania sesjami. |
| `SESSION_TTL` | `14 * 86400` (14 dni) | Czas życia sesji w sekundach; sesja jest **ślizgająca** (przedłużana). |
| `LOGIN_MAX_ATTEMPTS` | `5` | Liczba nieudanych prób logowania blokująca klucz. |
| `LOGIN_ATTEMPT_WINDOW` | `15 * 60` (15 min) | Okno (w sekundach), w którym liczą się nieudane próby. |

### 1.2. Schemat bazy danych (auto-tworzenie w `db()`)

`db()` wykonuje `CREATE DATABASE IF NOT EXISTS` (utf8mb4/utf8mb4_unicode_ci), `USE`, a następnie komendy `CREATE TABLE IF NOT EXISTS` — pełny, aktualny schemat:

**`zgloszenia`** — zgłoszenia serwisowe (rowery przyjęte do serwisu):

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator zgłoszenia. |
| `bike_name` | `VARCHAR(255) NOT NULL` | Nazwa/opis roweru. |
| `date_in` | `DATE NOT NULL` | Data przyjęcia do serwisu. |
| `date_planned` | `DATE` (domyślnie `NOT NULL`, migracja zmienia na `DEFAULT NULL`) | Planowana data odbioru; `NULL` gdy wyłączony moduł „kalendarz”. |
| `customer_phone` | `VARCHAR(32) NOT NULL` | Telefon klienta. |
| `fault_description` | `TEXT NOT NULL` | Opis usterki (linie `- ` z opisu bywają traktowane jako lista usług). |
| `service_notes` | `TEXT NULL` | Notatki serwisowe („wykonane czynności” z karty; max 6000 znaków walidowane aplikacyjnie). |
| `services_done` | `TEXT NULL` | JSON z tablicą nazw wykonanych czynności (checkboxy z katalogu usług); `NULL` = jeszcze nie zapisywano, `[]` = zapisano i pusto. |
| `status` | `ENUM('in_progress','completed','picked_up') NOT NULL DEFAULT 'in_progress'` | Status zgłoszenia. |
| `service_no` | `VARCHAR(32) NULL`, `UNIQUE KEY uk_service_no` | Numer serwisowy (etykieta QR na rowerze), format `RO-RRRR-NNNN`. |
| `confirmed` | `TINYINT(1) NOT NULL DEFAULT 1` | Potwierdzenie zgłoszenia (0 = wymaga potwierdzenia na PC po przyjęciu z mobile). |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Data utworzenia. |
| `deleted_at` | `TIMESTAMP NULL` | Kosz (soft delete); niepuste = w koszu. |
| `created_by` | `INT UNSIGNED NULL` | Właściciel — kto założył zgłoszenie (id `users`); `NULL` = rekord sprzed wdrożenia. |
| `confirmed_by` | `INT UNSIGNED NULL` | Kto wydał rower (kliknięcie „Wydaj rower”); zerowany przy cofnięciu wydania. |

Indeksy: `PRIMARY KEY (id)`, `UNIQUE KEY uk_service_no (service_no)`, `KEY idx_status (status)`.

**`zdjecia`** — metadane zdjęć:

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator zdjęcia. |
| `zgloszenie_id` | `INT UNSIGNED NOT NULL` | Właściciel — klucz obcy do `zgloszenia(id)`; `ON DELETE CASCADE`. |
| `filename` | `VARCHAR(255) NOT NULL` | Nazwa pliku na dysku (losowa: 32 znaki hex + rozszerzenie). |
| `original_name` | `VARCHAR(255) NULL` | Oryginalna nazwa pliku przesłanego przez użytkownika (max 255). |
| `size_bytes` | `BIGINT UNSIGNED NULL` | Rozmiar pliku w bajtach (dodany migracją, uzupełniany z dysku). |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Data wgrania. |

Klucze: `KEY idx_zgloszenie (zgloszenie_id)`, `CONSTRAINT fk_zdjecia_zgloszenie ... ON DELETE CASCADE`.

**`ustawienia`** — para klucz→wartość (config aplikacji w bazie):

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `klucz` | `VARCHAR(64)` (PK) | Nazwa ustawienia. |
| `wartosc` | `VARCHAR(255) NOT NULL DEFAULT ''` | Wartość (ograniczenie 255 znaków — dlatego cache aktualizacji zapisywany jest bez pola `opis`). |

Znane klucze: `app_password_hash`, `moduly` (JSON), `installed_version`, `update_check_at`, `update_check_result`, `service_address`, `service_city`, `service_phone`, `google_maps_url`, `site_url`, `korekta_2_9_uslugi`.

**`uslugi`** — katalog usług:

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator usługi. |
| `nazwa` | `VARCHAR(255) NOT NULL`, `UNIQUE KEY uk_nazwa` | Nazwa usługi (unikalna — duplikat = błąd INSERT). |

**`users`** — konta użytkowników (3.0):

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator konta. |
| `login` | `VARCHAR(64) NOT NULL`, `UNIQUE KEY uk_login` | Login (2–64 znaki, bez spacji). |
| `password_hash` | `VARCHAR(255) NOT NULL` | Hash hasła (`password_hash(..., PASSWORD_DEFAULT)`). |
| `rola` | `ENUM('admin','pracownik') NOT NULL DEFAULT 'pracownik'` | Rola konta — jedyna skladowa mapy uprawnień rolowych. |
| `aktywny` | `TINYINT(1) NOT NULL DEFAULT 1` | Konto aktywne (0 = wyłączone, logowanie niemożliwe). |
| `must_change_password` | `TINYINT(1) NOT NULL DEFAULT 0` | Wymuszona zmiana hasła (ustawiane przy resecie przez admina, zerowane przy zmianie własnego hasła). |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Data utworzenia konta. |
| `last_login_at` | `TIMESTAMP NULL` | Ostatnie logowanie (aktualizowane w `auth_login()`). |

**`sesje`** — sesje w bazie:

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator sesji. |
| `token_hash` | `CHAR(64) NOT NULL`, `UNIQUE KEY uk_token` | **Hash SHA-256** tokena (w bazie nie leży surowy token). |
| `user_id` | `INT UNSIGNED NOT NULL` | Właściciel — klucz obcy do `users(id)`, `ON DELETE CASCADE`. |
| `expires_at` | `DATETIME NOT NULL` | Wygaśnięcie sesji (przedłużane przy sesji ślizgającej). |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Utworzenie sesji. |

Klucze: `KEY idx_sesje_user (user_id)`, `CONSTRAINT fk_sesje_user ... ON DELETE CASCADE`.

**`login_attempts`** — ochrona przed brute-force:

| Kolumna | Typ | Znaczenie |
|---|---|---|
| `id` | `INT UNSIGNED AUTO_INCREMENT` (PK) | Identyfikator wpisu. |
| `klucz` | `VARCHAR(160) NOT NULL` | Klucz próby: `login|REMOTE_ADDR`. |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Czas próby. |

Klucz: `KEY idx_klucz_czas (klucz, created_at)`.

### 1.3. Migracje i seedy wykonywane w `db()` (przy każdym pierwszym wywołaniu w żądaniu)

1. **Seed konta admin** — gdy tabela `users` jest pusta: `INSERT INTO users (login, password_hash, rola) VALUES ('admin', app_password_hash(), 'admin')`.
2. **`zdjecia.size_bytes`** — wykrywanie kolumny przez `SHOW COLUMNS`; brak = `ALTER TABLE zdjecia ADD COLUMN size_bytes ... AFTER original_name` + uzupełnienie wartości `filesize()` z dysku dla istniejących wierszy.
3. **Migracje `zgloszenia`** (wykrywanie kolumn przez `SHOW COLUMNS`):
   - `confirmed` — `ALTER TABLE ... ADD COLUMN confirmed TINYINT(1) NOT NULL DEFAULT 1 AFTER status`;
   - `service_no` — dodanie kolumny, wsteczne wypełnienie numerem `RO-<rok od created_at>-<id w formacie %04d>` dla wszystkich istniejących zgłoszeń oraz `ALTER TABLE ... ADD UNIQUE KEY uk_service_no`;
   - `service_notes` — `ADD COLUMN ... AFTER fault_description`;
   - `services_done` — `ADD COLUMN ... AFTER service_notes`;
   - **korekta jednorazowa 2.9** (marker ustawienia `korekta_2_9_uslugi`): jeśli `services_done` zgadza się w całości z liniami `- ` z `fault_description`, pole czyszczone jest do `NULL` (karta pokazuje puste checkboxy); potem marker ustawiany na `'1'`;
   - `deleted_at` — `ADD COLUMN ... AFTER created_at` (kosz / soft delete);
   - `created_by` — `ADD COLUMN ... AFTER deleted_at` (właściciel / kto założył);
   - `confirmed_by` — `ADD COLUMN ... AFTER created_by` (kto wydał rower);
   - **`date_planned` nullable** — jeśli kolumna ma `Null = NO`, wykonywane jest `MODIFY COLUMN date_planned DATE DEFAULT NULL` (moduł „kalendarz” może być wyłączony = brak terminu).
4. **Katalog uploads** — `mkdir(UPLOAD_DIR, 0755, true)` gdy nie istnieje.

Migracje są **idempotentne** (wykrywanie stanu przez `SHOW COLUMNS` / `SELECT`), wykonywane automatycznie również po `do_update()`.

---

## 2. Funkcje `config.php` — według kategorii

### 2.1. Baza danych i „migracje”

- **`db(): PDO`** — zwraca (z cache `static`) połączenie PDO; najpierw łączy się z serwerem bez bazy, tworzy bazę `DB_NAME` (utf8mb4), przełącza się na nią, tworzy wszystkie tabele i wykonuje opisane wyżej migracje i seedy. Ustawia `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION`, `DEFAULT_FETCH_MODE => FETCH_ASSOC`, `EMULATE_PREPARES => false` (prawdziwe prepared statements).

### 2.2. Uwierzytelnianie i sesje (`auth_*`)

- **`app_password_hash(): string`** — czyta hash hasła aplikacji z `ustawienia.app_password_hash`; gdy brak, generuje `password_hash(APP_PASSWORD, PASSWORD_DEFAULT)` i zapisuje (seed przy pierwszym uruchomieniu).
- **`change_own_password(string $current, string $next): ?string`** — zmiana hasła **bieżącego** konta: weryfikuje `current` przez `password_verify`, wymaga `strlen($next) >= 6`, zapisuje nowy hash i zeruje `must_change_password`; kasuje pozostałe sesje konta (`token_hash <> bieżący`). Zwraca `null` (sukces) lub komunikat błędu.
- **`auth_cookie_plausible(string $token): bool`** — czy token wygląda jak nasz: dokładnie 64 znaki `[a-f0-9]` (regex `^[a-f0-9]{64}$`).
- **`auth_is_https(): bool`** — `true` gdy `$_SERVER['HTTPS']` ustawione i różne od `'off'` **lub** nagłówek reverse proxy `HTTP_X_FORWARDED_PROTO === 'https'`.
- **`auth_cookie_options(int $expires): array`** — wspólne opcje cookie sesji: `path => '/'`, `httponly => true`, `samesite => 'Lax'`, `secure => auth_is_https()`.
- **`auth_user(): ?array`** — zwraca dane bieżącego użytkownika z cache na żądanie albo `null`. Pobiera cookie `AUTH_COOKIE`, odrzuca token niepasujący wzorcowi, liczy `hash('sha256', $token)` i łączy tabele `sesje` z `users` po `token_hash` z warunkiem `u.aktywny = 1`. Przeterminowaną sesję kasuje; sesję ślizgającą przedłuża (gdy zostało mniej niż połowa `SESSION_TTL`) i odświeża cookie. Zwraca wiersz `users` + `session_id` / `session_hash` / `expires_at`.
- **`auth_is_authenticated(): bool`** — `auth_user() !== null`.
- **`current_user(): ?array`** — alias `auth_user()`.
- **`login_blocked(string $key): bool`** — `true`, gdy w oknie `LOGIN_ATTEMPT_WINDOW` zarejestrowano ≥ `LOGIN_MAX_ATTEMPTS` prób dla klucza.
- **`auth_login(string $login, string $password): ?string`** — logowanie: sprząta przeterminowane sesje i stare próby, sprawdza blokadę, pobiera `users WHERE login = ? AND aktywny = 1`, weryfikuje hasło (`password_verify`). Przy porażce zapisuje próbę i zwraca **celowo uniwersalny** komunikat „Nieprawidłowy login lub hasło.” (nie zdradza istnienia loginu). Przy sukcesie: kasuje próby, generuje token `bin2hex(random_bytes(32))`, zapisuje w `sesje` wyłącznie jego **hash**, aktualizuje `last_login_at`, ustawia cookie. Zwraca `null` (sukces) lub komunikat błędu.
- **`auth_logout(): void`** — kasuje sesję w bazie po hashu z cookie (nie tylko cookie!) i czyści cookie (data w przeszłości).
- **`auth_require(): void`** — dla endpointów API: gdy brak sesji — `json_fail(..., 401)`.
- **`auth_is_admin(): bool`** — `true` gdy zalogowany i `rola === 'admin'`.
- **`auth_require_admin(): void`** — guard endpointów admina: najpierw `auth_require()` (401), potem sprawdzenie roli → `json_fail('Brak uprawnień administratora.', 403)`.

### 2.3. Ustawienia, moduły, dane instancji

- **`setting_get(string $klucz, string $domyslna = ''): string`** — odczyt z tabeli `ustawienia`; brak klucza = wartość domyślna.
- **`setting_set(string $klucz, string $wartosc): void`** — zapis/upsert (`INSERT ... ON DUPLICATE KEY UPDATE`).
- **`dane_instancji(): array`** — tablica `['adres','miasto','telefon','maps_url','site_url']` odczytana z bazy, z fallbackiem do stałych z `config.php`.
- **`wersja_aplikacji(): string`** — `setting_get('installed_version', APP_VERSION)`.
- **`moduly_dostepne(): array`** — pełna lista modułów możliwych do wyłączenia: `kalendarz`, `zdjecia`, `skaner`, `uslugi`, `druk`, `kosz`, `kolorystyka`, `powitanie`, `karta_wydania`, `wykonane`.
- **`moduly(bool $refresh = false): array`** — mapa `nazwa => bool` z cache (`static`); odczytuje JSON z klucza `moduly`; **brak klucza = moduł włączony** (kompatybilność wstecz). `$refresh = true` wymusza odświeżenie.
- **`modul(string $nazwa): bool`** — czy dany moduł jest włączony (nieznany = `false`).
- **`users_login_map(): array`** — mapa `id => login` użytkowników (statyczny cache, jedno zapytanie na żądanie — eliminuje N+1 na liście zgłoszeń).

### 2.4. Katalog usług

- **`uslugi_list(): array`** — lista `[{id, nazwa}]` posortowana po `id`.
- **`uslugi_add(string $nazwa): ?string`** — dodaje usługę (trim, max 255 znaków przez `mb_substr`); pusty opis = błąd, duplikat (PDOException na UNIQUE) = „Taka usługa już istnieje.”. Zwraca `null` (sukces) lub komunikat.
- **`uslugi_remove(int $id): bool`** — usuwa usługę po id; `true` gdy usunięto choć jeden wiersz.

### 2.5. Statystyki i zdjęcia

- **`photos_stats(): array`** — `['photos' => liczba, 'bytes' => suma size_bytes]` dla całej tabeli `zdjecia`.
- **`photos_for(int $zgloszenieId): array`** — zdjęcia danego zgłoszenia jako `[{id, url, name, created}]` (`url = UPLOAD_URL . '/' . filename`), kolejność rosnąca po id.
- **`store_photos(int $zgloszenieId, array $files): array`** — zapis wgranych plików z `$_FILES['photos']` i wpisów w `zdjecia`. Sprawdza: liczbę plików (`> MAX_PHOTOS_PER_REQUEST` → fail), **łączny limit 100 MB** (przed pętlą, żeby nie zapisać części partii), błędy wgrywania (`UPLOAD_ERR_*` z mapą komunikatów), rozmiar `MAX_PHOTO_BYTES`, `is_uploaded_file()`, **MIME przez `finfo`** (fallback `getimagesize`) w `ALLOWED_PHOTO_MIME`, poprawność obrazu (`getimagesize`), a następnie zapisuje pod losową nazwą `bin2hex(random_bytes(16)).ext` z `chmod 0644`. Zwraca tablicę zapisanych zdjęć `[{id, url, name, created}]`; błędy przez `json_fail()`.
- **`delete_photo(int $photoId): bool`** — usuwa plik z dysku (`@unlink`) i wiersz z `zdjecia`; `false` gdy nie istnieje.
- **`delete_photos_of(int $zgloszenieId): void`** — usuwa **wszystkie** zdjęcia zgłoszenia (pliki + wpisy) — używane przy trwałym kasowaniu zgłoszenia.

### 2.6. Mapowanie i walidacja

- **`map_zgloszenie(array $row): array`** — buduje rekord w formacie oczekiwanym przez frontend: `id`, `bikeName`, `dateIn`, `datePlanned`, `customerPhone`, `faultDescription`, `status`, `confirmed` (bool), `serviceNo`, `serviceNotes`, `servicesDone` (`null` = nie zapisywano, tablica = zapisano), `deleted` (bool), `createdAt`, `createdById`/`createdBy`, `confirmedById`/`confirmedBy`, `photos` (z `photos_for`). Uwaga: **`confirmedById/confirmedBy` są zerowane gdy `status !== 'picked_up'`** („kto wydal” widoczne tylko przy faktycznym wydaniu).
- **`validate_zgloszenie(array $in): array`** — walidacja formularza zgłoszenia; zwraca oczyszczoną tablicę `[$bikeName, $dateIn, $datePlanned|null, $phone, $fault, $status]`. Wymaga: niepustej nazwy roweru (≤255), daty `YYYY-MM-DD` sprawdzonej `checkdate()` (przyjęcia — zawsze; planowanej — gdy włączony moduł `kalendarz`, przy wyłączonym pusty termin zamieniany na `null`), ≥9 cyfr w telefonie, niepustego opisu usterki, statusu z `STATUSES`. Błędy przez `json_fail()`.
- **`validate_services_done(string $raw): array`** — walidacja listy wykonanych czynności (string JSON → tablica): odrzuca nie-tablice, pomija puste i nietekstowe, każdy element ≤200 znaków, maks. 50 elementów, deduplikacja (`array_unique`). Pusty string = `[]`.

### 2.7. Pomocniki HTTP/JSON

- **`json_out(mixed $data, int $httpCode = 200): never`** — wysyła nagłówki (`Content-Type: application/json; charset=utf-8`, `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`, `Pragma: no-cache` — zabezpieczenie przed cache'owaniem LiteSpeed), kod HTTP, JSON (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`) i kończy skrypt.
- **`json_fail(string $error, int $httpCode = 400): never`** — `json_out(['success' => false, 'error' => $error], $httpCode)`.

### 2.8. Automatyczne aktualizacje (v2)

- **`wersja_normalizuj(string $v): string`** — normalizacja do semver `X.Y.Z` („3.8” → „3.8.0”, „3.8-instalator” → „3.8.0”, brak = `0.0.0`) — bez tego `version_compare` źle porównuje sufiksy.
- **`config_przebuduj(string $staryCfg, string $wzorzec): array`** — przebudowuje `config.php` z **nowego** `config.example.php`, przenosząc 1:1 sekrety i dane instancji z pliku starego (lista stałych: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_PASSWORD`, `AUTH_COOKIE`, `SERVICE_ADDRESS`, `SERVICE_CITY`, `SERVICE_PHONE`, `GOOGLE_MAPS_URL`, `SITE_URL`). Twarda walidacja: żadna stała nie może zostać `'UZUPELNIJ'`, muszą istnieć `function db(` i `const APP_VERSION`, komentarz wzorca musi zostać przerobiony. Zwraca `['ok' => bool, 'tresc' => string, 'blad' => string]`.
- **`check_update(bool $force = false): ?array`** — sprawdza `https://api.github.com/repos/mex13rs/roweryexpert/releases/latest` (User-Agent `RoweryExpert-Updater`, timeout 10 s); cache 24 h w `update_check_at` / `update_check_result` (bez pola `opis` — kolumna ma 255 znaków). Zwraca `['dostepna', 'nowa_wersja', 'obecna_wersja', 'url', 'opis']` albo `null` przy błędzie sieci/parsowania.
- **`do_update(): array`** — pełny proces aktualizacji: pobiera release z GitHuba, waliduje format tagu `^v\d+\.\d+(\.\d+)?$`, **blokuje aktualizację wstecz i pętlę** (`version_compare <=`), pobiera zipball, zapisuje plik tymczasowy, weryfikuje ZIP (musi zawierać `serwis.php` i `install.php`), odczytuje nowy `config.example.php` z paczki i **przed podmianą plików** przebudowuje nowy `config.php` (przerwanie = „Nic sie nie zmienilo”). Następnie: kopia zapasowa do `uploads/backup/backup-<data>.zip` (pomija `config.php`, `uploads/`, `.git/`, backupy) z tworzeniem `.htaccess` „Require all denied”, rozpakowanie paczki z pominięciem `config.php`, `uploads/`, `.user.ini`, zapis z obsługą błędu prawa zapisu, `opcache_invalidate()` na plikach i `opcache_reset()`, wywołanie `db()` (migracje), zapis `installed_version`, wyczyszczenie cache aktualizacji. Zwraca `['success' => bool, ...]` (błąd: `'error'`, sukces: `'data' => ['nowa_wersja']`).

---

## 3. Endpointy API (`api/*.php`)

Wspólne dla **wszystkich** endpointów:

- **Gament brakującego `config.php`** — `if (!is_file(__DIR__ . '/../config.php'))` → HTTP **503** + JSON `{"success":false,"error":"Instalacja nie zostala zakonczona - uruchom install.php"}` (panel `serwis.php` zamiast tego przekierowuje na `install.php`).
- Wspólna odpowiedź sukcesu: `{"success": true, ...}`; błędu: `{"success": false, "error": "..."}`.
- Kody: 200 / 201 (twórczo) / 400 / 401 (brak sesji) / 403 (brak uprawnień) / 404 / 405 / 500 / 503.
- **Brak tokenów CSRF** w endpointach API (token `inst_csrf` występuje wyłącznie w `install.php`) — ochrona opiera się na cookie `HttpOnly` + `SameSite=Lax`.

### 3.1. `api/zgloszenia.php` — zgłoszenia serwisowe

Uwierzytelnienie: `auth_require()` dla wszystkich metod. Blok `try/catch (Throwable)` → `error_log('[zgloszenia.php] ...')` + `json_fail('Błąd serwera: ' . $e->getMessage(), 500)`.

| Metoda | `action` / parametry | Uprawnienia | Efekt / odpowiedź |
|---|---|---|---|
| **GET** | brak | każda zalogowana rola | `SELECT * FROM zgloszenia ORDER BY id DESC` (razem z koszem!), każde przez `map_zgloszenie`. Odp.: `{success, data: [rekordy]}`. **Brak filtrowania po właścicielu — każdy widzi wszystkie zgłoszenia.** |
| **POST** | `action=create` (multipart): `bike_name`, `date_in`, `date_planned`, `customer_phone`, `fault_description`, `status`, `source` (`desktop`/`mobile`), `services_done` (kompatybilność ze starym frontem), pliki `photos` | każda rola | Walidacja `validate_zgloszenie`; `source=mobile` → `confirmed=0`, inaczej `1`; moduł `zdjecia` wyłączony + obecne pliki → 400 („Moduł zdjęć jest wyłączony w ustawieniach panelu.”); INSERT z `created_by = id bieżącego` (pole `services_done` zapisywane tylko gdy lista niepusta, inaczej `NULL`); numer serwisowy `RO-<rok>-%04d(id)`; `store_photos()`. Odp. **201** `{success, data: <map_zgloszenie>}`. |
| **POST** | `action=status`: `id`, `status` | każda rola | Walidacja `id > 0` i statusu z `STATUSES`; 404 gdy brak. Przejście do `picked_up` → zapis `confirmed_by = id bieżącego`; powrót z `picked_up` → `confirmed_by = NULL`; inaczej tylko `status`. Odp.: `{success, id, status, data: <map_zgloszenie>}` (pełny rekord, żeby frontend odświeżył „kto wydał”). |
| **POST** | `action=confirm`: `id` | każda rola | `UPDATE ... SET confirmed = 1, confirmed_by = id`; 404 gdy rekord nie istnieje. Odp.: `{success, id, confirmed: true}`. |
| **POST** | `action=update`: `id` + pola formularza, `service_notes` | każda rola | 404 gdy brak; pełna walidacja; notatka ≤6000 znaków; `date_planned` przez `COALESCE(?, date_planned)` (NULL = brak zmiany). Odp.: `{success, data: <map_zgloszenie>}`. |
| **POST** | `action=services`: `id`, `services_done` (JSON) | każda rola + **włączony moduł `wykonane`** (403) | Zapis checkboxów „Wykonane czynności”; pusta lista = `'[]'` (nie `null`). Odp.: `{success, data}`. |
| **POST** | `action=notes`: `id`, `service_notes` | każda rola | Autozapis notatek z karty (≤6000 znaków); pusty = `NULL`. Odp.: `{success, data}`. |
| **POST** | `action=restore`: `id` | każda rola + moduł `kosz` (403) + **`owner_guard($id)`** | `deleted_at = NULL`. Odp.: `{success, id}`. |
| **POST** | nieznany `action` | — | **404** „Nieznana akcja.” |
| **DELETE** | `?id=N` (bez `purge`) | każda rola + moduł `kosz` (403) + **`owner_guard($id)`** | Soft delete: `deleted_at = NOW() WHERE deleted_at IS NULL`. Odp.: `{success, id, deleted: true}`. |
| **DELETE** | `?id=N&purge=1` | **wyłącznie admin** (`auth_require_admin()`) + moduł `kosz` | Trwałe usunięcie: `delete_photos_of($id)` (pliki z dysku) + `DELETE FROM zgloszenia`. Odp.: `{success, id, purged: true}`. **Nieodwracalne.** |
| inne metody | — | — | **405** „Metoda nieobsługiwana.” |

Funkcje lokalne pliku:

- **`record_exists(int $id): bool`** — `SELECT 1 FROM zgloszenia WHERE id = ?`.
- **`owner_guard(int $id): void`** — **zasada własności**: admin przechodzi bez ograniczeń; pracownik musi mieć `created_by === auth_user()['id']`, inaczej `json_fail('Możesz kasować i przywracać tylko własne zgłoszenia.', 403)`. Rekordy z `created_by = NULL` (sprzed wdrożenia) kasuje/przywraca **tylko admin**. Stosowane **wyłącznie** przy `restore` i `DELETE` (kosz) — nie przy edycji/statusach/zdjęciach.

### 3.2. `api/zdjecia.php` — zdjęcia zgłoszeń

Uwierzytelnienie: `auth_require()`; **dodatkowa bramka na poziomie całego pliku: wyłączony moduł `zdjecia` = 403 dla całego API** („Moduł zdjęć jest wyłączony w ustawieniach panelu.”). `try/catch` → `error_log('[zdjecia.php] ...')` + 500.

| Metoda | Parametry | Uprawnienia | Odpowiedź / efekt |
|---|---|---|---|
| **GET** | `?zgloszenie_id=N` | każda zalogowana rola (moduł `zdjecia`) | `{success, data: [{id, url, name, created}]}`; brak/nieprawidłowe `zgloszenie_id` → 400. |
| **POST** | multipart: `zgloszenie_id`, pliki `photos` | każda zalogowana rola (moduł `zdjecia`) | Weryfikacja istnienia zgłoszenia (404), `$_FILES['photos']` niepuste (400), `store_photos()` z pełną walidacją limitów. Odp. **201** `{success, data: [<zapisane zdjęcia>]}`. **Brak `owner_guard` — każdy zalogowany doda/usunie zdjęcie do każdego zgłoszenia.** |
| **DELETE** | `?id=N` | każda zalogowana rola (moduł `zdjecia`) | `delete_photo()` — kasuje plik i wiersz; nie istnieje → 404. Odp.: `{success, id}`. |
| inne metody | — | — | **405** „Metoda nieobsługiwana.” |

### 3.3. `api/uslugi.php` — katalog usług

Uwierzytelnienie: `auth_require()` na starcie. **Brak bloku `try/catch`** (błędy PHP nie są tu zamieniane na JSON 500).

| Metoda | `action` / parametry | Uprawnienia | Odpowiedź |
|---|---|---|---|
| **GET** | brak | każda zalogowana rola (odczyt = wszyscy) | `{success, data: [{id, nazwa}]}`. |
| **POST** | `action=add`, `nazwa` | **admin** (`auth_require_admin()`) + moduł `uslugi` (403) | `uslugi_add()`; błąd → 400; sukces → `{success, data: <pełna lista>}`. |
| **POST** | `action=remove`, `id` | **admin** + moduł `uslugi` (403) | `uslugi_remove()`; nie usunięto → 404 „Nie znaleziono usługi.”; sukces → `{success, data: <pełna lista>}`. |
| **POST** | nieznany `action` | admin | **400** „Nieznana akcja.” |
| inne metody | — | — | **400** „Nieznane żądanie.” |

### 3.4. `api/uzytkownicy.php` — konta użytkowników

Uwierzytelnienie: **`auth_require_admin()` na samym początku pliku** — cały endpoint dostępny wyłącznie dla admina. **Brak `try/catch`.** Lokalna funkcja **`uzytkownicy_login_ok(string $login): bool`** — regex `^[A-Za-z0-9ĄĆĘŁŃÓŚŹŻąćęłńóśźż._-]{2,64}$` (login 2–64 znaki, bez spacji).

| Metoda | `action` / parametry | Odpowiedź / efekt |
|---|---|---|
| **GET** | brak | Lista kont **bez hashy haseł**: `id, login, rola, aktywny, must_change_password, created_at, last_login_at` + podzapytania `zgloszenia` (liczba zgłoszeń z `created_by`) i `wydane` (z `confirmed_by`); sortowanie: admini pierwsi, potem login. Odp.: `{success, data: [wiersze]}`. |
| **POST** | `action=create`: `login`, `haslo`, `rola` | Walidacja loginu (regex), hasło ≥6 znaków, unikalność loginu (400). INSERT z `password_hash($haslo, PASSWORD_DEFAULT)`. Odp. **201** `{success, id}`. |
| **POST** | `action=password`: `id`, `haslo` | Nowy hash + `must_change_password = 1` + **kasowanie wszystkich sesji konta** (`DELETE FROM sesje WHERE user_id = ?`) — wymuszona zmiana i wylogowanie. Odp.: `{success}`. |
| **POST** | `action=role`: `id`, `rola` (`admin`/`pracownik`) | Zmiana roli; **nie można zmienić roli własnego konta** (400). Odp.: `{success}`. |
| **POST** | `action=toggle`: `id`, `aktywny` (0/1) | Włączenie/wyłączenie konta; **nie można wyłączyć własnego konta** (400); przy wyłączeniu kasowane są wszystkie sesje tego konta. Odp.: `{success}`. |
| **POST** | `action=delete`: `id` | **Twarde usunięcie tylko kont bez historii**: nie można usunąć własnego konta (400); gdy konto ma zgłoszenia (`created_by` lub `confirmed_by`) → 400 z komunikatem „…wyłącz je zamiast kasować…”. Sukces = `DELETE users` + `DELETE sesje` + `DELETE login_attempts WHERE klucz LIKE 'login|%' ESCAPE '\\'` (z escape'owaniem `\ % _`). Odp.: `{success, id}`. |
| **POST** | nieznany `action` | **400** „Nieznana akcja.” |
| inne metody | — | **400** „Nieznane żądanie.” |

### 3.5. `api/ustawienia.php` — ustawienia panelu (moduły, dane instancji, aktualizacje)

Uwierzytelnienie: `auth_require()` na starcie; konkretne akcje dodatkowo wymagają admina. `try/catch` → `error_log('[ustawienia.php] ...')` + 500.

| Metoda | `action` | Uprawnienia | Odpowiedź / efekt |
|---|---|---|---|
| **GET** | brak | każda zalogowana rola | `{success, data: {moduly: {...}, dane_instancji: {...}, update: <check_update() bez force — cache 24 h>}}`. |
| **POST** | `check_update` | każda zalogowana rola | `check_update(true)` (wymuszenie odpytania GitHuba). Odp.: `{success, data: {update: ...|null}}`. |
| **POST** | `do_update` | **admin** | `do_update()` — odpowiedź przekazywana wprost: `{success:false, error}` lub `{success:true, data:{nowa_wersja}}`. **Efekty uboczne:** pobranie paczki z GitHuba, kopia zapasowa ZIP w `uploads/backup/` (+ `.htaccess` blokujący dostęp), podmiana plików (z pominięciem `config.php`, `uploads/`, `.user.ini`), przebudowa `config.php` z nowego wzorca ze starymi sekretami + kopia `config-<data>.php.bak`, `opcache_reset()`, migracje `db()`, zapis `installed_version`, wyczyszczenie cache aktualizacji. |
| **POST** | `modules` | **admin** | Pole `moduly` = JSON; wejście **sanityzowane do listy `moduly_dostepne()`** (brak klucza = `true`), zapis `setting_set('moduly', json)` + odświeżenie cache `moduly(true)`. Odp.: `{success, data: {moduly}}`. |
| **POST** | `dane_instancji` | **admin** | Pola i limity: `service_address` ≤120, `service_city` ≤120, `service_phone` ≤32, `google_maps_url` ≤255 (**`FILTER_VALIDATE_URL`, puste dozwolone**), `site_url` ≤255 (**`FILTER_VALIDATE_URL`**). Zapis do `ustawienia`. Odp.: `{success, data: {dane_instancji}}`. |
| **POST** | inne | — | **400** „Nieznane żądanie.” |

### 3.6. `api/konto.php` — konto bieżącego użytkownika

Uwierzytelnienie: `auth_require()` na starcie. **Brak `try/catch`.**

| Metoda | `action` / parametry | Uprawnienia | Odpowiedź / efekt |
|---|---|---|---|
| **GET** | brak | **admin** (`auth_require_admin()` wewnątrz gałęzi GET) | `{success, data: {photos: <liczba>, bytes: <bajty>}}` — statystyki zdjęć (`photos_stats()`). |
| **POST** | `action=password`: `current`, `next` | każda zalogowana rola (własne konto) | `change_own_password()`; błąd → 400 z komunikatem; sukces → `{success}`. Efekty: nowy hash, zerowanie `must_change_password`, **kasowanie pozostałych sesji** tego konta (bieżąca zostaje). |
| **POST** | inne | — | **400** „Nieznane żądanie.” |

---

## 4. Model bezpieczeństwa — podsumowanie

### 4.1. Role i mapa uprawnień

Istnieją **dwie role** w `users.rola`: `admin` i `pracownik` (domyślna). Nie ma roli „mechanik”. Guardy tworzące mapę uprawnień:

- `auth_require()` → 401 „Brak autoryzacji - zaloguj się.” (brak/nieprawidłowa sesja);
- `auth_require_admin()` → 401, a następnie 403 „Brak uprawnień administratora.” (rola ≠ `admin`);
- `owner_guard($id)` → 403 „Możesz kasować i przywracać tylko własne zgłoszenia.” (pracownik bez `created_by` = swojego);
- bramki modułów `modul(...)` → 403 z komunikatem o wyłączonym module.

| Uprawnienie | `admin` | `pracownik` |
|---|---|---|
| Podgląd **wszystkich** zgłoszeń (GET lista, także kosz) | tak | tak (bez filtrowania po właścicielu) |
| Tworzenie / edycja / notatki / checkboxy / zmiana statusu / potwierdzanie dowolnego zgłoszenia | tak | tak |
| Soft delete (kosz) i przywracanie | każde zgłoszenie | **tylko własne** (`owner_guard`) |
| Trwałe usunięcie (`purge=1`, z plikami) | **tak** | **nie** (403) |
| Dodawanie / usuwanie zdjęć | każde zgłoszenie | każde zgłoszenie (brak `owner_guard`) |
| Odczyt katalogu usług | tak | tak |
| Zapis / usunięcie usługi | **tak** | nie (403) |
| Zarządzanie kontami (`uzytkownicy.php`) | **tak** (cały plik) | nie (401/403) |
| Ustawienia: moduły, dane instancji, aktualizacja (`do_update`) | **tak** | nie (403) |
| Sprawdzenie aktualizacji (`check_update`) | tak | tak |
| Statystyki zdjęć (GET `konto.php`) | **tak** | nie (403) |
| Zmiana **własnego** hasła | tak | tak |

### 4.2. Własność (ownership)

- Właściciel zgłoszenia = kolumna `zgloszenie.created_by` (kto założył), powiązanie po id z `users`; `confirmed_by` = kto wydał rower (zerowany przy cofnięciu wydania i maskowany przez `map_zgloszenie` gdy status ≠ `picked_up`).
- `owner_guard()` działa **tylko** w `zgloszenia.php` przy `action=restore` i `DELETE` (kosz). Rekordy sprzed wdrożenia (`created_by = NULL`) są „niczyje” → kasuje/przywraca je wyłącznie admin.
- Rekordy nie są anonimizowane: konto z historią zgłoszeń **nie da się usunąć na stałe** (można je tylko wyłączyć `toggle`), żeby na kartach zachować informację, kto obsługiwał zlecenie.

### 4.3. Sesje, cookie, logowanie

- Sesje trzymane **w bazie** (`sesje`), TTL **14 dni**, sesja **ślizgająca** (przedłużana przy < połowie TTL, cookie odświeżane).
- Token: 32 bajty `random_bytes()` w hex; w bazie wyłącznie `hash('sha256', token)` — wyciek bazy nie umożliwia przejęcia sesji; surowy token tylko w cookie.
- Cookie: **własna nazwa** `AUTH_COOKIE` (izolacja od starej wersji), `path=/`, **`HttpOnly`**, **`SameSite=Lax`**, `Secure` gdy `auth_is_https()` (HTTPS lub `X-Forwarded-Proto: https`).
- Token weryfikowany przez `auth_cookie_plausible()` (dokładnie 64 znaki hex) przed jakimkolwiek zapytaniem.
- Brute-force: `login_attempts` z kluczem `login|REMOTE_ADDR`, **5 prób w 15 minut**; komunikaty logowania uniwersalne (nie zdradzają istnienia loginu); czyszczenie starych wpisów i przeterminowanych sesji przy każdym logowaniu.
- Hasła: **`password_hash(PASSWORD_DEFAULT)` / `password_verify`** (bcrypt/argon wg instalacji), min. 6 znaków; hash hasła aplikacji seedowany z `APP_PASSWORD` do `ustawienia.app_password_hash`.
- Wylogowanie kasuje sesję **w bazie**, nie tylko cookie; zmiana/reset hasła kasuje sesje konta.
- Logowanie i wylogowanie obsługiwane przez `serwis.php` (`POST action=login`, `?logout`), nie przez API.

### 4.4. Pozostałe zabezpieczenia

- **Prepared statements** wszędzie: PDO z `EMULATE_PREPARES => false`, `ERRMODE_EXCEPTION`; brak złożeń zmiennych w SQL (jedyne `LIKE ? ESCAPE` z `addcslashes`).
- **Wgrywanie zdjęć**: limity 10 MB / plik, 100 MB łącznie, 20 plików / żądanie; biała lista MIME (`finfo` + fallback `getimagesize`), weryfikacja `getimagesize`, `is_uploaded_file()`, losowa nazwa pliku (32 hex), `chmod 0644`, brak wykonywania plików; wyłączenie modułu `zdjecia` blokuje całe API zdjęć i przyjęcie ze zdjęciami.
- **Wrażliwe dane**: `config.php` (hasła DB i aplikacji) wykluczony z kopii zapasowej aktualizacji i z podmiany przez paczkę GitHub; katalog `uploads/backup/` chroniony generowanym `.htaccess` „Require all denied” (backupy zawierają sekrety). Brak wysyłki e-maili — **aplikacja nie zawiera żadnej funkcji `mail()`/SMTP**.
- **HTTPS**: wykrywany również przez nagłówek reverse proxy, wpływa wyłącznie na flagę `Secure` cookie (brak wymuszania przekierowań na HTTPS).
- **Brak CSRF w API** — ochrona wynika z `SameSite=Lax` + `HttpOnly`; token `inst_csrf` (porównanie `hash_equals`) istnieje tylko w formularzach `install.php`.
- **Gate instalacji**: brak `config.php` → 503 JSON (API) / przekierowanie (panel).
- **Odpowiedzi błędów**: `catch (Throwable)` w `zgloszenia.php`, `zdjecia.php`, `ustawienia.php` loguje do `error_log` i zwraca 500 z treścią wyjątka; `uslugi.php`, `uzytkownicy.php`, `konto.php` nie mają tego bloku.
- **Cache**: wszystkie odpowiedzi API i strony mają nagłówki `no-store/no-cache` (ochrona przed starym HTML z LiteSpeed).

---

# Część V — Frontend JS (1/2): rdzeń, lista, formularz, druk

Fragment obejmuje sześć plików frontendu panelu „RoweryExpert". Wszystkie nazwy poniżej
to **rzeczywiste** identyfikatory z kodu (funkcje, zmienne, klasy, identyfikatory HTML,
klucze API). Opisy nie dodają funkcjonalności, których w tych plikach nie ma.

**Wspólny stan globalny** deklarowany w `core.js` i wykorzystywany przez pozostałe pliki:

| Zmienna | Znaczenie |
|---|---|
| `db` | tablica zgłoszeń wczytana z API (`api/zgloszenia.php`) / bazy MySQL |
| `currentFilter` | aktywny filtr listy, domyślnie `'all'` |
| `currentSort` | aktywne sortowanie, domyślnie `'planned_asc'` („najbliższy termin odbioru na górze") |
| `searchQuery` | aktualna treść pola wyszukiwania |
| `MODULY` | obiekt flag włączonych modułów panelu |
| `IS_MOBILE` | stała wykrywająca urządzenie mobilne (liczona raz, przy załadowaniu skryptu) |

**Stałe endpointów API** (deklarowane w `core.js`, używane w całym panelu):

`API_ZGLOSZENIA = 'api/zgloszenia.php'`, `API_ZDJECIA = 'api/zdjecia.php'`,
`API_KONTO = 'api/konto.php'`, `API_USLUGI = 'api/uslugi.php'`,
`API_USTAWIENIA = 'api/ustawienia.php'`, `API_UZYTKOWNICY = 'api/uzytkownicy.php'`.

---

## 1. `core.js` — rdzeń panelu

### 1.1 Wrapper żądań API

#### `apiFetch(url, options)`
Asynchroniczny wrapper nad `window.fetch` z obsługą wygasłej sesji: przy odpowiedzi
HTTP **401** wyświetla toast „Brak dostępu - zaloguj się ponownie." (typ `error`),
po 1200 ms wykonuje `window.location.reload()` i rzuca `new Error('Unauthorized')`.
Dla pozostałych kodów zwraca odpowiedź bez zmian. **Uwaga:** wrapper fizycznie znajduje
się w `core.js` (choć tematycznie opisywany bywa przy `api.js`); nie dołącza on żadnego
tokenu CSRF — komunikacja opiera się na sesji PHP (cookie); tokeny CSRF występują
wyłącznie w `install.php`, nie w tych wywołaniach AJAX.

### 1.2 System ładowania modułów (`MODULY`, `loadModules`, `saveModules`, `modulOn`, `applyModules`)

#### `MODULY` (obiekt)
Domyślna mapa flag modułów: `{ kalendarz, zdjecia, skaner, uslugi, druk, kosz,
kolorystyka, powitanie, karta_wydania, wykonane }` — wszystkie ustawione na `true`.
Flagi żyją w bazie, dzięki czemu PC i telefon widzą te same ustawienia; przy braku API
zostają wartości domyślne (wszystko włączone).

#### `modulOn(nazwa)`
Zwraca `MODULY[nazwa] !== false`, tzn. nieznana/wygasła flaga traktowana jest jako
włączona. To podstawowy warunek używany w całym kodzie (np. `modulOn('druk')`).

#### `loadModules()`
Pobiera flagi przez `apiFetch(API_USTAWIENIA)` i podmienia `MODULY` na
`data.data.moduly`, jeśli `data.success` jest prawdziwe. Błąd sieci/JSON jest
ciszą pochłaniany (`catch`) — wtedy działają flagi domyślne.

#### `saveModules()`
Wysyła `POST` do `API_USTAWIENIA` z polami `action=modules` oraz `moduly`
(JSON flag). Przy `!data.success` rzuca wyjątek z komunikatem serwera
(„Nie udało się zapisać modułów."), a po sukcesie podmienia `MODULY` wersją
zwróconą przez serwer.

#### `applyModules()`
Główna funkcja stosująca flagi do UI. Kolejno: (1) przełącza na `<body>` klasy
`off-<nazwa>` dla każdej wyłączonej flagi (CSS ukrywa elementy statyczne),
(2) wymusza akcent `setAccent(...)` — przy wyłączonym module `kolorystyka` wariant
domyślny `zolty` **bez zapisu** (wybrany kolor zostaje w `localStorage` i wraca
po ponownym włączeniu), (3) ustawia `datePlannedInput.required = kalendarz`
i czyści datę terminu, gdy kalendarz wyłączony (to samo dla `#edit-date-planned`),
(4) resetuje filtry, które zniknęły: `'trash'` → `'all'` bez modułu `kosz`,
`'today' | 'tomorrow' | 'overdue'` → `'all'` bez modułu `kalendarz`,
(5) ukrywa opcje `sort-select` zaczynające się od `planned` i przy potrzebie
przełącza `currentSort` na `dateIn_desc` (z zapisem do `localStorage.re_sort`),
(6) wywołuje `applyDeviceLayout()` i `updateSaveButtons()`, (7) przywraca stany
`active` na `.filter-btn` i `.dash-tile` zgodne z `currentFilter`.

#### `updateSaveButtons()`
Dopasowuje widoczność i etykiety przycisków zapisu do modułów i urządzenia.
Na `IS_MOBILE` pokazuje `#save-only-btn`, a chowa `#save-print-calendar-btn`;
na desktopie odwrotnie i ustawia tekst `#save-btn-label` zależnie od flag:
`druk && kalendarz` → „Zapisz, Drukuj i Dodaj do Kalendarza", tylko `druk` →
„Zapisz i drukuj", tylko `kalendarz` → „Zapisz i dodaj do kalendarza", brak obu →
„Zapisz". Nie robi nic, gdy którekolwiek z trzech pól nie istnieje w DOM.

### 1.3 Nawigacja i zakładki

`core.js` **przechowuje wyłącznie referencje DOM** zakładek ustawień — nie zawiera
logiki przełączania (obsługiwanej przez `switchSettingsTab` w `ustawienia.js`,
a przyciski mają `data-tab` w `partials/modal-ustawienia.php`):

- przyciski: `#tab-btn-general`, `#tab-btn-serwis`, `#tab-btn-uslugi`,
  `#tab-btn-moduly`, `#tab-btn-users`;
- treści: `#tab-content-general`, `#tab-content-serwis`, `#tab-content-uslugi`,
  `#tab-content-moduly`, `#tab-content-users`;
- pozostałe referencje nawigacji/modala ustawień: `#open-settings-btn`,
  `#close-settings-btn`, `#settings-modal`, `#new-service-input`,
  `#add-service-btn`, `#service-list`, `#service-checkbox-list`,
  pola hasła (`#current-password`, `#new-password`, `#confirm-password`,
  `#save-password-btn`, `#password-hint`), statystyki zdjęć
  (`#stats-photos`, `#stats-size`, `#stats-warn`, `#refresh-stats-btn`),
  przełącznik motywu (`#theme-toggle-btn`, `#theme-icon-sun`, `#theme-icon-moon`),
  modala zdjęć (`#photos-modal`, `#photos-modal-title`, `#close-photos-btn`,
  `#photos-modal-input`, `#camera-modal-input`, `#photos-grid`, `#lightbox`,
  `#lightbox-img`), lista i filtry (`#search-input`, `#services-list-container`,
  `.filters .filter-btn`), pola formularza (`#service-form`, `#bike-name`,
  `#date-in`, `#date-planned`, `#customer-phone`, `#fault-description`,
  `#photos-input`, `#camera-input`), `#toast-container`.

### 1.4 Modal potwierdzenia (zamiast natywnego `confirm()`)

#### `showConfirmModal(title, message, okLabel = 'Usuń')`
Ustawia treść w `#confirm-modal-title` / `#confirm-modal-message`, etykietę
przycisku potwierdzenia `#confirm-modal-ok`, dodaje klasę `active` do
`#confirm-modal` i zwraca `Promise`, którego rozstrzygnięcie zależy od wyboru
użytkownika. Domyślna etykieta zgadywania to „Usuń".

#### `closeConfirmModal(result)`
Zdejmuje klasę `active` i rozwiązuje odłożone `confirmModalResolve` wartością
`result` (`true` dla OK, `false` dla Anuluj/zamknięcia), po czym zeruje uchwyt.
Zdarzenia: `#confirm-modal-ok` → `true`, `#confirm-modal-cancel`,
`#confirm-modal-close` oraz kliknięcie w tło modala → `false`.

### 1.5 Powiadomienia toast

`core.js` utrzymuje jedynie referencję `#toast-container` (do którego doklejane są
komunikaty). Samą funkcję `showToast(message, type)` definiuje `motyw.js`
(typy `success` / `info` / `error`, jeden toast naraz, automatyczne usunięcie po 3 s);
`core.js` wywołuje ją m.in. w `apiFetch` (401), przy błędach wczytywania zgłoszeń
i w całym mechanizmie aktualizacji.

### 1.6 Sesja i użytkownik

#### Inicjalizacja w `DOMContentLoaded`
Najpierw ustawiany jest motyw: `localStorage.getItem('theme') || 'dark-theme'`
i `setTheme(savedTheme)` (domyślnie ciemny od pierwszego renderu, także ekranu
logowania). Następnie `if (!IS_AUTHENTICATED) return;` — brak sesji oznacza, że
wyświetlono ekran logowania i dalsza inicjalizacja nie zachodzi. Stałe
`IS_AUTHENTICATED` oraz `USER_ID` pochodzą z bootstrapu PHP (`partials/scripts.php`).

#### Rola administratora
W `checkForUpdate` rolę czyta się przez
`document.getElementById('current-user')?.dataset.rola === 'admin'` — tylko admin
widzi przycisk „Aktualizuj" (zob. sekcja aktualizacji).

#### Reszta startu (po `await loadModules(); applyModules();`)
- automatyczne daty: `#date-in` = dziś (`formatDateForInput(now)`),
  `#date-planned` = dziś + 3 dni, ale wyłącznie gdy `modulOn('kalendarz')`,
  w przeciwnym razie puste;
- `if (modulOn('uslugi')) loadServices();` — katalog usług (checkboxy) tylko przy
  włączonym module `uslugi`;
- pobranie zgłoszeń: `apiFetch(API_ZGLOSZENIA)` → `db = data.data`; przy błędzie
  `data.success` toast z `data.error` lub „Nie udało się wczytać zgłoszeń.",
  przy wyjątku sieciowym toast „Nie można połączyć się z serwerem przez API.";
- `renderServicesList()` — pierwsze wyrenderowanie listy;
- powitanie: gdy `document.body.dataset.powitanie` (z parametru `?powitanie=1`
  po udanym logowaniu) czyści adres przez `history.replaceState(null, '',
  location.pathname)` (żeby F5 nie pokazywał okna ponownie) i wywołuje
  `pokazPowitanie()` tylko gdy `modulOn('powitanie') && modulOn('kalendarz')`;
- `checkForUpdate()` — sprawdzenie aktualizacji przy starcie.

### 1.7 Aktualizacje panelu (`checkForUpdate` / `forceCheckUpdate` / `openUpdateModal` / `closeUpdateModal`)

Referencje: `#update-banner`, `#update-banner-text`, `#update-now-btn`,
`#update-modal`, `#update-modal-versions`, `#update-modal-progress`,
`#update-progress-text`, `#update-progress-bar`, `#update-modal-ok`,
`#update-modal-cancel`, `#update-modal-close`; stan `updateData`.

#### `checkForUpdate()`
Wczytuje `GET API_USTAWIENIA` i jeśli `data.data.update.dostepna` jest prawdziwe,
pokazuje baner `#update-banner`. Dla admina tekst brzmi
„Dostępna nowsza wersja: X (masz Y)" i odsłania `#update-now-btn`; dla pozostałych
„Dostępna nowsza wersja panelu — powiadom administratora." (bez przycisku).
Sprawdzenie działa raz na 24 h po stronie PHP (cache); błąd sieci pozostawia
baner ukryty.

#### `forceCheckUpdate()`
Wymuszone sprawdzenie przyciskiem `#update-check-btn` (omija cache 24 h): blokuje
przycisk („Sprawdzanie…"), wysyła `POST API_USTAWIENIA` z ciałem
`action=check_update` (`application/x-www-form-urlencoded`). Przy dostępnej wersji
pokazuje baner + toast „Dostępna wersja X"; gdy brak — baner
„Masz najnowszą wersję (X)." i toast „Brak nowych aktualizacji."; błędy
komunikuje toastami, a w `finally` przywraca przycisk.

#### `openUpdateModal()`
Otwiera `#update-modal`, wypełnia `#update-modal-versions` tekstem
„Z wersji A do wersji B", chowa pasek postępu i odblokowuje `#update-modal-ok`.

#### `closeUpdateModal()`
Zdejmuje klasę `active` z `#update-modal`; wywoływane przez Anuluj, krzyżyk
i kliknięcie w tło.

#### Handler `#update-modal-ok` (wykonywanie aktualizacji, `do_update`)
Blokuje przycisk, odsłania `#update-modal-progress` („Pobieranie aktualizacji…",
pasek do 30 %), wysyła `POST API_USTAWIENIA` z `action=do_update`. Sukces →
pasek 100 %, „Gotowe! Odświeżam panel…", toast „Zaktualizowano do wersji X" i
`window.location.reload()` po 1200 ms; porażka/błąd → chowa postęp, odblokowuje
przycisk i pokazuje toast `error`.

### 1.8 Link do Google Calendar

W `core.js` znajduje się wyłącznie komentarz zapowiadający
„Domyślne ustawienia Google API: zawsze szybki link (wyłącznie), bez konfiguracji" —
faktyczna implementacja (`generateGoogleCalendarLink`, `convertToUTCFormat`,
`openGoogleCalendar`) znajduje się w `api.js` (sekcja 2), a wywołania z listy
przez `openGoogleCalendarFromId` w `lista.js`.

### 1.9 Wykrywanie układu urządzenia (mobile vs desktop)

#### `detectMobile()`
Zwraca `true`, gdy `navigator.userAgent` pasuje do wzorca
`/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i`,
albo gdy urządzenie jest dotykowe (`ontouchstart` / `maxTouchPoints > 0`)
i `window.innerWidth <= 1024`. Wynik zapisywany jest raz w stałej `IS_MOBILE`.

#### `applyDeviceLayout()`
Przełącza klasę `is-mobile` na `<body>` (reszta ukryć listy/kafli/filtrów idzie
przez CSS), ukrywa `#save-print-calendar-btn` na mobile, `#save-only-btn` na
desktopie oraz ukrywa wgrywanie zdjęć (`#photo-upload-group` i
`#photos-modal-input-group`) na desktopie. Funkcja wywoływana jest przy załadowaniu
pliku oraz wewnątrz `applyModules()`.

### 1.10 Nakładka „Zapisywanie"

#### `showUploading(show)`
Zarządza `#upload-overlay` i blokuje `#save-print-calendar-btn` oraz
`#save-only-btn` (zapobiega podwójnemu kliknięciu). Przy chowaniu nakładka nie
znika od razu: dostaje klasę `leaving`, a `uploadHideTimer` (1050 ms) usuwa
klasy `active`/`leaving` na końcu sekwencji animacji (rowerzysta „odjeżdża"
za krawędź ekranu).

### 1.11 Tabela funkcji `core.js`

| Funkcja | Opis |
|---|---|
| `apiFetch(url, options)` | Wrapper `fetch` obsługujący 401 (toast + reload po 1200 ms + `throw`). Bez tokenu CSRF. |
| `modulOn(nazwa)` | Sprawdza, czy moduł jest włączony (`!== false`), nieznana flaga = włączona. |
| `loadModules()` | Pobiera flagi `MODULY` z `GET api/ustawienia.php`; błąd = flagi domyślne. |
| `saveModules()` | Zapisuje `action=modules` + JSON flag do `api/ustawienia.php`, aktualizuje `MODULY`. |
| `updateSaveButtons()` | Dobiera widoczność/etykiety przycisków zapisu do modułów `druk`/`kalendarz` i `IS_MOBILE`. |
| `applyModules()` | Stosuje flagi: klasy `off-*` na `<body>`, `required` przy datach, reset filtrów/sortowań, akcent, układ urządzenia, stany `active`. |
| `showUploading(show)` | Nakładka „Zapisywanie" + blokada przycisków, opóźnione ukrycie (1050 ms). |
| `detectMobile()` | Detekcja mobile po UA i dotyku + szerokości ≤ 1024 px. |
| `applyDeviceLayout()` | Klasa `is-mobile` na `<body>` i przełączanie widoczności przycisków/zgrupowań zdjęć. |
| `showConfirmModal(title, message, okLabel)` | Modal potwierdzenia zwracający `Promise<boolean>`. |
| `closeConfirmModal(result)` | Zamyka modal i rozwiązuje `Promise` wartością `true`/`false`. |
| `checkForUpdate()` | Sprawdza dostępność nowej wersji (cache 24 h), ustawia baner i przycisk wg roli admina. |
| `forceCheckUpdate()` | Wymuszone `action=check_update`, obsługa toastów i stanu przycisku. |
| `openUpdateModal()` | Otwiera modal aktualizacji z informacją „Z wersji … do wersji …". |
| `closeUpdateModal()` | Zamyka modal aktualizacji (Anuluj/krzyżyk/tło). |
| *(handler `#update-modal-ok`)* | Wysyła `action=do_update`, pokazuje pasek postępu i przeładowuje panel po sukcesie. |
| *(blokada `DOMContentLoaded`)* | Motyw, `IS_AUTHENTICATED`, moduły, daty, `loadServices`, pobranie `db`, `renderServicesList`, powitanie, `checkForUpdate`. |

---

## 2. `api.js` — zapis do bazy, dane formularza, Google Calendar

### 2.1 `apiFetch` — uwaga lokalizacyjna

Wrapper `apiFetch` (obsługa 401, brak CSRF) jest **zdefiniowany w `core.js`**
(sekcja 1.1); `api.js` wyłącznie go wywołuje. Autoryzacja = sesja PHP (cookie);
żadne wywołanie w tym pliku nie dokłada nagłówka ani pola CSRF.

### 2.2 Endpointy wywoływane z poziomu `api.js`

Jedyny endpoint używany bezpośrednio: `POST api/zgloszenia.php` w `saveItemToDB`.
Pozostałe wywołania HTTP panelu (dla kompletności): `GET/POST api/ustawienia.php`
(moduły, `check_update`, `do_update`), `GET/POST/DELETE api/zgloszenia.php`
(lista, `create`, `confirm`, `status`, `restore`, kosz, purge) — te znajdują się
w `core.js` i `lista.js`.

### 2.3 Funkcje

#### `saveItemToDB(item, files)`
Tworzy `FormData` z `action=create` oraz polami `bike_name`, `date_in`,
`date_planned`, `customer_phone`, `fault_description`, `status`, `source`
(`'mobile'` gdy `IS_MOBILE`, w przeciwnym razie `'desktop'`) i — jeśli `files`
ma elementy — `photos[]` dla każdego pliku. Pole `services_done` jest **pomijane**
(zgodnie z komentarzem: przyjęcie to zakres prac, nie stan wykonania). Po
`POST API_ZGLOSZENIA` przy `!data.success` rzuca `Error(data.error ||
'Nie udało się zapisać zgłoszenia.'),` w przeciwnym razie wstawia nowy rekord
na początek `db` (`db.unshift(data.data)`), wywołuje `renderServicesList()`
i zwraca zapisany obiekt.

#### `getFormData()`
Buduje obiekt danych z pól formularza: `bikeName`, `dateIn`, `datePlanned`,
`customerPhone`, `faultDescription`, `status` (zawsze `'in_progress'` na przyjęciu)
oraz `servicesDone: []`. Ręczny opis z `#fault-description` jest łączony
z zaznaczonymi checkboxami usług — każde zaznaczenie trafia jako linia „- „
pod opisem (po pustej linii), a gdy opisu brak, sam blok usług staje się opisem.

#### `resetForm()`
Czyści `#bike-name`, `#fault-description`, `#customer-phone`, `#photos-input`,
`#camera-input` oraz odznacza wszystkie `.service-checkbox:checked`. Ustawia datę
przyjęcia na dziś, a termin (gdy `modulOn('kalendarz')`) na **+2 dni** — uwaga:
startowa inicjalizacja w `core.js` używa +3 dni, `resetForm` +2 dni.

#### `generateGoogleCalendarLink(item)`
Składa link `https://calendar.google.com/calendar/render` z parametrami
`action=TEMPLATE`, `text=` „🔧 Serwis: <nazwa roweru>", `details=` (blok
„KLIENT: <telefon>", „DATA PRZYJĘCIA: <data>", „OPIS USTERKI: <opis>"),
`dates=` w formacie całodniowym `start/koniec` (koniec = `datePlanned` + 1 dzień),
oraz `sf=true`, `output=xml` i `remind=` (pusty `remind` nadpisuje domyślne reguły
powiadomień konta Google i wyłącza przypomnienia).

#### `convertToUTCFormat(localDateStr)`
Konwertuje datę `"YYYY-MM-DD"` na `"YYYYMMDD"` przez usunięcie myślników
(`replace(/-/g, '')`) — format tekstowy wydarzeń całodniowych Kalendarza Google;
dla pustego wejścia zwraca `''`.

#### `openGoogleCalendar(item)`
Otwiera wygenerowany link w nowej karcie (`window.open(link, '_blank')`) i pokazuje
toast informacyjny „Otwarto okno dodawania do Kalendarza Google!".

### 2.4 Helpery telefonu — status w tym pliku

`api.js` **nie zawiera** helperów telefonu. Są one zdefiniowane w `motyw.js`
i podpięte w innych plikach:

- `formatPhone(value)` (`motyw.js`) — usuwa niedziesiętne znaki, ogranicza do 9 cyfr
  i wstawia myślniki co 3 cyfry (`500-600-700`; komentarz w kodzie: „500 600 700 →
  500-600-700"). Podpięty w `druk.js` na `input` pola `#customer-phone`
  (z odtworzeniem pozycji kursora przez `setSelectionRange`).
- `validatePhone(phone)` (`motyw.js`) — usuwa niedziesiętne znaki i wymaga
  `digits.length >= 9`. Wywoływane w `validateForm()` w `formularz.js`
  (komunikat: „Wprowadź poprawny numer telefonu (min. 9 cyfr)!").

### 2.5 Tabela funkcji `api.js`

| Funkcja | Opis |
|---|---|
| `saveItemToDB(item, files)` | `POST api/zgloszenia.php` z `action=create`, zdjęciami `photos[]` i `source`; uzupełnia `db`, renderuje listę, zwraca rekord. |
| `getFormData()` | Zamienia pola formularza na obiekt zgłoszenia (status `in_progress`, usługi w opisie jako linie „- „, `servicesDone=[]`). |
| `resetForm()` | Czyści pola i checkboxy, ustawia datę przyjęcia = dziś oraz termin +2 dni (gdy kalendarz włączony). |
| `generateGoogleCalendarLink(item)` | Buduje link Kalendarza Google (całodniowy, `remind=''`, tytuł „🔧 Serwis: …", szczegóły z telefonem/datą/opisem). |
| `convertToUTCFormat(localDateStr)` | `"2026-07-14"` → `"20260714"` dla wydarzeń całodniowych. |
| `openGoogleCalendar(item)` | Otwiera link w nowej karcie + toast informacyjny. |

---

## 3. `lista.js` — rendering listy zgłoszeń

### 3.1 Wyszukiwanie

#### `itemMatchesSearch(item)`
Zwraca `true` przy pustym `searchQuery`; inaczej porównuje małe litery zapytania
z polami: `bikeName`, `customerPhone`, `faultDescription`, `serviceNo`
(numer serwisowy), `serviceNotes` (notatki) oraz spójną listą `servicesDone`.
To jedyne kryterium tekstowe używane na liście (poza wyszukiwarką mobilną
z `filtry.js`, która działa po `serviceNo`).

### 3.2 Plakietki użytkowników

#### `userChipColor(login)`
Prosty hash `h = (h * 31 + code) % 360` po znakach loginu daje stały kolor
`hsl(<h>, 58%, 42%)` — niezależny od motywu, więc ten sam login zawsze ma ten sam
odcień.

#### `userChip(login, emptyTitle)`
Zwraca HTML kółka z inicjałem (pierwszy znak loginu w wielkości liter) z tytułem
`title=login`; bez loginu renderuje szare `user-chip-empty` z treścią „—"
(np. rekord sprzed wdrożenia użytkowników). Treść jest przepuszczana przez
`escapeHtml`.

### 3.3 `renderServicesList()` — główna funkcja renderująca

Przebudowuje `#services-list-container` od zera i na początku wywołuje
`updateFilterCounts()`. Przebieg:

1. **Filtrowanie `db`:** przy `currentFilter === 'trash'` zostają tylko rekordy
   z `item.deleted`; poza koszem rekordy skasowane odpadają, a dalej filtry:
   `'tomorrow'` → `isPlannedTomorrow`, `'today'` → `isPlannedToday`,
   `'overdue'` → `isOverdue`, `'mine'` → `item.createdById === USER_ID`
   (zgłoszenia założone przez bieżącego użytkownika), `'all'` → bez ograniczenia
   statusu, inaczej `item.status === currentFilter`. Na końcu każdy element
   przechodzi `itemMatchesSearch`.
2. **Sortowanie:** `Intl.Collator('pl')` i mapa `sorters` z kluczami
   `planned_asc`, `planned_desc`, `dateIn_desc`, `dateIn_asc`, `name` (po
   `bikeName`), `status` (wg `STATUS_ORDER`, dalej `datePlanned`), `user`
   (wg `createdBy`), `issuer` (wg `confirmedBy`) — rekordy bez autora (`null`)
   idą na koniec przez znak `U+FFFD`. Równorzędność rozstrzyga malejące `id`.
3. **Pusty stan (trzy warianty):** bez kryteriów — ikona 🚲 i tekst
   „Tu pojawią się przyjęte rowery. Zacznij od formularza „Przyjmij nowy rower".",
   filtr `trash` — 🗑️ „Kosz jest pusty. Usunięte zgłoszenia trafią tutaj i będzie
   można je przywrócić.", inaczej — 🔍 „Żadne zgłoszenie nie pasuje do wyszukiwania
   ani filtru. Zmień kryteria.".
4. **Karta zgłoszenia (`div.service-item-card`):** klasy
   `card-<status>` + `pending-card` (gdy `!item.confirmed && !item.deleted`)
   + `is-deleted`, atrybut `data-id`. Etykiety statusu: `deleted` → „W koszu",
   `in_progress` → „W serwisie", `completed` → „Gotowy", `picked_up` → „Odebrany".
   Odznaka `.status-badge` klikalna wywołuje `cycleStatus(id)` (tylko poza koszem).
5. **Zdjęcia:** miniatury `.photo-thumb` z podglądem `openLightbox(url)`
   tylko gdy `modulOn('zdjecia')`.
6. **Przyciski akcji:** „Kalendarz" (`openGoogleCalendarFromId`) tylko gdy
   `!IS_MOBILE && modulOn('kalendarz')`, „Drukuj" (`printFromId`) tylko gdy
   `!IS_MOBILE && modulOn('druk')`, „Zdjęcia (n)" (`openPhotosModal`) gdy
   `modulOn('zdjecia')`, „Edytuj" (`openEditModal`), kosz (`deleteItem`) gdy
   `modulOn('kosz')`; w koszu zamiast tego: podgląd zdjęć + „Przywróć"
   (`restoreItem`) + „Usuń trwale" (`purgeItem`).
7. **Treść karty:** nagłówek z nazwą roweru i opcjonalnym numerem serwisowym
   (`.service-no`), link `tel:` z telefonem, wiersz „Przyjęto:" z datą i plakietką
   kto przyjął (`createdBy`), wiersz „Termin:" z datą i odznaką
   `.overdue-badge` „Po terminie" (gdy `isOverdue`, tylko gdy `modulOn('kalendarz')`),
   opis usterki `.fault-desc`, opcjonalny blok „Notatki:" (`serviceNotes`,
   `white-space: pre-wrap`) i miniatury zdjęć.
8. **Plakietki „kto przyjął / kto wydał":** `intakeHtml` = kółko + login
   `createdBy` w `.chip-inline` (bez autora — szare „—" z tytułem
   „Konto sprzed wdrożenia użytkowników"); `issuerHtml` = kółko `confirmedBy`
   z tytułem „Rower wydał: <login>" doklejone obok odznaki statusu w `.status-wrap`.
9. **Maska oczekiwania (`pending-mask`):** gdy zgłoszenie nie zostało potwierdzone,
   doklejana jest przesłona z notą „Zgłoszenie zablokowane — wymaga potwierdzenia
   na komputerze" (mobile) / „wymaga potwierdzenia" (desktop) oraz przyciskiem
   „Potwierdź" (`confirmItem(id)`) widocznym **wyłącznie na desktopie**.

**Paginacja / lazy loading: nie występuje** — wszystkie dopasowane rekordy są
renderowane synchronicznie w jednej operacji, bez stronicowania i bez doładowywania.

### 3.4 Kliknięcie w kartę → szczegóły

Delegowany `click` na `servicesListContainer` (`#services-list-container`):
ignoruje zdarzenia z `a, button, .status-badge, .pending-mask, input, label`
(przez `e.target.closest(...)`), a następnie z najbliższego `.service-item-card`
czyta `data-id` i wywołuje `openDetailModal(id)` (zdefiniowane w `karta.js`).

### 3.5 Akcje na rekordach

#### `window.confirmItem(id)`
Potwierdzenie zgłoszenia przyjętego na telefonie: wysyła `action=confirm`
z `id` do `API_ZGLOSZENIA`, ustawia `item.confirmed = true`, renderuje listę,
pokazuje toast „Zgłoszenie potwierdzone i odblokowane!" i uruchamia
`printAndAddToCalendar(item)` (kalendarz i druk — części wyłączone modułami
pomijane). Bez rekordu lub przy już potwierdzonym nic nie robi.

#### `window.cycleStatus(id)`
Przełącza status w cyklu `in_progress → completed → picked_up → in_progress`
przez `action=status` (pole `status`). Po sukcesie podmienia cały rekord z
`data.data` (serwer zwraca pełny obiekt — zmienia się też „kto wydał"
=`confirmedBy`), renderuje listę i pokazuje toast
„Zmieniono status roweru: <nazwa>". Gdy nowy status to `picked_up` i włączone są
moduły `druk` oraz `karta_wydania`, a urządzenie to desktop — drukuje kartę
wydania przez `triggerPrint(item, 'wydanie')`.

#### `window.openGoogleCalendarFromId(id)`
Znajduje rekord w `db` po `id` i przekazuje go do `openGoogleCalendar(item)`.
Dostępny tylko z poziomu listy desktopowej (przycisk „Kalendarz").

#### `window.printFromId(id)`
Drukuje dla znalezionego rekordu: po wydaniu (`status === 'picked_up'`)
kartę wydania (`'wydanie'`), a wcześniej potwierdzenie przyjęcia (`'przyjecie'`).

#### `window.deleteItem(id)` (kosz)
Nie działa, gdy `!modulOn('kosz')`. Potwierdza przez `showConfirmModal`
(„Przenieś do kosza", opis z nazwą roweru i informacją, że trwałe usunięcie
znajduje się w koszu, przycisk „Do kosza"), następnie `DELETE
api/zgloszenia.php?id=<id>`, ustawia `item.deleted = true`, renderuje listę
i pokazuje toast „Zgłoszenie przeniesione do kosza." (`info`).

#### `window.restoreItem(id)` (kosz)
Nie działa bez modułu `kosz`. Wysyła `action=restore` z `id`, ustawia
`item.deleted = false`, renderuje listę i pokazuje toast
„Przywrócono zgłoszenie z kosza.".

#### `window.purgeItem(id)` (kosz, trwałe usunięcie)
Nie działa bez modułu `kosz`. Pyta przez `showConfirmModal` („Usuń trwale",
ostrzeżenie o nieodwracalności wraz ze zdjęciami, przycisk „Usuń trwale"),
wysyła `DELETE api/zgloszenia.php?id=<id>&purge=1`, usuwa rekord z `db`
(`db = db.filter(...)`), renderuje listę i pokazuje toast
„Usunięto zgłoszenie trwale." (`info`).

### 3.6 Podświetlenia: terminy i „po terminie"

Funkcje terminowe żyją w `formularz.js` (patrz sekcja 4), a `lista.js` ich używa:
`isOverdue(item)` steruje klasą/badge „Po terminie" na wierszu „Termin:",
`isPlannedToday` / `isPlannedTomorrow` obsługują filtry `'today'` / `'tomorrow'`.
Rekordy skasowane, odebrane (`picked_up`) oraz bez `datePlanned` nigdy nie są
uważane za spóźnione ani zaplanowane.

---

## 4. `formularz.js` — formularz przyjęcia roweru i liczniki

### 4.1 Pola formularza

Referencje do pól pochodzą z `core.js` (sekcja 1.3): `#service-form`,
`#bike-name`, `#date-in`, `#date-planned`, `#customer-phone`,
`#fault-description`, `#photos-input` (pliki), `#camera-input` (aparat),
a także checkboxy `.service-checkbox` (katalog usług z `#service-checkbox-list`)
oraz przycisk `#save-only-btn`.

### 4.2 Walidacja

#### `validateForm()`
Trzy warunki, każdy z toastem typu `info` i `focus()` na polu:
1. `#bike-name` niepusty — inaczej „Wprowadź nazwę roweru!";
2. `validatePhone(#customer-phone)` (min. 9 cyfr) — inaczej
   „Wprowadź poprawny numer telefonu (min. 9 cyfr)!";
3. `getFormData().faultDescription` niepusty (opis ręczny **lub** zaznaczone
   usługi) — inaczej „Opisz usterkę lub zaznacz wykonywane usługi!".
Zwraca `true` tylko gdy wszystkie trzy spełnione. Walidacja daty planowanej
obsługiwana jest natywnie przez atrybut `required` ustawiany w `applyModules()`.

### 4.3 Przepływ zapisu

#### Handler `submit` `#service-form`
`preventDefault()` → `validateForm()` → `getFormData()` → zapamiętanie liczby
zdjęć (`selectedFiles.length`) → `showUploading(true)` → `saveItemToDB(item,
selectedFiles)` → `showUploading(false)`, `resetForm()`, wyczyszczenie
`selectedFiles` i `renderPhotoPreviews()`. Na **mobile**: pełnoekranowy monit
`showPhoneSuccess(photoCount)` (kalendarz i druk nastąpią po potwierdzeniu na
komputerze) oraz, gdy były zdjęcia, `notifyFotoWarn()` (toast o progu 80 % limitu).
Na **desktopie**: toast „Zapisano zgłoszenie rowerowe w bazie!", potem
`notifyFotoWarn()`, a następnie wspólna akcja `printAndAddToCalendar(saved)`.
Błąd zapisu → `showUploading(false)` + toast `error` z komunikatem wyjątku.

#### Handler kliknięcia `#save-only-btn`
Analogiczny przebieg bez druku i kalendarza (stąd osobny przycisk „Zapisz tylko
w bazie", widoczny na desktopie według `applyDeviceLayout`): walidacja,
`saveItemToDB`, reset, na mobile `showPhoneSuccess`, na desktopie toast
„Zapisano zgłoszenie w bazie serwisu!", błąd → toast `error`.

### 4.4 Helpery terminów (używane też przez listę, kafle i powitanie)

#### `isOverdue(item)`
`false` gdy rekord skasowany, odebrany (`picked_up`) lub bez `datePlanned`;
inaczej `item.datePlanned < formatDateForInput(new Date())` — porównanie
lexykograficzne dat ISO.

#### `isPlannedToday(item)`
Jak wyżej, ale równość `datePlanned` z dzisiejszą datą (format `YYYY-MM-DD`).

#### `isPlannedTomorrow(item)`
Jak wyżej, ale równość `datePlanned` z datą jutrzejszą (dzisiejsza + 1 dzień).

### 4.5 Liczniki filtrów i dashboard

#### `setFilterCount(id, count, variant)`
Ustawia treść licznika w elemencie o podanym `id`, ukrywa go przy zerze
(`el.hidden = count === 0`) i nadaje klasę `filter-count` (+ `variant`).

#### `updateFilterCounts()`
Licznik `#count-trash` = liczba rekordów z `item.deleted` (wariant
`count-neutral`), a następnie wywołanie `updateDashboard()`. Wywoływane z
`renderServicesList()` przy każdym renderze.

#### `updateDashboard()`
Wypełnia kafle podsumowań nad listą z puli **nieusuniętych** (`live`):
`#dash-in-progress` (status `in_progress`), `#dash-completed` (`completed`),
a z pełnej puli `db`: `#dash-overdue` (`isOverdue`), `#dash-today`
(`isPlannedToday`), `#dash-tomorrow` (`isPlannedTomorrow`). Dodatkowo przełącza
klasę `active` na `.dash-tile`, którego `dataset.filter` równa się `currentFilter`.

### 4.6 Powitanie po zalogowaniu

#### `pokazPowitanie()`
Wypełnia modal „Podsumowanie dnia": `#welcome-today` (liczba odbiorów na dziś),
`#welcome-tomorrow` (na jutro) oraz `#welcome-overdue`, ukryty przy zerze, a
przy liczbach > 0 pokazujący „Po terminie: N". Następnie dodaje klasę `active`
do `#welcome-modal`. Wywoływane tylko z `core.js`, gdy jest flaga `?powitanie=1`
i włączone moduły `powitanie` oraz `kalendarz`.

#### `zamknijPowitanie()`
Zdejmuje klasę `active` z `#welcome-modal`; podpięte pod `#welcome-modal-ok`
i `#welcome-modal-close`.

---

## 5. `filtry.js` — filtry, wyszukiwarka, sortowanie, karta mobilna

### 5.1 Wyszukiwarka

#### Handler `input` na `#search-input`
Przepisuje wartość do globalnego `searchQuery`, wywołuje `renderServicesList()`
i `scheduleMobileCard()`.

#### `openSearchCard()` (mobile)
Działa wyłącznie gdy `IS_MOBILE`; wymaga **min. 4 znaków** zapytania. Filtruje
`db` po nieusuniętych rekordach z `serviceNo` zawierającym zapytanie (case
insensitive): brak dopasowań → toast `error` „Brak zgłoszenia o numerze X.";
dopasowanie dokładne (`serviceNo === q`) lub jedyne dopasowanie →
`openDetailModal(id)`; kilka dopasowań → toast `info`
„Znaleziono N zgłoszeń — wpisz więcej cyfr numeru." i karty nie otwiera.
Na desktopie funkcja nic nie robi.

#### `scheduleMobileCard()`
Na mobile opóźnia `openSearchCard` o **700 ms** (kasuje poprzedni timer
`mobileCardTimer`), żeby nie otwierać karty przy każdym wciśniętym znaku.

#### Handler `keydown` na `#search-input` (Enter)
`preventDefault()`, anuluje odliczanie `mobileCardTimer` i otwiera kartę
natychmiast (`openSearchCard()`).

### 5.2 Filtry przyciskowe

#### Handler `click` na `.filters .filter-btn`
Zdejmuje `active` ze wszystkich przycisków, dodaje klikniętemu, ustawia
`currentFilter = btn.dataset.filter` i odświeża listę przez
`renderServicesList()`. Zestaw filtrów wynika z `data-filter` w HTML;
obsługiwane wartości rozpoznawane w `renderServicesList`: `all`, `trash`,
`today`, `tomorrow`, `overdue`, `mine` oraz statusy `in_progress`,
`completed`, `picked_up`.

### 5.3 Kafle dashboardu

#### Handler `click` na `#dash-grid`
Z najbliższego `.dash-tile` czyta `dataset.filter`, ustawia `currentFilter`,
synchronizuje klasę `active` na przyciskach `.filter-btn`, renderuje listę
i przewija widok do `#services-list-container` (`scrollIntoView`, płynnie,
do góry). Kafle pełnią rolę skrótu do filtrów; ich liczby uzupełnia
`updateDashboard()` z `formularz.js`.

### 5.4 Sortowanie

Na starcie odczytywana jest preferencja `localStorage.re_sort` (jeśli odpowiada
istniejącej `option` w `#sort-select`) i przypisywana do `currentSort`;
następnie `sortSelect.value = currentSort`. Handler `change` zapisuje wybór do
`localStorage.re_sort` (w `try/catch`) i odświeża listę. Dostępne klucze sortowań
i ich znaczenia opisano w `renderServicesList` (sekcja 3.3); opcje zaczynające
się od `planned` są ukrywane, gdy wyłączony jest moduł `kalendarz`
(`applyModules()` w `core.js`).

### 5.5 Karta harmonogramu / mobilna karta wyniku wyszukiwania

W tym pliku **nie ma osobnego „karty harmonogramu"** — mobilnym odpowiednikiem
listy jest karta zgłoszenia otwierana przez `openSearchCard()` (wyszukiwanie
po numerze serwisowym, min. 4 znaki, debounce 700 ms lub Enter), która korzysta
z `openDetailModal(id)`. Terminowe kafle („Jutro", „Po terminie") obsługują
`updateDashboard()` z `formularz.js`.

---

## 6. `druk.js` — drukowanie, QR, zdjęcia, monit telefoniczny

### 6.1 Wspólna akcja „zapisz + druk + kalendarz"

#### `printAndAddToCalendar(item)`
Kolejność: najpierw `openGoogleCalendar(item)` jeśli `modulOn('kalendarz')`,
następnie `triggerPrint(item)` jeśli `modulOn('druk')`. Wywoływane z
`formularz.js` (desktop po zapisie) oraz `lista.js` (po potwierdzeniu
zgłoszenia z telefonu).

### 6.2 Mechanizm drukowania

#### `triggerPrint(item, tryb)`
Tryby: `'przyjecie'` (domyślny — potwierdzenie przyjęcia roweru) oraz
`'wydanie'` (karta wydania roweru). Co robi:

- **Tytuły:** `#print-title-client` = „Karta Wydania Roweru" /
  „Potwierdzenie Przyjęcia Roweru"; `#print-title-service` =
  „Karta Wydania Roweru - Egzemplarz Serwisu" /
  „Zlecenie Serwisowe - Egzemplarz Serwisu".
- **Szablon A4 (egzemplarz klienta):** `#print-bike-name`, `#print-date-in`
  (`formatDateForUser`), `#print-customer-phone`, `#print-fault-description`,
  `#print-service-no` (`item.serviceNo || '—'`).
- **Szablon strony 2 (egzemplarz serwisu):** `#print-bike-name-service`,
  `#print-date-in-service`, `#print-customer-phone-service`,
  `#print-fault-description-service`, `#print-service-service-no` —
  w kodzie: `#print-service-no-service`.
- **Wykonane czynności:** lista `getDoneServices(item)` (funkcja z `karta.js`)
  pokazywana wyłącznie gdy tryb to `'wydanie'` **i** `modulOn('wykonane')`;
  pozycje drukowane są jako „☑ <nazwa>" (`innerHTML` z `escapeHtml`).
  Przy pustej liście ukrywane są wiersz `#print-done-row-client`,
  komórka `#print-done-cell-client`, treść `#print-done-client`, a także
  `#print-done-title-service` i `#print-done-service`.
- **Notatki:** `#print-service-notes` = `item.serviceNotes` lub „—"; blok
  `#print-notes-block` przełączany przez `style.display` (`flex`/`none`,
  bo `display` ma inline flex przez który `[hidden]` nie zadziałałby),
  a `#print-notes-spacer` ukrywany, gdy notatki istnieją.
- **Kody QR:** obsługa `#qr-code-canvas`, `#qr-code-img` oraz
  `#qr-service-canvas` (szczegóły niżej).
- **Druk:** lokalna funkcja `executePrint()` wywołuje `window.print()` po 150 ms;
  wywoływana natychmiast po rysowaniu na canvasie albo w `qrImg.onload`
  / `qrImg.onerror` w gałęzi awaryjnej.

### 6.3 Kod QR — generowanie

Główna ścieżka: gdy biblioteka **QRious** (CDN) jest dostępna
(`typeof QRious !== 'undefined'`), pokazywany jest `#qr-code-canvas`
(ukrywany `#qr-code-img`) i rysowany kod o zawartości
`window.APP_CFG.mapsUrl` (link map/lokalizacji serwisu), `size: 150`,
`level: 'H'`, czarny foreground na białym tle. Dodatkowo, gdy rekord ma
`item.serviceNo`, rysowany jest drugi kod na `#qr-service-canvas`
z **numerem serwisowym** (etykieta do naklejenia na rower); bez numeru
canvas jest ukrywany.

Ścieżka awaryjna (`catch`): przy braku QRious lub błędzie rysowania kod QR
pobierany jest z zewnętrznego API `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<mapsUrl>`
do `#qr-code-img` (canvas ukryty, `#qr-service-canvas` ukryty); druk odbywa się
w `onload`, a przy błędzie pobierania — mimo wszystko w `onerror`
(komunikat `console.error`).

### 6.4 Google Calendar

Moduł ten tylko uruchamia integrację przez `printAndAddToCalendar` /
`openGoogleCalendarFromId`; budowa linku (`generateGoogleCalendarLink`,
`convertToUTCFormat`) opisana jest w sekcji 2 (`api.js`).

### 6.5 Formatowanie telefonu

#### Handler `input` na `#customer-phone`
Przechwytuje pozycję kursora, formatuje wartość przez `formatPhone`
(z `motyw.js`: same cyfry, maks. 9, myślniki co 3) i przywraca kursor
przez `setSelectionRange` z korektą o różnicę długości.

### 6.6 Zdjęcia w formularzu nowego zgłoszenia

#### `selectedFiles` (tablica lokalna)
Bufor wybranych plików przed wysyłką do `saveItemToDB`.

#### Handler `input change` na `#photos-input` i `#camera-input`
Dokleja `Array.from(input.files)` do `selectedFiles`, czyści pole wejściowe
(żeby ten sam plik mógł być wybrany ponownie) i wywołuje `renderPhotoPreviews()`.

#### `renderPhotoPreviews()`
Przebudowuje kontener `#photo-previews`: dla każdego pliku `FileReader`
czyta go jako data URL i tworzy podgląd `.photo-preview` z przyciskiem
`.remove-photo` („×", tytuł „Usuń z wyboru"). Usunięcie wycina element
z `selectedFiles`, odbudowuje `photosInput.files` przez obiekt `DataTransfer`
i renderuje ponownie.

### 6.7 Monit po zapisie z telefonu

#### `showPhoneSuccess(photoCount)`
Otwiera pełnoekranowy modal `#phone-success-modal` z tekstem
`#phone-success-msg`: gdy były zdjęcia, dopisuje „Dodano N zdjęcie/zdjęcia/zdjęć."
(poprawna odmiana dla 1, 2–4 i 5+), a dalej zdanie „Zgłoszenie jest zamazane
na liście do czasu **potwierdzenia na komputerze**", zakończone ogonem
zależnym od modułów: „ — tam uruchomi się też kalendarz", „… wydruk potwierdzenia
dla klienta", oba („kalendarz i wydruk potwierdzenia dla klienta") albo samo
kropkowane zakończenie, gdy oba moduły są wyłączone.

#### Handler `#phone-success-ok`
Zamyka modal (`classList.remove('active')`).

---

## Podsumowanie ról plików

| Plik | Główna rola |
|---|---|
| `core.js` | Stan globalny, endpointy, `apiFetch`, moduły (`MODULY`/`modulOn`/`loadModules`/`saveModules`/`applyModules`), modal potwierdzania, nakładka uploadu, detekcja urządzenia, start aplikacji, powitanie, aktualizacje panelu. |
| `api.js` | Zapis nowego zgłoszenia (`saveItemToDB`), budowa danych formularza (`getFormData`), reset formularza, link i konwersja dat dla Google Calendar. |
| `lista.js` | Rendering i filtrowanie listy, plakietki użytkowników, statusy/odznaki, akcje wierszy (potwierdzenie, cykl statusu, kosz, druk, kalendarz), kliknięcie karty → szczegóły. |
| `formularz.js` | Walidacja i przepływ zapisu formularza, helpery terminów, liczniki filtrów i dashboardu, modal powitania. |
| `filtry.js` | Wyszukiwarka (z kartą mobilną po numerze serwisowym), przyciski filtrów, kafle dashboardu, sortowanie z `localStorage`. |
| `druk.js` | Wydruk A4 (przyjęcie/wydanie), kody QR (QRious + API zapasowe), autoformat telefonu, podglądy zdjęć, monit po zapisie z telefonu. |

---

# Część VI — Frontend JS (2/2): karta, kalendarz, ustawienia, skaner

Poniższy fragment opisuje siedem plików JS z katalogu `assets/js/`:
`karta.js`, `kalendarz.js`, `ustawienia.js`, `uzytkownicy.js`, `motyw.js`, `skaner.js`, `zdjecia.js`.

Wspólne symbole definiowane w `core.js` (używane niemal wszędzie): `db` (tablica zgłoszeń),
`apiFetch()` (fetch z obsługą 401 i przeładowaniem sesji), `modulOn(nazwa)` (flaga modułu,
`MODULY[nazwa] !== false`), `MODULY` (10 flag: `kalendarz`, `zdjecia`, `skaner`, `uslugi`, `druk`,
`kosz`, `kolorystyka`, `powitanie`, `karta_wydania`, `wykonane`), `IS_MOBILE`, endpointy
`API_ZGLOSZENIA`, `API_ZDJECIA`, `API_KONTO`, `API_USLUGI`, `API_USTAWIENIA`, `API_UZYTKOWNICY`,
`showConfirmModal()`, `renderServicesList()` (`lista.js`), `updateDashboard()` / `isOverdue()`
(`formularz.js`), `triggerPrint()` (`druk.js`).

---

## 1. `assets/js/karta.js` — karta zgłoszenia (podgląd) i modal edycji

Plik zawiera **dwa modale**: modal edycji zgłoszenia (`#edit-modal`) oraz modal podglądu
(z „Wydaniem roweru", `#detail-modal`), a także autozapis notatek i zaznaczeń „wykonane czynności".

### 1.1 Stan i elementy modalu edycji

- `editModal` / `editForm` — elementy `#edit-modal` i `#edit-form`; modal edycji zgłoszenia wraz
  z formularzem wysyłanym do API.
- `editBikeNameInput`, `editDateInInput`, `editDatePlannedInput`, `editCustomerPhoneInput`,
  `editFaultInput`, `editServiceNotesInput` — pola formularza: nazwa roweru, data przyjęcia,
  termin planowany, telefon klienta, opis usterki, notatki serwisowe.
- `editModalId` (zmienna modułowa) — identyfikator aktualnie edytowanego zgłoszenia; `null`,
  gdy modal jest zamknięty.

### 1.2 Funkcje modalu edycji

- **`closeEditModal()`** — zamyka modal edycji (usuwa klasę `active`) i zeruje `editModalId`.
  Wywoływana z przycisku Anuluj, krzyżka, kliknięcia w tło modala i po nieudanym pobraniu rekordu.
- **`window.openEditModal(id)`** — otwiera modal edycji dla zgłoszenia o podanym `id`; wypełnia
  wszystkie pola danymi z `db` i ustawia focus na polu nazwy roweru. **Bramka danych:** jeśli
  wpis nie istnieje lub ma `item.deleted` (jest w koszu), funkcja wychodzi bez otwierania —
  wpisów z kosza się nie edytuje.
- **`editCustomerPhoneInput` — listener `input`** — przepuszcza każdą wartość przez
  `formatPhone()`, dzięki czemu numer jest formatowany na bieżąco do postaci `500-600-700`
  (maks. 9 cyfr, myślniki co 3 cyfry).
- **`editForm` — listener `submit`** — główny przepływ zapisu edycji:
  1. `e.preventDefault()`, wyjście przy `editModalId === null`;
  2. weryfikacja, że wpis nadal istnieje (brak → `closeEditModal()`);
  3. walidacja telefonu: `validatePhone()`; przy błędzie toast
     „Numer telefonu musi mieć co najmniej 9 cyfr." (`error`) i brak wysyłki;
  4. zbudowanie `FormData` z polami `action=update`, `id`, `bike_name`, `date_in`, `date_planned`,
     `customer_phone`, `fault_description`, `status` (**status przekazywany jest bez zmian** —
     edycja nie zmienia statusu), `service_notes`;
  5. `apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData })` → parsowanie JSON;
     przy `!data.success` rzucenie wyjątku z komunikatem serwera lub domyślnym
     „Nie udało się zapisać zmian.";
  6. podmiana rekordu w cache: `db[idx] = data.data` (serwer zwraca pełny, zaktualizowany rekord);
  7. `closeEditModal()`, `renderServicesList()`, toast „Zaktualizowano zgłoszenie.";
  8. blok `catch` → `showToast(err.message, 'error')`.
- **Listener kliknięć `#edit-modal-cancel`, `#edit-modal-close` oraz tło modala** — trzy
  niezależne ścieżki zamknięcia modalu edycji (`closeEditModal()`).

### 1.3 Wykonane czynności (checkboxy z katalogu usług)

- **`getDoneServices(item)`** — zwraca tablicę faktycznie wykonanych usług z
  `item.servicesDone` albo pustą tablicę, gdy pole nie jest tablicą (`null` = jeszcze nie
  zapisywano).
- **`getPlannedServices(item)`** — buduje listę „uzgodnionych przy przyjęciu" usług: bierze
  linie z `item.faultDescription` zaczynające się od `"- "`, odfiltrowuje je względem nazw
  z katalogu `services` (gdy katalog nie jest pusty), a następnie dokłada wszystko, co jest już
  zaznaczone jako wykonane (żeby nie zgubić stanu) — wynik to `Array.from(new Set(...))`
  (unikat, zachowanie kolejności).
- **`onDoneToggle(item)`** — obsługa kliknięcia w checkbox „wykonane czynności"; zbiera wartości
  wszystkich zaznaczonych pól `#detail-done-list input:checked`, zapisuje je optymistycznie do
  `item.servicesDone`, dokłada wpis do mapy `donePending` (z `prev` — stanem sprzed serii
  kliknięć, służącym do wycofania) i restartuje timer **debounce 450 ms** (`doneSaveTimer`).
- **`flushDoneSaves()`** — wysyła nagromadzone zaznaczenia: dla każdego wpisu z `donePending`
  (mapa wyczyszczona przed pętlą) POST `FormData { action=services, id, services_done:
  JSON.stringify(values) }` do `API_ZGLOSZENIA`; przy sukcesie ustawia
  `item.servicesDone = data.data.servicesDone`, a przy błędzie **cofniecie stanu optymistycznego**
  (`e.item.servicesDone = e.prev`), ponowne wyrenderowanie karty jeśli to ona jest otwarta
  (`fillDetailModal`) i toast błędowy.
- **Listener `input` na `#detail-notes`** — autozapis notatek: aktualizuje `item.serviceNotes`
  lokalnie (podgląd/wydruk od razu widzą zmianę), dopisuje do `notesPending` z `prev` i uruchamia
  debounce 450 ms (`notesSaveTimer`) na `flushNotesSaves()`.
- **`flushNotesSaves()`** — wysyła notatki: POST `{ action=notes, id, service_notes }` do
  `API_ZGLOSZENIA`; po sukcesie podmienia `item.serviceNotes` na wartość z odpowiedzi serwera,
  a przy błędzie przywraca `prev`, wpisuje wartość z powrotem do pola `#detail-notes` (gdy karta
  jest otwarta) i pokazuje toast.

### 1.4 Modal podglądu (`#detail-modal`)

- `detailModal`, `detailIssueBtn` (`#detail-issue-btn` — „Wydaj rower"), `detailModalId` —
  elementy i stan modala podglądu zgłoszenia (bez edycji pól, z akcją wydania).
- **`statusLabelFor(item)`** — mapuje rekord na etykietę statusu: `deleted` → „W koszu",
  `in_progress` → „W serwisie", `completed` → „Gotowy", `picked_up` → „Odebrany", inaczej `''`.
  Ta sama funkcja jest współdzielona z dymkiem kalendarza.
- **`fillDetailModal(item)`** — wypełnia całą kartę zgłoszenia:
  - `#detail-service-no` — numer serwisowy (tekst + `hidden`, gdy pusty);
  - `#detail-bike-name` — nazwa roweru; `#detail-status` — badge z `statusLabelFor()`;
  - `#detail-date-in` — data przyjęcia przez `formatDateForUser()`;
  - `#detail-date-planned` — termin planowany + sufiks **„— PO TERMINIE"**, gdy `isOverdue(item)`;
  - `#detail-phone` — link `tel:` z escapingiem (`escapeHtml`) telefonu klienta;
  - `#detail-fault` — opis usterki (lub „—" gdy pusty);
  - **„kto przyjął / kto wydał"**: `#detail-created-by` = `item.createdBy` (etykieta „Założył:")
    oraz `#detail-confirmed-by` = `item.confirmedBy` (etykieta „Wydanie:"), oba z domyślnym
    „—" dla starych rekordów sprzed wdrożenia użytkowników;
  - notatki `#detail-notes`: wartość `item.serviceNotes`, pole **wyłączone przy `item.deleted`**
    (w koszu tylko do odczytu), w przeciwnym razie aktywne z autozapisem;
  - **bramka wydania**: `canIssue = !item.deleted && item.status !== 'picked_up'`;
    przycisk `#detail-issue-btn` jest wtedy `disabled = !canIssue`, a jego tekst to
    „Wydaj rower" lub „Rower już wydany";
  - **wykonane czynności**: sekcja widoczna tylko gdy `modulOn('wykonane')` **oraz**
    `getPlannedServices(item).length > 0` (w innym wypadku chowane są `#detail-done-label`
    i `#detail-done-list`); dla każdej usługi tworzony jest `label.service-check` z
    `input.service-checkbox` (wartość = nazwa usługi, `checked` wg `getDoneServices`,
    `disabled = !canIssue`, listener `change → onDoneToggle(item)`), wszystko w kontenerze
    `span.service-checkbox-list`;
  - **informacja o potwierdzeniu**: `#detail-pending` pokazywany, gdy `!item.confirmed` i nie
    jest to kosz; tekst `#detail-pending-text` zależy od `IS_MOBILE`
    („Zgłoszenie zablokowane — wymaga potwierdzenia na komputerze" vs „…wymaga potwierdzenia").
- **`window.openDetailModal(id)`** — ustawia `detailModalId`, wywołuje `fillDetailModal(item)`
  i otwiera modal (`classList.add('active')`); nie otwiera modala dla nieistniejącego wpisu.
  To miejsce docelowe kliknięć w kalendarzu i wyniku skanu QR.
- **`closeDetailModal()`** — zamyka modal podglądu i zeruje `detailModalId`.
- **Listener `#detail-issue-btn` (wydanie roweru → status `picked_up`)**:
  1. twarda weryfikacja w `click`: brak `detailModalId`, brak wpisu, `item.deleted` lub już
     `picked_up` → wyjście;
  2. POST `FormData { action=status, id, status=picked_up }` do `API_ZGLOSZENIA`;
  3. przy `data.data` podmiana całego rekordu w `db` (serwer dopisuje **„kto wydał"**,
     `confirmedBy`) i użycie świeżej wersji do ponownego wyrenderowania; bez danych tylko
     `item.status = 'picked_up'`;
  4. `fillDetailModal(fresh)`, `renderServicesList()`, toast
     „Rower wydany klientowi: {bikeName}";
  5. jeśli `modulOn('druk') && modulOn('karta_wydania') && !IS_MOBILE` → natychmiastowy druk
     karty wydania: `triggerPrint(fresh, 'wydanie')`.
- **Listener `#detail-modal-close`, `#detail-cancel-btn` oraz kliknięcie w tło modala** —
  trzy ścieżki zamknięcia karty (`closeDetailModal()`).

### 1.5 Zdjęcia, kosz i uprawnienia — co faktycznie jest w tym pliku

- **Zdjęcia: w `karta.js` nie ma logiki zdjęć.** Podgląd i wgrywanie obsługuje `zdjecia.js`
  (`window.openPhotosModal`, `openLightbox`), a miniatury na liście generuje `lista.js`
  (przycisk „Zdjęcia (n)" widoczny przy `modulOn('zdjecia')`). Karta zgłoszenia nie zawiera
  sekcji zdjęciowej — nie jest ona częścią `#detail-modal`.
- **Kosz: w `karta.js` nie ma akcji kasowania.** `deleteItem()` (DELETE `API_ZGLOSZENIA`),
  `restoreItem()` (`action=restore`) i `purgeItem()` (DELETE z `purge=1`) żyją w `lista.js`
  i wszystkie bramkują się `modulOn('kosz')`. `karta.js` reaguje na stan `item.deleted`
  wyłącznie **stanie UI**: etykieta „W koszu", notatki tylko do odczytu, zablokowane wydanie,
  ukryta informacja „wymaga potwierdzenia".
- **Uprawnienia w UI:** `karta.js` nie sprawdza ról (rola admin/pracownik jest renderowana
  po stronie PHP w `partials/*`); bramkowanie w tym pliku to: moduły (`modulOn('wykonane')`,
  `modulOn('druk')`, `modulOn('karta_wydania')`), stan rekordu (`deleted`, `picked_up`,
  `confirmed`) oraz `IS_MOBILE` (blokada druku karty wydania na telefonie).

---

## 2. `assets/js/kalendarz.js` — kalendarz terminów (widok miesięczny)

### 2.1 Stan i stałe

- `calendarModal`, `calGrid`, `calTitle` — modal `#calendar-modal`, siatka dni `#cal-grid`
  i nagłówek miesiąca `#cal-title`.
- `MONTHS_PL` — tablica polskich nazw miesięcy (od „Styczeń" do „Grudzień") do tytułu modala.
- `calYear`, `calMonth` — aktualnie oglądany rok/miesiąc; inicjalizowane z `new Date()`.
- `STATUS_ORDER = { in_progress: 0, completed: 1, picked_up: 2 }` — klucz kolejności chipów
  w dniu: do wykonania → gotowe → odebrane.
- `calDayData` — mapa `data → [zgłoszenia]` wypełniana przez `renderCalendar()`, czytana przez
  dymek „więcej".

### 2.2 Funkcje i logika

- **`calendarItems()`** — zwraca wszystkie zgłoszenia widoczne w kalendarzu, czyli `db` bez
  wpisów z kosza (`!item.deleted`).
- **`calItemOrder(a, b)`** — comparator sortowania w dniu: najpierw wg `STATUS_ORDER`
  (nieznany status = 9), a przy remisie rosnąco po `id`.
- **`calHidePopover()`** — zamyka dymek „więcej" (usuwa klasę `open`).
- **`calHideSoon()`** — planuje zamknięcie dymka po **300 ms**; jeśli w tym czasie kursor
  znajdzie się nad dymkiem (`calPopover.matches(':hover')`), timer jest przekładany — dymek
  nie znika pod kursorem.
- **`calPlural(n)`** — poprawna polska odmiana rzeczownika dla liczby zgłoszeń:
  1 → „zgłoszenie", 2–4 (poza 12–14) → „zgłoszenia", inaczej → „zgłoszeń".
- **`calShowPopover(anchor)`** — buduje i pokazuje powiększony widok dnia:
  1. czyta `data-cal-day`, sortuje wpisy `calItemOrder`, przy pustej liście wychodzi;
  2. tytuł: data przez `toLocaleDateString('pl-PL', { day, month, weekday })` + liczba
     i odmiana „zgłoszeń";
  3. dla każdego wpisu przycisk `button.cal-pop-item` z klasą stanu: `s-done` (odebrany),
     `s-ready` (gotowy), `s-progress` (w serwisie) oraz dopiskiem **`is-overdue`**, gdy
     `status !== 'picked_up'` i `isOverdue(item)`; treść: nazwa roweru (przez `escapeHtml`)
     i wiersz meta `statusLabelFor(item) · {dataIn} → {dataPlanned}` (przez `formatDateForUser`);
  4. pozycjonowanie „od komórki dnia": `transformOrigin` ustawiane na `top`/`bottom` w
     zależności od miejsca pod komórką (przy braku miejsca na dole dymek rozwija się w górę),
     `left` przycięty do marginesu 8 px od krawędzi okna; zapisuje `calPopover.dataset.day`
     i znacznik czasu `calShownAt`.
- **`renderCalendar()`** — odrysowuje siatkę miesiąca:
  1. tytuł = `MONTHS_PL[calMonth] + ' ' + calYear`;
  2. tydzień zaczyna się od poniedziałku: `startOffset = (firstDay.getDay() + 6) % 7`;
  3. **grupowanie po dniach**: każde zgłoszenie „zajmuje" każdy dzień od `dateIn`
     (lub `datePlanned` przy braku `dateIn`) do `datePlanned` (gdy termin < daty przyjęcia —
     korekta do `from`), pętla dzienna z zabezpieczeniem `guard < 90` iteracji; dzięki temu
     wpis 25–27 jest widoczny na 25, 26 i 27;
  4. wyrenderowanie **42 komórek** (6 tygodni) klasy `cal-cell` z dopiskami `other-month`
     i `today` (porównanie z `formatDateForInput(new Date())`);
  5. w komórce: numer dnia `cal-daynum`, maks. **3 chipy** `button.cal-chip`
     (klasy: `chip-done` dla odebranych — na szaro; `chip-ready`/`chip-progress` dla pozostałych
     z dopiskiem **`chip-overdue`** przy `isOverdue`), `title` chipa zawiera nazwę roweru i zakres
     dat, a przy więcej niż 3 wpisach dodatkowy element `span.cal-more`
     („+N więcej", `data-cal-day`, `role="button"`, `tabindex="0"`, `aria-label`).
- **`openCalendarItem(id)`** — ukrywa dymek i zamyka modal kalendarza, a następnie otwiera
  **kartę podglądu zgłoszenia** (`openDetailModal(id)`); gdy wpis zniknął z `db`, pokazuje
  toast „To zgłoszenie nie jest już dostępne." (`error`). Uwaga: kliknięcie prowadzi do karty
  zgłoszenia, **nie** do osobnej przefiltrowanej listy — filtrowanie listy odbywa się
  niezależnie, przez filtry w `filtry.js`/`lista.js`.
- **`shiftCalendar(delta)`** — chowa dymek, przesuwa `calMonth` o `delta` z zawijaniem
  roku (0 ↔ 11) i przerysowuje widok; podpięte pod `#cal-prev-btn` / `#cal-next-btn`.

### 2.3 Zdarzenia (obsługa myszy, dotyku i klawiatury)

- **`#open-calendar-btn`** — otwiera modal kalendarza po resecie do **bieżącego** miesiąca
  i `renderCalendar()`.
- **`#calendar-modal-close` / kliknięcie w tło `#calendar-modal`** — chowa dymek i zamyka modal.
- **Klik w `calGrid`** — jeśli trafiono w `.cal-more`: przełączanie dymka na dotyku (zamykanie
  dopiero po `Date.now() - calShownAt > 600`, żeby tap nie zamywał świeżo otwartego dymka),
  w przeciwnym razie `calShowPopover(more)`; jeśli trafiono w `[data-cal-id]` (chip lub pozycję)
  → `openCalendarItem(parseInt(...))`.
- **`mouseover` / `mouseout` / `focusin` / `focusout` na `calGrid`** — otwieranie dymka po
  najechaniu lub ustawieniu fokusu na „więcej", chowanie (`calHideSoon`) po ich opuszczeniu
  (z kontrolą `relatedTarget`).
- **`mouseenter` / `mouseleave` na `calPopover`** — dymek pozostaje otwarty pod kursorem;
  kliknięcie w pozycję otwiera zgłoszenie, a kliknięcie w tło dymka (np. drugi tap) go zamyka.
- **`window` + `scroll` (faza capture)** — chowanie dymka przy przewijaniu, bo dymek jest
  „przyklejony" do komórki i traciłby punkt odniesienia.

---

## 3. `assets/js/ustawienia.js` — modal ustawień

### 3.1 Otwieranie, zamykanie i zakładki

- **`openSettingsModal()`** (strzałka const) — otwiera `#settings-modal`, od razu odświeża
  statystyki zdjęć (`loadPhotoStats()`) i synchronizuje przełączniki modułów
  (`syncModuleToggles()`). Jest to również handler kliknięcia w nazwę użytkownika
  w nagłówku (`#current-user`).
- **Listener `keydown` na `#current-user`** — otwiera ustawienia po `Enter` lub `Space`
  (element jest klikalny klawiaturą).
- **`closeSettingsBtn` / kliknięcie w tło `settingsModal`** — zamykają modal i czyści
  `passwordHintEl` (podpowiedź przy zmianie hasła).
- **`SETTINGS_TABS`** — tablica par `[nazwa, przycisk, treść]` dla zakładek:
  `general` („Ogólne"), `serwis` („Dane serwisu"), `uslugi` („Dodaj usługi"),
  `moduly` („Moduły"), `users` („Użytkownicy"). Przyciski/treści admina mogą nie istnieć
  w DOM u pracownika — dlatego wszędzie używany jest operator `?.`.
- **`switchSettingsTab(tabName)`** — przełącza zakładki: buduje mapę istniejących zakładek,
  wymusza powrót do `general`, gdy żądana zakładka nie istnieje (pracownik: `serwis`,
  `uslugi`, `moduly`, `users`) albo gdy `uslugi` jest wyłączone modułem
  (`!modulOn('uslugi')`); przełącza klasy `active` i `hidden`, a następnie ładuje dane:
  `serwis` → `loadInstDane()`, `uslugi` → `renderServiceList()`, `moduly` → `syncModuleToggles()`,
  `users` → `loadUsers()`.
- **Listener kliknięć przycisków zakładek** — `#tab-btn-general`, `#tab-btn-serwis`,
  `#tab-btn-uslugi`, `#tab-btn-moduly`, `#tab-btn-users` (ostatnie cztery przez `?.`).

### 3.2 Zakładka „Ogólne" (hasło + statystyki zdjęć)

- **Statystyki zdjęć**: `statsPhotosEl` (`#stats-photos`), `statsSizeEl` (`#stats-size`),
  `statsWarnEl` (`#stats-warn`), `refreshStatsBtn` (`#refresh-stats-btn`). Blok statystyk jest
  renderowany tylko dla admina (PHP), więc `loadPhotoStats()` **natychmiast wraca**, gdy
  `statsPhotosEl` nie istnieje.
- **`FOTO_WARN_PCT = 80`** — próg ostrzegawczy zużycia limitu zdjęć (procent).
- **`fotoWarnText(bytes)`** — zwraca tekst ostrzeżenia, gdy zużycie ≥ 80% `FOTO_LIMIT_MB`
  („Zdjęcia: zużyto X% limitu Y MB — zostało …. Usuń część starych zdjęć."), a `null` poniżej
  progu lub gdy limit nie jest skonfigurowany.
- **`photoStatsNow()`** — pobiera bieżące zużycie z `API_KONTO` i zwraca
  `{ photos, bytes }` albo `null` przy błędzie; używane do ostrzeżeń „na żywo".
- **`notifyFotoWarn()`** — pokazuje toast `⚠` z ostrzeżeniem o limicie; wywoływane **po**
  toaście „Zapisano", bo nowszy toast zastępuje poprzedni; poniżej progu nic nie wyświetla.
- **`loadPhotoStats()`** — pobiera statystyki z `API_KONTO` (GET), ustawia liczbę zdjęć,
  zużycie w formacie `formatBytes(bytes) + ' / ' + FOTO_LIMIT_MB + ' MB'`, pokazuje/chowa
  `#stats-warn` i przebarwia rozmiar na `var(--danger)` przy przekroczeniu progu; przy błędzie
  wpisuje „—" i toast „Nie udało się pobrać statystyk.".
- **`formatBytes(bytes)`** — formatuje rozmiar: `0 MB`, `… KB` (poniżej 1 MB, zaokrąglone,
  min. 1), `x,0 MB` z przecinkiem (do 100 MB, jedno miejsce po przecinku) albo pełne „MB"
  od 100 MB w górę.
- **Listener `#refresh-stats-btn`** → `loadPhotoStats()`.
- **Listener `#save-password-btn` (zmiana hasła własnego konta)** — walidacja lokalna
  (wszystkie trzy pola wypełnione; `next.length >= 6`; `next === confirm`), komunikaty w
  `passwordHintEl`, a następnie `fetch(API_KONTO, POST, application/x-www-form-urlencoded)`
  z `action=password&current=…&next=…` (`credentials: 'same-origin'`); sukces → czyszczenie pól,
  zielony hint „Hasło zostało zmienione." i toast „Hasło zmienione!", błąd → czerwony hint
  z `data.error`, wyjątek sieciowy → „Błąd połączenia.".
- **`logoutBtn` (`#logout-btn`)** — wylogowanie: `window.location.href = pathname + '?logout=1'`
  (koniec sesji dziennej po stronie serwera).

### 3.3 Zakładka „Dane serwisu" (dane instytucji)

- Elementy: `instAdres`, `instMiasto`, `instTelefon`, `instMaps`, `instSite`, `instHint`,
  `saveInstBtn` (`#inst-*`, `#inst-hint`, `#save-inst-btn`).
- **`loadInstDane()`** — pobiera konfigurację z `API_USTAWIENIA` (GET) i wypełnia pola
  z `data.data.dane_instancji` (`adres`, `miasto`, `telefon`, `maps_url`, `site_url`);
  przy braku elementów lub błędzie pomija bez komunikatu (pola zostają puste).
- **Listener `#save-inst-btn`** — zapis: hint „Zapisywanie…", blokada przycisku, wysyłka
  `URLSearchParams { action=dane_instancji, service_address, service_city, service_phone,
  google_maps_url, site_url }` POST-em do `API_USTAWIENIA`; sukces → hint
  „Zapisano. Zmiany widoczne od razu na wydruku." + toast „Dane serwisu zaktualizowane!",
  błąd → `data.error` lub „Błąd połączenia." w kolorze `var(--danger)`, w `finally`
  odblokowanie przycisku. Dane trafiają na potwierdzenie przyjęcia (wydruk A4) i do kodu QR
  „Oceń nas" (nazwa RoweryExpert jest stała).

### 3.4 Zakładka „Dodaj usługi" (katalog usług)

- **`services`** (tablica modułowa) — cache katalogu usług pobierany z API.
- **`loadServices()`** — GET `API_USLUGI`; przy `data.success` ustawia `services` i przebudowuje
  obie widoczne reprezentacje katalogu: checkboxy formularza i listę w zakładce.
  Błąd → toast „Nie udało się pobrać listy usług.".
- **`renderFormServiceCheckboxes()`** — przebudowuje checkboxy pod polem „Opis usterki"
  (`serviceCheckboxList`); pusty katalog → komunikat
  „Brak skonfigurowanych usług — dodaj je w Ustawieniach."; każde pole to
  `label.service-check` + `input.service-checkbox` o wartości `item.nazwa`.
- **`renderServiceList()`** — buduje listę `#service-list` w zakładce „Dodaj usługi": każdy
  wiersz to nazwa usługi + przycisk `button.remove-service` („×", tytuł „Usuń usługę");
  pusta lista → „Brak dodanych usług.".
- **`removeService(id)`** — najpierw `showConfirmModal('Usuń usługę', …)` z ostrzeżeniem, że
  usługa zniknie także z checkboxów formularza; potem POST `application/x-www-form-urlencoded`
  `action=remove&id=…` do `API_USLUGI`; sukces → podmiana `services` odpowiedzią serwera,
  przerysowanie obu widoków i toast „Usunięto usługę." (`info`).
- **`addService()`** — walidacja pustej nazwy (toast „Wpisz nazwę usługi."), blokada przycisku,
  POST `action=add&nazwa=…` do `API_USLUGI`; sukces → czyszczenie pola, przerysowanie widoków,
  toast „Dodano usługę!"; `finally` odblokowuje przycisk. Podpięte pod `#add-service-btn`
  i `Enter` w `#new-service-input` (oba przez `?.`, bo u pracownika zakładki nie ma).

### 3.5 Zakładka „Moduły"

- **`MODUL_NAZWA`** — mapa flag → czytelne nazwy (m.in. `kalendarz`: „Kalendarz",
  `zdjecia`: „Zdjęcia", `skaner`: „Skaner QR", `uslugi`: „Katalog usług", `druk`: „Drukowanie",
  `kosz`: „Kosz", `kolorystyka`: „Kolorystyka", `powitanie`: „Powitanie",
  `karta_wydania`: „Karta wydania", `wykonane`: „Wykonane czynności").
- **`modToggles`** — wszystkie checkboxy `.mod-toggle` (każdy z `data-mod` = nazwa flagi),
  **`modulyHint`** (`#moduly-hint`) — linia statusu zapisu.
- **`syncModuleToggles()`** — ustawia `checked` każdego przełącznika wg `modulOn(data.mod)`
  i czyści hint; przy braku `modulyHint` (pracownik bez zakładki) niczego nie zmienia.
- **Listener `change` na każdym `mod-toggle`** — natychmiastowa zmiana: optymistyczne
  `MODULY[nazwa] = cb.checked`, hint „Zapisywanie…", następnie `saveModules()`
  (POST `action=modules`, `moduly=JSON` do `API_USTAWIENIA`), `applyModules()`,
  `renderServicesList()`, `updateDashboard()`; **specjalny efekt**: włączenie modułu
  `kalendarz` przy pustym `datePlannedInput` ustawia domyślny termin na **+3 dni**;
  sukces → hint „Moduł „…" włączony/wyłączony." + toast „Zapisano ustawienia modułów.";
  błąd → **cofnięcie** flagi i `checked` do stanu poprzedniego + toast błędowy.
- Uwaga: przełącznik `kolorystyka` steruje **widocznością palety akcentu**, a wymuszony
  domyślny żółty kolor przy jego wyłączeniu realizuje `applyModules()` wywołujące
  `setAccent(..., zapisz)` z `motyw.js` (wybór użytkownika w `localStorage` zostaje zachowany).

### 3.6 Zakładka „Użytkownicy"

Zakładka jest renderowana w PHP wyłącznie dla admina; cała jej logika (`loadUsers()`,
lista, karta konta, role, reset hasła) znajduje się w `uzytkownicy.js` — patrz sekcja 4.
`switchSettingsTab('users')` wywołuje tylko `loadUsers()`.

### 3.7 Aktualizacje i kopie zapasowe — **nie są częścia `ustawienia.js`**

- W tym pliku **nie ma** zakładki „Aktualizacje" ani „Kopie zapasowe". Sprawdzanie i instalacja
  aktualizacji żyją w `core.js` (elementy `#update-banner-text`, `#update-check-btn`,
  `#update-modal`, funkcja `forceCheckUpdate()` wysyłająca żądanie do `API_USTAWIENIA`),
  a banner/pasek jest w `partials/panel.php`.
- Kopie zapasowe nie mają osobnego kontrolera w UI: `config.php` tworzy je **automatycznie**
  przy aktualizacji (`uploads/backup/backup-*.zip` oraz kopia `config-*.php.bak`,
  katalog chroniony `.htaccess`). W modalu aktualizacji (`partials/modaly.php`) znajduje się
  tylko informacja „Przed aktualizacją zostanie utworzona kopia zapasowa plików."
  — nie jest to osobna sekcja ustawień.
- Zakładka kolorystyki/akcentu również **nie istnieje** w modalu ustawień: wybór koloru to
  paleta w nagłówku obsługiwana przez `motyw.js`, a w ustawieniach jest tylko przełącznik
  modułu `kolorystyka`.

---

## 4. `assets/js/uzytkownicy.js` — zarządzanie kontami (wyłącznie admin)

### 4.1 Stan i elementy

- `usersListEl` (`#users-list`, z atrybutem `data-me` = id zalogowanego), `usersHintEl`
  (`#users-hint`), `adduserBtn` (`#add-user-btn`), `newuserLogin`, `newuserPass`, `newuserRola`
  (`#new-user-login`, `#new-user-pass`, `#new-user-rola`) — formularz dodawania konta.
- `myUserId` — `Number(usersListEl?.dataset.me || 0)`; identyfikator własnego konta, używane
  w self-guardach.
- Elementy karty konta `#user-modal`: `userModal`, `userModalLogin`, `userModalBadge`,
  `userModalStatus`, `userModalLast`, `userModalCreated`, `userModalPassflag`,
  `userModalZgloszenia`, `userModalWydane`, `userModalRola`, `userModalPassInput`,
  `userModalPassSave`, `userModalHint`, `userModalToggle`, `userModalDelete`, `userModalCancel`,
  `userModalClose`.
- `usersCache` — lokalna kopia listy kont; `openUserId` — konto otwarte w karcie.

### 4.2 Funkcje pomocnicze

- **`usersHint(text, isError)`** — wpisuje komunikat w `#users-hint` (kolor `var(--danger)`
  dla błędów, `var(--text-secondary)` dla neutralnych); no-op, gdy element nie istnieje.
- **`cardHint(text, isError)`** — to samo dla `#user-modal-hint` wewnątrz karty konta.
- **`usersPost(fields)`** — wspólny wysyłacz do `API_UZYTKOWNICY`: zamienia obiekt pól w
  `FormData`, wykonuje POST przez `apiFetch`, parsuje JSON i rzuca wyjątek przy
  `!data.success` (komunikat serwera lub „Operacja nieudana."). Obsługuje akcje:
  `create`, `role`, `toggle`, `password`, `delete`.
- **`formatLastLogin(v)`** — formatuje znacznik czasu; puste/`null` → **„nigdy"**, a poprawny
  format `'2026-09-28 10:00:00'` → `'28.09.2026 10:00'` (regex z grupami R-M-D i HH:MM);
  w innym wypadku zwraca surową wartość. Używane też do „Konto utworzone".
- **`roleBadge(u)`** — zwraca HTML plakietki roli: `admin` w kolorze `var(--primary)`
  z obramowaniem, `pracownik` w stylu szarym (małe wersaliki, kapsułka `border-radius: 999px`).
- **`userById(id)`** — wyszukuje konto w `usersCache` po liczbowym `id` (porównanie przez
  `Number(...)`), inaczej `null`.

### 4.3 Lista kont

- **`renderUsers()`** — renderuje `#users-list` z `usersCache`; pusta lista →
  „Brak kont użytkowników.". Każdy wiersz to `div.user-row` (`data-user-id`, `role="button"`,
  `tabindex="0"`, `aria-label` z escapowanym loginem) zawierający:
  login (`escapeHtml`), `roleBadge`, opcjonalne flagi („konto wyłączone" na czerwono,
  „wymusza zmianę hasła", „(to Twoje konto)"), **„ostatnie logowanie: …"** (przez
  `formatLastLogin(u.last_login_at)`) oraz strzałkę `›`; w drugim wierszu statystyki
  „założył: N zgłoszeń · wydał: N rowerów".
- **`loadUsers()`** — GET `API_UZYTKOWNICY` przez `apiFetch` z hintem „Wczytywanie…";
  sukces → `usersCache = data.data`, `renderUsers()` i **odświeżenie otwartej karty**
  (`if (openUserId !== null) fillUserCard(openUserId)`); błąd → wyczyszczenie listy i
  komunikat błędu w `users-hint`.

### 4.4 Karta konta (modal `#user-modal`)

- **`fillUserCard(id)`** — wypełnia kartę: login, plakietkę roli, status
  („wyłączone" na czerwono / „aktywne"), ostatnie logowanie i datę utworzenia
  (`formatLastLogin`), flagę hasła („wymusza zmianę po zalogowaniu" / „ustalone"),
  liczniki zgłoszeń i wydanych rowerów, wartość `select` roli, tekst przycisku
  („Włącz konto" / „Wyłącz konto"), czyszczenie pola hasła (chyba że jest w nim fokus).
  **Self-guardy:** dla własnego konta (`self`) wyłączone są `userModalRola` i
  `userModalToggle`, a `userModalDelete` jest wyłączone także wtedy, gdy konto ma
  `refs = zgloszenia + wydane > 0`; hint wyjaśnia przyczynę (konto z historią obsługi można
  tylko **wyłączyć**, żeby na kartach została informacja, kto je obsługiwał).
- **`openUserCard(id)`** — ustawia `openUserId`, buduje kartę i otwiera modal.
- **`closeUserCard()`** — zamyka modal i zeruje `openUserId`.
- **Listener kliknięcia / `keydown` (Enter, Spacja) na `#users-list`** — otwiera kartę konta
  z najbliższego `data-user-id`.
- **Listener `change` na `#user-modal-rola`** — zmiana roli: normalizacja wartości do
  `admin|pracownik`, `usersPost({ action:'role', id, rola })`, toast informacyjny
  „Rola konta „…": administrator/pracownik.", ponowne `loadUsers()`; błąd → `cardHint` i
  `fillUserCard()`, co **cofna** wartość w `select`.
- **Listener kliknięcia `#user-modal-toggle`** — włączenie/wyłączenie konta:
  `usersPost({ action:'toggle', id, aktywny: '0'|'1' })`, toast
  „Konto „…" wyłączone/włączone.", odświeżenie listy; błąd → `cardHint`.
- **Listener kliknięcia `#user-modal-pass-save` (reset hasła)** — walidacja `min. 6 znaków`
  (hint czerwony + focus), potem `usersPost({ action:'password', id, haslo })`; sukces →
  toast „Hasło konta „…" zmienione; konto wylogowane.", wyczyszczenie pola, `loadUsers()`.
- **Listener kliknięcia `#user-modal-delete` (usunięcie konta)** — twardy strażnik
  `if (u || userModalDelete.disabled)`; najpierw `showConfirmModal('Usunąć konto?', …)`
  z dopiskiem, że konta z historią obsługi należy **wyłączyć**, a nie kasować; potem
  `usersPost({ action:'delete', id })`, zamknięcie karty, toast „Konto „…" usunięte."
  i `loadUsers()`.
- **Listener `#user-modal-close`, `#user-modal-cancel` i kliknięcie w tło modala** — zamknięcie
  karty (`closeUserCard()`).

### 4.5 Dodawanie konta

- **Listener kliknięcia `#add-user-btn`** — walidacja: login niepusty (hint „Podaj login
  (np. imię pracownika)."), hasło min. 6 znaków (hint „Hasło startowe musi mieć min. 6
  znaków."); blokada przycisku; `usersPost({ action:'create', login, haslo, rola })`
  (rola z `select`, domyślnie `pracownik`); sukces → czyszczenie pól, reset roli na
  „pracownik", toast „Konto „…" utworzone.", `loadUsers()`; błąd → hint; `finally`
  odblokowanie przycisku.

### 4.6 Bramkowanie uprawnień

Cały plik (a także HTML `#users-list`, formularz dodawania i `#user-modal`) renderowany jest
wyłącznie w PHP dla roli `admin` (`partials/modal-ustawienia.php`); w JS nie ma zatem
warunków rolowych poza self-guardami (`myUserId`, blokada własnego konta i kasowania kont
z historią). Pracownik nie widzi zakładki „Użytkownicy" — `switchSettingsTab()` cofa go do
„Ogólne".

---

## 5. `assets/js/motyw.js` — motyw, akcent, helpery i toasty

### 5.1 Motyw jasny/ciemny

- **Listener `themeToggleBtn`** — przełącza motyw: jeśli `document.body` ma `light-theme`,
  wywołuje `setTheme('dark-theme')`, w przeciwnym razie `setTheme('light-theme')`.
- **`setTheme(theme)`** — dla `light-theme` dodaje klasę do `<body>`, chowa ikonę słońca
  (`themeIconSun`) i pokazuje księżyc (`themeIconMoon`); dla motywu ciemnego odwrotnie
  (klasa `light-theme` usuwana). Zawsze zapisuje wybór w `localStorage` pod kluczem `theme`
  (ustawienie per urządzenie).

### 5.2 System koloru akcentu

- **`ACCENT_KLASY`** — tablica klas dodawanych do `<body>`:
  `['accent-zielony', 'accent-czerwony', 'accent-niebieski', 'accent-pomaranczowy']`.
  Domyślny **żółty** nie ma własnej klasy (jest kolorem bazowym, stąd pominięty).
- **`ACCENT_PLIKI`** — mapa wariantów graficznych `zolty | zielony | czerwony | niebieski |
  pomaranczowy` → pary `{ logo, fav }` (`logo.png`/`favicon.png` oraz przekolorowane
  `logo-zielony.png`/`favicon-zielony.png` itd.); oryginały mają żółty `#FFDD00`,
  warianty są generowane z logo i faviconki.
- **`paletteBtn` (`#palette-btn`)**, **`accentPicker` (`#accent-picker`)** — przycisk palety
  w nagłówku i wysuwany panel z próbkami.
- **`setAccent(accent, zapisz)`** — główna funkcja ustawiania akcentu:
  1. domyślny wariant `zolty` przy pustej wartości;
  2. usunięcie wszystkich `ACCENT_KLASY` z `<body>` i dodanie `accent-{wariant}`
     (dla `zolty` nic nie jest dodawane);
  3. oznaczenie aktywnej próbki: `.accent-swatch` dostaje `active` gdy
     `dataset.accent === wariant`;
  4. podmiana **logo** (wszystkie `.logo-img`, z kontrolą `src.endsWith(plik.logo)`,
     żeby nie przeładowywać) i **faviconki** (`link[rel="icon"]`) na wariant z `ACCENT_PLIKI`;
  5. zapis w `localStorage` pod kluczem `accent` **wyłącznie gdy `zapisz !== false`** —
     dzięki temu wymuszenie żółtego przy wyłączonym module `kolorystyka` (wywołanie z
     `applyModules()` w `core.js`) nie kasuje wcześniejszego wyboru użytkownika.
- **Inicjalizacja przy starcie**: `setAccent(localStorage.getItem('accent') || 'zolty')`
  — kolor jest stosowany przed pierwszym świadomym odrysowaniem.
- **Listener kliknięcia `#palette-btn`** — `stopPropagation()` i przełączenie klasy `open`
  na `#accent-picker`.
- **Listener kliknięcia `.accent-swatch`** — `setAccent(sw.dataset.accent)` (z zapisem).
- **Listener `click` na `document`** — zamknięcie palety po kliknięciu poza nią i poza
  przyciskiem (`!accentPicker.contains(e.target) && !paletteBtn.contains(e.target)`).

### 5.3 Helpery dat, telefonu, HTML i toastów

- **`formatDateForInput(date)`** — format `YYYY-MM-DD` (z zerem wiodącym) do elementu
  `input[type="date"]` i jako klucz dni w kalendarzu.
- **`formatDateForUser(dateStr)`** — czytelna data dla użytkownika w PL, `DD.MM.RRRR`
  (bez godzin); pusty/wejście bez wartości → `''`.
- **`formatPhone(value)`** — usuwa wszystkie niedigitowe znaki, przycina do **9 cyfr** i
  wstawia myślniki co 3 cyfry (`500-600-700`).
- **`validatePhone(phone)`** — `true`, gdy w numerze jest co najmniej **9 cyfr**.
- **`showToast(message, type = 'success')`** — tworzy `div.toast` z ikoną SVG dobraną do
  typu: `success` (ptak, `var(--success)`), `info` (kółko z „i", `var(--calendar-color)`),
  `error` (dokument z „!", `var(--danger)`); **jeden toast naraz** — poprzednie są usuwane
  z `toastContainer` (skumulowane toasty zasłaniały ekran telefonu); po 3 s animacja
  wyjazdu (`slideIn 0.3s reverse forwards`) i usunięcie po dodatkowych 300 ms.
- **`escapeHtml(value)`** — bezpieczne wstawianie tekstu do HTML: `&`, `<`, `>`, `"`, `'`
  → `&amp;`, `&lt;`, `&gt;`, `&quot;`, `&#039;`; wejście normalizowane przez
  `String(value ?? '')`.

---

## 6. `assets/js/skaner.js` — skaner QR na telefonie

### 6.1 Stan i elementy

- `scanModal` (`#scan-modal`), `scanVideo` (`#scan-video`), `scanHint` (`#scan-hint`);
  zmienne: `scanStream` (strumień kamery), `scanRafId` (id `requestAnimationFrame`),
  `scanBusy` (blokada wielokrotnej detekcji w jednym ticku), `scanLastTick` (czas ostatniego
  odczytu), `barcodeDetector` (natywny detektor, tworzony laztywnie),
  `jsQRPromise` (memoizowane ładowanie fallbacku).

### 6.2 Funkcje

- **`stopScanner()`** — zamyka modal, kasuje `scanBusy`, anuluje pętlę `requestAnimationFrame`,
  zatrzymuje wszystkie tracki strumienia kamery i czyści `scanVideo.srcObject`.
- **`loadJsQR()`** — ładuje bibliotekę `jsQR` **na żądanie** z CDN
  (`https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js`) przez dynamiczny `<script>`;
  wynik jest memoizowany w `jsQRPromise`, a `window.jsQR` (jeśli już jest) rozwiązuje promise
  od razu; błąd ładowania → wyjątek „Nie udało się załadować biblioteki skanera."
  (fallback jest potrzebny np. w iOS Safari bez `BarcodeDetector`).
- **`detectCodeFromVideo()`** — jedna próba odczytu kodu z bieżącej klatki wideo:
  1. **natywnie**: `'BarcodeDetector' in window` → instancja
     `new BarcodeDetector({ formats: ['qr_code'] })`, `detect(scanVideo)`, zwrócenie
     `codes[0].rawValue` (przy braku kodu `null`);
  2. **fallback jsQR**: `loadJsQR()`, pobranie wymiarów `videoWidth/videoHeight` (przy 0 →
     `null`), rysowanie klatki na zapamiętywanym `canvas` (`willReadFrequently: true`),
     `getImageData` i `jsQR(frame.data, w, h)` → `res.data`.
- **`scanTick(ts)`** — pętla skanowania na `requestAnimationFrame`: wychodzi, gdy modal nie jest
  otwarty; wykonuje detekcję tylko gdy `!scanBusy`, jest strumień, `video.readyState >= 2`
  i minęło **> 300 ms** od ostatniej próby; po wykryciu kodu wywołuje `handleScanResult(code)`
  i **przerywa** pętlę (bez kolejnego `requestAnimationFrame`); wyjątek przy detekcji jest
  raportowany **tylko raz** (`scanTick.warned`) komunikatem w `#scan-hint`
  „Nie udało się uruchomić odczytu kodu — wpisz numer ręcznie w wyszukiwarce.".
- **`handleScanResult(raw)`** — co dzieje się z odczytanym kodem:
  1. `stopScanner()`;
  2. **wymuszenie filtra `all`** (skan ma być widoczny niezależnie od aktywnego filtra) —
     przełączenie `.filter-btn` na `data-filter="all"`;
  3. wstawienie kodu w wyszukiwarkę: `searchQuery = code`, `searchInput.value = code`,
     `renderServicesList()`;
  4. wyszukanie po `serviceNo` (bezpieczne porównanie wielkości liter, tylko wpisy
     spoza kosza): `db.filter(item => !item.deleted && (item.serviceNo||'').toLowerCase() ===
     code.toLowerCase())`.
  **Efekt:** dokładnie jedno trafienie → `openDetailModal(matches[0].id)` (otwiera się karta
  zgłoszenia) + toast „Znaleziono zgłoszenie: {bikeName}"; kilka trafień → na telefonie
  (`IS_MOBILE`) od razu karta pierwszego z nich z toastem informacyjnym, na komputerze toast
  nakazujący sprawdzić listę; zero trafień → toast błędny „Brak zgłoszenia o numerze {code}.".
  Wynik to **karta podglądu**, a nie osobny typ listy — sama lista jest jedynie przefiltrowana
  zapytaniem wyszukiwania.
- **`window.openScanModal()`** — otwiera skaner:
  1. bramka modułu: `if (!modulOn('skaner')) return;`
  2. wyczyszczenie poprzedniego wyszukiwania (`searchQuery`, `input`), reset `scanTick.warned`,
     hint „Skieruj aparat na kod QR z numerem serwisowym (naklejka na rowerze).", otwarcie modala;
  3. sprawdzenie `navigator.mediaDevices.getUserMedia` (inaczej wyjątek „Ta przeglądarka nie
     udostępnia aparatu."), `getUserMedia({ video: { facingMode: { ideal: 'environment' } },
     audio: false })`, podpięcie strumienia do `<video>`, `await scanVideo.play()`,
     reset `scanLastTick` i start `requestAnimationFrame(scanTick)`;
  4. w `catch`: `stopScanner()` + toast „Nie udało się uruchomić aparatu: …".
- **Listener `#scan-qr-btn`** → `window.openScanModal`; **`#scan-modal-close`**,
  **`#scan-cancel-btn`** i kliknięcie w tło modala → `stopScanner()`.
- **Bramka sprzętowa**: `if (!IS_MOBILE) document.getElementById('scan-qr-btn').hidden = true;`
  — skaner (aparat) jest pokazywany tylko na urządzeniach mobilnych.

---

## 7. `assets/js/zdjecia.js` — zdjęcia: siatka, wgrywanie, usuwanie, lightbox

### 7.1 Modal zdjęć

- `photosModalId` — id zgłoszenia, którego zdjęcia są otwarte.
- **`window.openPhotosModal(id)`** — otwiera modal zdjęć: ustawia tytuł na nazwę roweru
  (`photosModalTitle`), czyści input plików, renderuje siatkę `item.photos || []`
  (`renderPhotosGrid`) i dodaje klasę `active` do `photosModal`; nie otwiera dla brakującego
  wpisu. Wywoływane z listy zgłoszeń (`lista.js`, tylko gdy `modulOn('zdjecia')`).
- **`renderPhotosGrid(photos)`** — przebudowuje siatkę `photosGrid`; pusta lista → komunikat
  „Brak zdjęć. Wgraj pierwsze zdjęcie powyżej."; każde kafelek `div.photo-tile` zawiera
  `<img>` z `escapeHtml(photo.url)` i `alt` (domyślnie „Zdjęcie") klikalne →
  `openLightbox(url)` oraz przycisk `button.delete-photo` („×", tytuł „Usuń zdjęcie") →
  `deletePhoto(photo.id)`.

### 7.2 Wgrywanie i limity

- **`uploadModalPhotos(files)`** — wysyła pliki do API:
  1. wyjście przy braku `photosModalId` lub pustej liście plików;
  2. `FormData` z polem `zgloszenie_id` i `photos[]` dla każdego pliku;
  3. `showUploading(true)` i zablokowanie **obu** inputów (`photosModalInput`,
     `cameraModalInput`) na czas wysyłki;
  4. POST `apiFetch(API_ZDJECIA, { method: 'POST', body: formData })`; przy `!data.success`
     wyjątek z komunikatem serwera;
  5. sukces → doklejenie nowych zdjęć do `item.photos`, ponowne `renderPhotosGrid` i
     `renderServicesList()`;
  6. **limity/ostrzeżenia**: `photoStatsNow()` → `fotoWarnText(bytes)`; jeśli przekroczono
     próg 80% limitu (`FOTO_LIMIT_MB` z `config.php`), jeden złożony toast
     „Dodano zdjęć: N. ⚠ {ostrzeżenie}" (kolor `error`), w przeciwnym razie zwykły
     „Dodano zdjęć: N"; dodatkowo `loadPhotoStats()` odświeża blok statystyk w Ustawieniach;
  7. `finally`: `showUploading(false)` i odblokowanie obu inputów.
- **Listener `change` na `photosModalInput`** — pobiera `Array.from(files)`, czyści input
  (żeby ten sam plik dało się wybrać ponownie) i wywołuje `uploadModalPhotos(files)`.
- **Listener `change` na `cameraModalInput`** — identyczna obsługa dla inputu aparatu
  (`capture`) używanego na telefonie.

### 7.3 Usuwanie zdjęcia

- **`window.deletePhoto(photoId)`** — najpierw `showConfirmModal('Usuń zdjęcie', …)` z
  ostrzeżeniem, że operacja jest **nieodwracalna** („Zostanie trwale usunięte z serwera");
  potem `apiFetch(API_ZDJECIA + '?id=…', { method: 'DELETE' })`; sukces → filtracja
  `item.photos` po `id`, przerysowanie siatki, `renderServicesList()` i odświeżenie
  statystyk (`loadPhotoStats()` — zużycie spada, znika ostrzeżenie o limicie), toast
  „Usunięto zdjęcie." (`info`); błąd → toast.
- **Listener `closePhotosBtn` i kliknięcie w tło `photosModal`** — zamknięcie modalu zdjęć
  i zerowanie `photosModalId`.

### 7.4 Lightbox i globalne zamykanie klawiszem Escape

- **`window.openLightbox(url)`** — ustawia `lightboxImg.src` i otwiera overlay lightboxa.
- **Listener kliknięcia w `lightbox`** — zamyka lightbox i czyści `src`.
- **Listener `keydown` na `document` (klawisz `Escape`)** — globalne zamykanie „na raz":
  lightbox, modal zdjęć (zerowanie `photosModalId`), `closeConfirmModal(false)`, a warunkowo
  `closeEditModal()`, `closeDetailModal()`, `stopScanner()`, zamknięcie `calendarModal`
  i `calHidePopover()` — każde wywołanie strzeżone jest `typeof … === 'function'` /
  `typeof … !== 'undefined'`, bo pliki mogą się ładować w różnej kolejności.

---

## Uwaga końcowa o zależnościach

Kolejność ładowania skryptów ma znaczenie: `motyw.js` dostarcza `escapeHtml`, `formatPhone`,
`formatDateForUser`, `showToast` używane przez pozostałe pliki; `ustawienia.js` definiuje
`services`, `loadServices()`, `fotoWarnText()` i `photoStatsNow()` wykorzystywane przez
`karta.js` (katalog „wykonane czynności") oraz `zdjecia.js` (ostrzeżenia o limicie);
`karta.js` udostępnia `openDetailModal`/`fillDetailModal`, z którego korzystają
`kalendarz.js` i `skaner.js`.

---

# Część VII — Historia zmian (changelog)

Pełny changelog: plik `CHANGELOG.md` na serwerze (53 wersje, 2026-09-23 → 2026-09-29).
Poniżej zwięzłe podsumowanie każdej wersji (kolejność od najnowszej).

## Wersje 3.x — konta, role, instalator, bezpieczeństwo

| Wersja | Data | Najważniejsze zmiany |
|---|---|---|
| `3.8.4` | 2026-09-29 | Katalog uploads/backup/ jest teraz chroniony .htaccess |
| `3.8.3` | 2026-09-29 | json_out() wysyła Cache-Control: no-store + Pragma: no-cache. |
| `3.8.2` | 2026-09-29 | do_update() przebudowuje config.php z nowego config.example.php |
| `3.8.1` | 2026-09-28 | Poprawka pozycjonowania modala ustawień — stała odległość od górnego |
| `3.8.0` | 2026-09-28 | Wersjonowanie semver X.Y.Z: ostatnia cyfra = drobne poprawki (3.8.1), |
| `3.8-instalator` | 2026-09-28 | Instalator install.php — graficzny wizard 6 kroków (wymagania serwera → |
| `3.7-nazwa-przy-wylogowaniu` | 2026-09-28 | korekta kolejności nagłówka: Korekta po feedbacku: nazwa użytkownika wraca na koniec nagłówka — znowu |
| `3.6-naglowek` | 2026-09-28 | nazwa użytkownika w nagłówku: Większa nazwa konta: .header-user 0.8rem → 1.05rem, font-weight: 600, |
| `3.5-cofniecie-wydania` | 2026-09-28 | „kto wydał" nie zostaje na stałe: api/zgloszenia.php → action=status: wejście w picked_up zapisuje |
| `3.4-kto-przyjal` | 2026-09-28 | kto przyjął i kto wydał — od razu na liście: Kto przyjął: w wierszu „Przyjęto" obok daty kółko z inicjałem loginu |
| `3.3-karta-konta` | 2026-09-28 | karta konta + usuwanie użytkowników: Lista pracowników to teraz tylko podgląd: wiersz (login, rola, flagi, |
| `3.2-wlasciciele` | 2026-09-28 | kto założył, kto wydał, filtr „Moje": Migracja zgloszenia (wykrywanie przez SHOW COLUMNS, kolumny nullable — |
| `3.1-role` | 2026-09-28 | role i uprawnienia: config.php: auth_is_admin() + auth_require_admin() (sesja 401 → rola 403). |
| `3.0-logowanie` | 2026-09-28 | CSS z serwis.php (2536 linii) → assets/css/panel.css, link z ?v=APP_VERSION. |

## Wersje 2.x — moduły, motyw, kalendarz mobile

| Wersja | Data | Najważniejsze zmiany |
|---|---|---|
| `2.13` | 2026-09-26 | Ostrzeżenie przy80% limitu zdjęć — próg FOTO_WARN_PCT =80: |
| `2.12` | 2026-09-26 | Motyw ciemny domyślnie — od pierwszego renderu strony, także na ekranie |
| `2.11` | 2026-09-26 | Wykonane czynności — przełącznik w Ustawieniach → Moduły (domyślnie |
| `2.10` | 2026-09-26 | Kolorystyka (2.4 + 2.5) — po wyłączeniu znika paleta koloru w nagłówku, |
| `2.9` | 2026-09-26 | Karta zgłoszenia: sekcja „Wykonane czynności” to wyłącznie usługi |
| `2.8` | 2026-09-26 | Kliknięcie w treść dowolnej karty na liście zgłoszeń otwiera |
| `2.7` | 2026-09-26 | Karta zgłoszenia (podgląd) ma teraz sekcję „Wykonane czynności" z tymi |
| `2.6` | 2026-09-26 | Po każdym zalogowaniu pokazuje się okno „Podsumowanie dnia" — ile rowerów |
| `2.5` | 2026-09-26 | Rower w logo i ikonka strony (faviconka) zmieniają kolor razem z akcentem |
| `2.4` | 2026-09-26 | Przycisk palety w nagłówku (obok przełącznika motywu) z 5 kulkami: |
| `2.3` | 2026-09-26 | Zakładka „Moduły" w Ustawieniach — 6 przełączników: Kalendarz, Zdjęcia, |
| `2.2` | 2026-09-25 | Wyszukiwanie na mobile: minimum 4 znaki numeru serwisowego i pokazuje |
| `2.1` | 2026-09-25 | Na mobile ukryte: lista zgłoszeń, kafle dashboardu, filtry i sortowanie |
| `2.0` | 2026-09-25 | Nowa paleta: żółty #FFDD00 + czerń + biel (był pomarańcz na graficie); |

## Wersje 1.x — pierwsze wydanie → wydruk, kalendarz, QR

| Wersja | Data | Najważniejsze zmiany |
|---|---|---|
| `1.24` | 2026-09-25 | Z paska filtrów zniknęły przyciski zduplikowane z dashboardem |
| `1.23` | 2026-09-25 | Dashboard podsumowań — kafle nad listą: „W serwisie”, „Gotowe do |
| `1.22` | 2026-09-25 | Dymek „+N więcej” rozrasta się teraz „z komórki dnia” zamiast |
| `1.21` | 2026-09-25 | Dymek „+N więcej” — po najechaniu kursorem (lub kliknięciu/tapnięciu |
| `1.20` | 2026-09-25 | favicon.png przekolorowany na pomarańcz strony (--primary: #f97316) |
| `1.19` | 2026-09-25 | Nowe logo — logo.png (512×322, tło przezroczyste) w nagłówku panelu |
| `1.18` | 2026-09-25 | Rower już odebrane też widoczne w kalendarzu — chipy statusu |
| `1.17` | 2026-09-25 | Strona „Instrukcja panelu” — osobny plik instrukcja.html |
| `1.16` | 2026-09-25 | Zakres pobytu w serwisie zamiast samego terminu — zgłoszenie |
| `1.15` | 2026-09-25 | Kalendarz terminów (widok miesięczny) — nowa ikonka kalendarza w nagłówku |
| `1.14` | 2026-09-25 | Podpis klienta i pieczątka serwisu przesunięte na dół — blok podpisów |
| `1.13` | 2026-09-25 | Usunięta sekcja „Dodatkowe uwagi” z kreseczkami — niepotrzebna, |
| `1.12` | 2026-09-25 | Wyszukiwanie po numerze serwisowym — pole „Szukaj” dopasowuje teraz |
| `1.11` | 2026-09-24 | Edycja zgłoszenia — przycisk „Edytuj” na karcie otwiera modal |
| `1.10` | 2026-09-24 | Rowerzysta z nakładki „Zapisywanie” nie pojawia się już na wydruku. |
| `1.9` | 2026-09-24 | Sylwetka rowerzysty bardziej jak człowiek: |
| `1.8` | 2026-09-24 | color-scheme na body (dark) i body.light-theme (light) — |
| `1.7` | 2026-09-24 | Typografia: Inter + Outfit → Barlow (jedna rodzina, 400–700, |
| `1.6` | 2026-09-24 | Animacja przy zapisywaniu zgłoszenia — obracające się koło zębate |
| `1.5` | 2026-09-24 | Wszystkie monity potwierdzania w jednym stylu modalnym — zamieniono |
| `1.4` | 2026-09-24 | Potwierdzanie usuwania zgłoszenia — natywne okno przeglądarki |
| `1.3` | 2026-09-24 | Opis maski zgłoszeń oczekujących: „Zgłoszenie zamazane” → |
| `1.2` | 2026-09-23 | Serwer zwracał starą wersję strony — LiteSpeed cache'ował wyrenderowany |
| `1.1` | 2026-09-23 | Numer wersji w stopce strony — stała APP_VERSION w config.php, |
| `1.0` | 2026-09-23 | Potwierdzanie zgłoszeń z mobile na komputerze: |

---

*Koniec dokumentacji. Źródła: kod aplikacji w `/rower/`, `instrukcja.html`, `CHANGELOG.md`, `README.md`.*
