# Changelog — RoweryExpert Panel Serwisowy (serwis2)

> Kopia rozwojowa projektu `serwis`. Wariant A: pełny reset tokenów designu.

Konwencja: wersja `X.Y.Z` (semver)
- `X` — zmiany organizacyjne (np. `4.0.0`)
- `Y` — większe zmiany (np. `3.9.0`)
- `Z` — drobne poprawki (np. `3.8.2`)

Numer bieżącej wersji zapisywany jest w `config.php` (`APP_VERSION`)
i wyświetlany w stopce strony.

---

## 3.8.10 — 2026-10-01

### Naprawione
- **Fałszywy toast „Nie udało się pobrać listy usług." po każdym odświeżeniu
  dla użytkowników nie-admin** — `renderServiceList()` dotykał elementu
  `#service-list`, który istnieje tylko w Ustawieniach widocznych dla admina
  (u pozostałych użytkowników = `null`). Wyjątek lądował w tym samym
  `catch` co błędy pobierania, więc mimo poprawnie renderowanych checkboxów
  usług w formularzu przyjęcia wyświetlał się komunikat o nieudanym ładowaniu.
  Naprawa: strażnik `if (!serviceListEl) return;` + rozdzielenie pobrania
  i renderu w `loadServices()` (błąd renderu nie udaje już błędu sieci;
  401 nie podwójnie toasuje).

---

## 3.8.9 — 2026-10-01

### Nowe
- **Instalator (krok 4): opcjonalny „E-mail do resetu hasła"** — adres, na który
  leci link do resetu hasła admina. Zapisywany w ustawieniach (`reset_email`),
  więc działa od razu po instalacji; też da się podać/później zmienić w
  Ustawieniach → Dane serwisu (funkcja istniała od 3.8.6).
- **Maskowany adres w komunikacie po wysyłce hasła** — ekran logowania pokazuje
  np. `med***rs@gmail.com` (3 pierwsze + 2 ostatnie litery części przed @,
  domena widoczna). Pełnego adresu nie zdradzamy na publicznym ekranie; komunikat
  jest identyczny niezależnie od tego, czy login istnieje (anty-enumeracja).
- **Instrukcja w ciemnym i jasnym motywie** — te same kolory co panel (przełącznik
  w nagłówku, wybór w tym samym localStorage co panel).
- **Instrukcja jako pierwszy ekran po instalacji** — krok 6 instalatora prowadzi
  do instrukcji (z banerem „instrukcję zawsze znajdziesz w stopce panelu"),
  a panel jest przyciskiem drugorzędnym.
- **„↑ Wróć do spisu treści" po każdej sekcji** instrukcji (17 linków).

### Zmienione
- Instrukcja: rozdz. 1 (maskowany adres po wysyłce), rozdz. 14 (e-mail do resetu
  podawany też w instalatorze).

## 3.8.8 — 2026-10-01

### Naprawione
- **Pole wyszukiwania nad listą po F5 podstawiało login** (np. „admin") —
  przeglądarka przywracała wartość z ekranu logowania i cała lista
  filtrowała się tym tekstem (stąd „brak wyników", częściowe wyniki oraz
  licznik Kosza „2" przy pustej liście). Pole startuje puste, ma
  `autocomplete="off"` i **przyjmuje tylko cyfry** (numer zlecenia /
  telefon) — litery są obcinane także przy późniejszym autofillu.
- **Licznik przy Koszu** liczy dokładnie to, co widać po wejściu w Kosz
  (z aktualnym wyszukiwaniem i filtrem po użytkowniku) — znika rozjazd
  „2 szt." przy pustej liście.

### Zmienione
- **Ręczne „Sprawdź aktualizacje" (Ustawienia → Ogólne) nie rusza żółtego
  banera na górze** — wynik to sam toast. Żółty baner pokazuje się tylko,
  gdy panel sam wykry nową wersję przy starcie (żadnego „Masz najnowszą
  wersję" na czerwono... żadnego utkniętego banera po kliknięciu).
- **Nowy przycisk „Aktualizuj" obok „Sprawdź aktualizacje"** — włączany
  dopiero, gdy check znajdzie nowszą wersję; otwiera standardowe okno
  aktualizacji (kopia zapasowa + potwierdzenie).
- Sortowanie „Wg użytkownika (kto założył)" usunięte z listy sortowań
  (najdłuższa opcja — select od razu węższy); po autorze filtrują nowe
  **ikonki użytkowników** w wierszu filtrów: kółko z inicjałem w kolorze
  plakietki, klik = tylko jego zgłoszenia, drugi klik zdejmuje filtr.

## 3.8.7 — 2026-10-01

### Dodane
- **Po zalogowaniu hasłem z maila panel sam otwiera Ustawienia → Ogólne** z podpowiedzią
  („dla bezpieczeństwa ustaw własne hasło”), a kursor wskazuje pole nowego hasła.
  Flaga `must_change_password` była ustawiana już w 3.8.6, ale nic jej nie pokazywało —
  komunikaty obiecywały wymuszoną zmianę, której nie było.

### Naprawione
- **Zmiana własnego hasła w Ustawieniach → Ogólne zawsze kończyła się
  błędem „Aktualne hasło jest nieprawidłowe"** (dla każdego, nie tylko po
  resecie — wykryte przy teście resetu 3.8.6). `change_own_password()`
  weryfikował podane hasło wobec `password_hash`, którego `auth_user()`
  nie pobiera z bazy (SELECT obejmuje tylko login, rolę i flagi) —
  `password_verify` dostawał pusty string. Teraz hash jest dogrywany
  osobnym zapytaniem. Naprawa obejmuje też okno wymuszanej zmiany hasła
  po resecie (to samo API).

### Zmienione
- **Przycisk „Sprawdź aktualizacje" przeniesiony z widoku głównego do
  karty Ustawień → Ogólne** (admin, pod statystykami zdjęć) — góra strony
  należy wyłącznie do banera z „Zaktualizuj teraz", który pojawia się,
  gdy faktycznie jest nowa wersja.

## 3.8.6 — 2026-10-01

### Dodane
- **Reset hasła administratora („Nie pamiętam hasła" przy ekranie logowania).**
  Przy podaniu loginu system generuje **losowe hasło** (12 znaków, bez mylących
  0/O/1/l/I), zapisuje je jako *oczekujące* w nowej tabeli `password_resets`
  i wysyła mailem razem z **linkiem potwierdzającym**. **Stare hasło działa
  aż do kliknięcia linku** — zmiana następuje dopiero tam, więc samo klikanie
  w formularz przez obcych z internetu nic nie zmienia.
  - link ważny 30 minut, jednorazowy (po użyciu wszystkie oczekujące kasowane),
  - limity: 3 wysyłki / 15 min na IP,
  - komunikaty uniwersalne (nie zdradzają, czy login istnieje ani czy wysyłka
    się udała); wyjątek: limit prób (zależy tylko od IP),
  - po potwierdzeniu `must_change_password = 1` — przy zalogowaniu użytkownik
    od razu ustawia własne hasło,
  - adres odbiorczy: nowe pole „E-mail do resetu hasła administratora" w
    Ustawieniach → Dane serwisu (z walidacją adresu),
  - wysyłka przez `mail()` skrzynki hostingu (nadawca `no-reply@<domena panelu>`,
    UTF-8) — test wysyłki na produkcji zaliczony 2026-10-01.

### Naprawione
- **Przycisk „Sprawdź aktualizacje" był nieosiągalny** — siedział wewnątrz
  banera aktualizacji, który jest ukryty, dopóki nowa wersja nie zostanie
  wykryta (a wykrycie bez niego wymagało ręcznego `POST check_update`,
  bo cache sprawdzenia trwa 24 h). Teraz przycisk jest **widoczny zawsze**
  (admin, prawy górny róg pod banerem) i omija cache.

## 3.8.5 — 2026-10-01

### Dodane
- **Panel na komputerze sam pokazuje zmiany zrobione gdzie indziej (bez F5).**
  Nowa kolumna `zgloszenia.updated_at` (migracja w `db()`, MySQL aktualizuje
  ją sam przy każdym UPDATE — endpointy edycji bez zmian) + lekki znacznik
  `api/zgloszenia.php?stamp=1` (liczba zgłoszeń + najnowszy `updated_at`
  + liczba zdjęć). Front co 10 s odpytuje znacznik; przy zmianie ponownie
  pobiera listę i przemalowuje listę zgłoszeń oraz kalendarz — **bez reloadu
  strony**, więc wypełniany formularz zostaje nietknięty. Scenariusz:
  przyjęcie na telefonie → zgłoszenie (z maską „Potwierdź") pojawia się
  na PC maksymalnie po 10 s.
  Odpytywanie wstrzymuje się, gdy: ktoś ma otwarte okno (karta, edycja,
  kalendarz, monit po przyjęciu), trwa zapis, albo karta panelu jest
  w tle przeglądarki (`visibilitychange` — brak zapytań do hostingu).

## 3.8.4 — 2026-09-29

### Zabezpieczone
- **Katalog `uploads/backup/` jest teraz chroniony `.htaccess`
  (`Require all denied`).** Backupy lądują w katalogu dostępnym z sieci,
  a kopia `config.php` zawiera sekrety (hasła bazy, hasło aplikacji) —
  wcześnie katalog był otwarty dla wszystkich (wykryte 2026-09-29 na
  produkcji, zablokowane ręcznie). `do_update()` zakłada `.htaccess`
  automatycznie przy tworzeniu katalogu backupu.

## 3.8.3 — 2026-09-29

### Naprawione
- **`json_out()` wysyła `Cache-Control: no-store` + `Pragma: no-cache`.**
  Bez tego LiteSpeed mógł cache'ować GET `api/ustawienia.php`, a przez niego
  lecą `check_update()` (wersja panelu) i `dane_instancji()` — panel
  dostawałby wystaringowane wersje i dane instancji. Ten sam wykryty wcześniej
  problem co z `serwis.php` (CHANGELOG 1.2).

## 3.8.2 — 2026-09-29

### Naprawione
- **`do_update()` przebudowuje `config.php` z nowego `config.example.php`**
  (sekrety przenoszone przez `config_przebuduj()`). Do tej pory aktualizacja
  podmieniała tylko frontend i `api/*`, a logika z `config.php` (`db()`,
  migracje, `check_update()`, samo `do_update()`) zostawała przy starej
  wersji — poprawki nigdy nie trafiały do działających instalacji.
  Stary config ląduje w `uploads/backup/config-*.php.bak`.
- `config_przebuduj()` twardo waliduje wynik (brak nieuzupełnionych stałych,
  obecność `function db(` i `APP_VERSION`) **przed** podmianą jakiegokolwiek
  pliku; przy błędzie aktualizacja przerywa się bez zmian na dysku.
- Podmiana komentarza wzorca („to jest WZORZEC…”) na komentarz gotowego pliku.
- **Cache-busting JS przez `wersja_aplikacji()`** zamiast `APP_VERSION` —
  przy niepodmienionym `config.php` przeglądarka dostawała stary JS.
- `do_update()` odmawia, gdy na GitHubie nie ma nowszej wersji, i zwraca
  błąd zamiast cichego sukcesu przy nieudanym zapisie plików.
- Pomijanie wpisów katalogów w zipballu GitHuba (kończą się na `/`).
- `opcache_invalidate()` dla każdego podmienionego pliku + `opcache_reset()`
  na końcu (wcześniej przez ~60 s działał stary kod).
- Czyszczenie cache `update_check_result` razem z `update_check_at`.
- Śmiertelny błąd `sys_get_temp_dir() / '…'` (dzielenie zamiast konkatenacji)
  w ścieżce pliku tymczasowego aktualizacji.

### Zmienione
- **Przycisk „Zapis do bazy” na mobile dostaje kolor akcentu strony**
  (`#save-only-btn` → `var(--primary)`) — zmienia się razem z wybraną
  kolorystyką. Na desktopie przycisk i tak jest ukrywany.
- Nowa funkcja `wersja_normalizuj()` — doprowadza wersję do postaci `X.Y.Z`
  (skrawa sufiksy typu `-instalator`), używana przez `check_update()`
  zamiast zduplikowanych regexów.

## 3.8.1 — 2026-09-28

### Naprawione
- Poprawka pozycjonowania modala ustawień — stała odległość od górnego
  marginesu ekranu.

## 3.8.0 — 2026-09-28

### Zmienione
- **Wersjonowanie semver X.Y.Z**: ostatnia cyfra = drobne poprawki (3.8.1),
  środkowa = większe zmiany (3.9.0), pierwsza = zmiany organizacyjne (4.0.0).
  `APP_VERSION` = `3.8.0`; `check_update` normalizuje wersje do X.Y.Z
  (z fallbackiem dla `3.8` → `3.8.0`). Tag `v3.8.0` obok `v3.8`.

## 3.8-instalator — 2026-09-28

### Dodane
- **Instalator `install.php`** — graficzny wizard 6 kroków (wymagania serwera →
  baza z testem połączenia i rozwijaną podpowiedzią „jak założyć bazę” →
  dane serwisu → hasło admina → podsumowanie → gotowe). CSRF, limit prób testu
  bazy (10 / 15 min), blokada przy istniejącym `config.php`, po instalacji
  przypomnienie o usunięciu pliku. Wygenerowany `config.php` = podmiana stałych
  we wzorcu `config.example.php` (sekret przez `var_export`).
- **Dane instancji jako stałe w `config.php`**: `SERVICE_ADDRESS`, `SERVICE_CITY`,
  `SERVICE_PHONE`, `GOOGLE_MAPS_URL`, `SITE_URL` — stopka wydruku i QR „Oceń nas”
  czytają ze stałych zamiast utwardzonych tekstów (WCZEŚNIEJ: `wydruk.php:64-65`,
  dwa miejsca w `druk.js`). Branding RoweryExpert pozostaje niekonfigurowalny.
- **Bramka instalacji**: brak `config.php` → `serwis.php` przekierowuje na
  `install.php`, `api/*` zwraca 503 JSON zamiast fatality PHP.
- **Edycja danych instancji po instalacji** — zakładka „Dane serwisu" w ustawieniach
  (tylko admin): adres, kod/miasto, telefon, link Google, URL panelu. Zapis do
  tabeli `ustawienia` przez `api/ustawienia.php` (`action=dane_instancji`),
  odczyt przez `dane_instancji()` z fallbackiem do stałych. Zmiana działa od razu
  na wydruku — bez przeinstalowywania.
- `README.md` (instrukcja instalacji i ręcznej aktualizacji) + `LICENSE` (MIT) —
  przygotowanie dystrybucji publicznej przez GitHuba.

### Zmienione
- `config.example.php` — odświeżony z 3.0 do 3.8 (kopiowany z `config.php`,
  sekrety i dane instancji podmienione na `UZUPELNIJ`).
- Neutralne przykłady telefonu w placeholderze pola telefonu (`panel.php`),
  na wydruku i w komentarzu `motyw.js` (bez numeru serwisu).
- `partials/head.php` — `window.APP_CFG.mapsUrl` dla JS.
- Instalator: normalizacja SITE_URL (zdublowany schemat `https://https://…`
  skracany do jednego, brak schematu → doklejany `https://`).
- **Automatyczne aktualizacje (v2)**: `check_update()` (GitHub API, cache 24 h
  w `ustawienia`) + `do_update()` (pobranie zipballa, weryfikacja, kopia zapasowa
  do `uploads/backup/`, podmiana z pominięciem `config.php`/`uploads/`.user.ini`,
  migracje `db()`). Baner w panelu: admin widzi przycisk „Zaktualizuj teraz",
  pracownik — „powiadom administratora". Modal z potwierdzeniem i paskiem postępu.

---

## 3.7-nazwa-przy-wylogowaniu — 2026-09-28 (korekta kolejności nagłówka)

- **Korekta po feedbacku**: nazwa użytkownika wraca na koniec nagłówka — znowu
  bezpośrednio przy przycisku wylogowaniu (jak przed 3.6), a to **przycisk
  ustawień został przeniesiony w stronę nazwy**. Kolejność:
  `paleta → ⚙ ustawienia → nick → 🚪 wyloguj`.
- CSS: selektor pary odwrócony (`#open-settings-btn + .header-user`),
  nadal cieśniejszy odstęp `−0.5rem`.
- Rozmiar nazwy, klik/Enter/Spacja otwierający ustawienia — bez zmian (z 3.6).

### Testy
- `test31` (41 asercji): kolejność sprawdzana dwustronnie —
  `previousElementSibling == #open-settings-btn` ORAZ
  `nextElementSibling == #logout-btn`.

---

## 3.6-naglowek — 2026-09-28 (nazwa użytkownika w nagłówku)

- **Większa nazwa konta**: `.header-user` `0.8rem → 1.05rem`, `font-weight: 600`,
  kolor `--text-primary` (był secondary), `max-width 90px → 170px` + podkreślenie
  przy najechaniu i `cursor: pointer`.
- **Przycisk ustawień zaraz obok nazwy**: `<span id="current-user">` przeniesiony
  bezpośrednio przed `#open-settings-btn` (+CSS `.header-user + #open-settings-btn`
  zmniejszający odstęp, żeby czytało się jako para).
- **Klik w nazwę otwiera ustawienia** (tak samo jak ikona): wspólna funkcja
  `openSettingsModal` w `ustawienia.js`, plus obsługa klawiatury
  (Enter/Space, `role="button"`).
- **Fix ukryty dotąd**: `var(--accent)` **nie istnieje** w CSS (jest `--primary`) —
  deklaracje odpadały po cichu; podmienione w `.user-row:hover` i odznace roli
  admina w `uzytkownicy.js` (wcześniejszy hover wierszy i ramka odznaki w
  ogóle nie działały).

### Testy
- `test31` rozbudowany o sekcję nagłówka: rozmiar `>= 16px`, sąsiedztwo
  `#current-user` ↔ `#open-settings-btn`, klik w nazwę otwiera modal ustawień.

---

## 3.5-cofniecie-wydania — 2026-09-28 („kto wydał" nie zostaje na stałe)

### Problem
Po cofnięciu wydania (kliknięcie odznaki statusu „Odebrany" → „W serwisie")
informacja **kto wydał rower** zostawała na liście i na karcie — `confirmed_by`
był kasowany tylko… nigdy; znikał dopiero po ręcznej ingerencji w bazę.

### Zmiana (dwa zabezpieczenia)
- **`api/zgloszenia.php` → `action=status`**: wejście w `picked_up` zapisuje
  `confirmed_by = <kto kliknął>`, **wyjście z `picked_up` czyści go do NULL**
  (dotyczy wszystkich ścieżek zmiany statusu: odznaka na liście, „Wydaj
  rower" w karcie). Przy okazji zniknął martwy `rowCount()`-check — jest
  teraz wstępny `SELECT status` (ten sam strzał = walidacja istnienia).
- **`config.php` → `map_zgloszenie`**: bramka na poziomie odczytu —
  `confirmedBy`/`confirmedById` przekazywane dalej **wyłącznie gdy
  `status === 'picked_up'`**. Dzięki temu stare rekordy (np. wpis id=56
  z13:48) też przestają pokazywać wydającego bez migracji danych, a każdy
  przyszły ścieżka zapisu (edycja, import) jest bezpieczna.
- `action=confirm` (potwierdzenie maski mobilnej) zostaje bez zmian —
  wartość i tak jest bramkowana statusem i nadpisywana przy faktycznym
  wydaniu.

### Testy
- `test32` — krok „wydanie" teraz przez `action=status` (picked_up), nie
  `confirm` (to był błąd w testach: „Wydaj rower" nigdy nie wysyła `confirm`).
- `test34` — rozszerzony o scenariusz użytkownika: wydanie → odznaka na
  liście (cofnięcie) → **kółko znika i API zwraca `confirmedBy: null`**,
  ponowne wydanie → kółko wraca.

---

## 3.4-kto-przyjal — 2026-09-28 (kto przyjął i kto wydał — od razu na liście)

### Plakietki osób na liście zgłoszeń
- **Kto przyjął**: w wierszu „Przyjęto" obok daty kółko z inicjałem loginu
  w stałym kolorze wyliczanym z loginu (hash → HSL, niezależny od motywu:
  ciemny/jasny zawsze ten sam kolor) + nazwa obok; tooltip = pełne konto.
- **Kto wydał**: przy odznace statusu drugie kółko z inicjałem wydającego —
  cała obsługa (przyjął → wydał) bez otwierania karty.
- Rekordy sprzed wdrożenia (`created_by` = NULL) = szare „—" z podpisem
  „Konto sprzed wdrożenia użytkowników" w tooltipie.
- Kliknięcie w kółko/nazwę jak w resztę karty → otwiera zgłoszenie.
- Nowe klasy CSS: `.user-chip`, `.user-chip-empty`, `.chip-inline`,
  `.chip-name`, `.status-wrap`.

### Testy
- Nowy `test34.py` — plakietka przyjmującego (tytuł = login, inicjał,
  kolor `hsl(...)`), kółko wydającego przy statusie, **różne kolory dla
  różnych loginów** i zawsze ten sam dla tego samego, szare „—" dla rekordu
  sprzed wdrożenia, czysta konsola.

---

## 3.3-karta-konta — 2026-09-28 (karta konta + usuwanie użytkowników)

### Karta konta (osobny modal, jak zgłoszenie)
- Lista pracowników to teraz **tylko podgląd**: wiersz (login, rola, flagi,
  ostatnie logowanie, licznik „założył / wydał") — klik lub Enter **otwiera
  kartę konta** (`#user-modal`), tak jak karta zgłoszenia.
- W karcie: status, ostatnie logowanie, utworzenie konta, stan hasła,
  liczniki zgłoszeń, zmiana roli (select), reset hasła, włącz/wyłącz,
  usunięcie — wszystko w jednym miejscu zamiast przycisków w wierszach.
- Zamknij: krzyżyk, „Zamknij" lub klik w tło (jak inne modale).
- Nowa reguła CSS `.user-row` (kursor + podświetlenie przy najechaniu).

### Usuwanie kont (`POST action=delete`)
- Twarde usunięcie **wyłącznie kont bez zgłoszeń** (ustalenie: historia
  „kto założył / kto wydał" na kartach ma pozostać) — konto z historią
  obsługi można tylko **wyłączyć**; karta pokazuje liczniki i podpowiedź,
  a przycisk „Usuń konto" jest wtedy nieaktywny (serwer też zwraca 400).
- Usuwanie własnego konta zablokowane (UI + API); konto z licznikiem 0
  kasuje też jego sesje i próby logowania.
- Potwierdzenie przez wspólny `showConfirmModal(...)` jak przy kasowaniu
  zgłoszeń.

### Testy
- `test31.py` przepisane pod nowy UI (karta konta zamiast przycisków
  w wierszach) — nadal 30/30.
- Nowy `test33.py` — kasowanie: konto bez zgłoszeń → karta → potwierdzenie
  → zniknęło z listy i z API; konto z zgłoszeniami → API 400, przycisk
  nieaktywny + podpowiedź; po wyczyszczeniu zgłoszeń (purge) to samo konto
  da się już usunąć; self-guardy (własnego nie wyłączysz/nie usuniesz).

---

## 3.2-wlasciciele — 2026-09-28 (kto założył, kto wydał, filtr „Moje")

### Dane
- Migracja `zgloszenia` (wykrywanie przez `SHOW COLUMNS`, kolumny **nullable** —
  stary kod na wspólnej bazie przechodzi to bez zmian): `created_by`
  (kto założył) i `confirmed_by` (kto wydał rower).
- `create` wypełnia `created_by`, `confirm` („Wydaj rower") — `confirmed_by`.
- `map_zgloszenie` zwraca `createdById`/`createdBy`/`confirmedById`/`confirmedBy`
  (nazwa loginu z cache `users_login_map()` — jedno zapytanie na request,
  bez N+1). Rekordy sprzed wdrożenia (NULL) = `null` = „—".

### Uprawnienia (własność)
- `owner_guard()` w `api/zgloszenia.php`: pracownik kasuje (kosz) i przywraca
  **tylko swoje** zgłoszenia (403 dla cudzych), admin — wszystkie;
  rekordy sprzed wdrożenia (`created_by = NULL`) kasuje więc tylko admin.
- `purge` bez zmian = wyłącznie admin (sprawdzany przed guardem własności).
- Restrukturyzacja `restore`: najpierw `record_exists` (404), potem guard,
  potem UPDATE — identyczne odpowiedzi co przed zmianą.

### UI
- Karta zgłoszenia: pola **„Założył"** i **„Wydanie"** (stare = „—").
- Lista: przycisk filtra **„Moje"** (zgłoszenia założone przez mnie,
  `USER_ID` z bootstrapu PHP) + sortowanie **„Wg użytkownika"**
  (kto założył) i **„Wg wydającego"** — rekordy bez autora na końcu listy.

### Testy
- Nowy `/tmp/opencode/test32.py` — **29/29**: `createdBy`/`confirmedBy`
  po API, cudze kasowanie → 403 (nietknięte), swoje kasowanie + przywracanie
  → 200, karta pokazuje autora i wydającego, rekord sprzed wdrożenia = „—",
  filtr „Moje" (bez cudzych), sortowanie wg użytkownika (kolejność zgodna
  z API), purge testu przez admina, konsola czysta.
- Regresje: `test30.py`, `test31.py`, `test_js_split.py`,
  `test_css_split.py`, `test_prod_old.py`.

---

## 3.1-role — 2026-09-28 (role i uprawnienia)

### Uprawnienia (mapa w jednym miejscu)
- `config.php`: `auth_is_admin()` + `auth_require_admin()` (sesja 401 → rola 403).
- Guardy `auth_require_admin()` w API:
  - `ustawienia.php` — zapis listy modułów,
  - `uslugi.php` — dodawanie/usuwanie usług (odczyt = wszyscy),
  - `konto.php` — statystyki zdjęć (GET),
  - `zgloszenia.php` — trwałe `purge` z kosza (niszczy zdjęcia).

### Konta użytkowników
- Nowy endpoint `api/uzytkownicy.php` (wyłącznie admin): lista kont
  (bez hashy haseł), tworzenie konta, reset hasła (+ flaga
  `must_change_password` + wylogowanie sesji), zmiana roli,
  włączenie/wyłączenie konta (z wylogowaniem). Self-guardy: admin nie
  wyłączy własnego konta ani nie zmieni własnej roli.
- Zakładka **„Użytkownicy"** w Ustawieniach (tylko admin): formularz
  nowego konta (login/hasło/rola) i lista kont z akcjami (reset hasła,
  zmiana roli, włącz/wyłącz), ostatnie logowanie, flagi konta.
- Pracownik widzi w Ustawieniach **tylko „Ogólne"** (brak zakładek
  Dodaj usługi / Moduły / Użytkownicy i bloku statystyk) — PHP nie
  renderuje ich wcale, JS ma strażników null (`?.`, wczesne `return`).

### Poprawki
- CSS: `.modal-overlay` przewijany (`overflow-y: auto`) + `.modal-card`
  `margin: auto` — karta wyższa od ekranu (np. długa lista użytkowników)
  była przycinana i dolne przyciski nieklikalne.
- `syncModuleToggles()` nie rusza `#moduly-hint`, gdy go nie ma
  (pracownik) — wcześniej `PAGEERROR: setting 'textContent'`.

### Testy
- Nowy `/tmp/opencode/test31.py` — 30/30:4 zakładki admina, tworzenie
  konta, widok pracownika (1 zakładka),403 na modułach/usługach/
  statystykach/tworzeniu kont/purge (z weryfikacją, że zgłoszenie
  przeżyło), reset hasła (stare nie działa → nowe działa), wyłączone
  konto nie loguje się, self-guardy. Konsola czysta.
- Regresje zielone: `test30.py` 17/17, `test_js_split.py` 19/19,
  `test_css_split.py` (293 reguły CSS).

---

## 3.0-logowanie — 2026-09-28 (serwis2 = fork, produkcja bez zmian)

Fork z systemem użytkowników działa **równolegle** z produkcją na tej samej
bazie MySQL; stary `serwis/` pozostaje na 2.13 (hasło aplikacji) aż do merge.

### Refaktor (etap 0)
- CSS z `serwis.php` (2536 linii) → `assets/css/panel.css`, link z `?v=APP_VERSION`.
- JS z `serwis.php` → 12 plików w `assets/js/` (kolejność źródłowa, bez systemu
  modułów — globalne zmienne zostają); bootstrap PHP (`IS_AUTHENTICATED`,
  `FOTO_LIMIT_MB`) został jako inline `<script>` przed skryptami.
- `serwis.php`: 5998 → ~850 linii. Rekonstrukcja zweryfikowana (oryginał ≡ podział).

### Partials HTML/PHP (3.0-post)
- `serwis.php` (858 linii) → **bootstrap czysto PHP: 44 linie** + 9 plików w
  `partials/`: `head`, `login`, `panel`, `modal-ustawienia`, `modaly`,
  `modal-karta`, `modal-zdjecia`, `wydruk`, `scripts`.
- Każdy partial zaczyna się guardem `defined('SERWIS_PANEL') or exit` — wejście
  wprost do `partials/*.php` niczego nie renderuje.
- Podział w kolejności źródłowej, rekonstrukcja zweryfikowana skryptem
  (oryginał ≡ suma partiali). Uwaga: `serwis.php` **bez** `?>` na końcu —
  HTML żyje w partialach, requires muszą być w bloku PHP.

### Konta i sesje
- Nowe tabele: `users`, `sesje` (w bazie tylko **hash** tokenu),
  `login_attempts`; seed konta `admin` z dotychczasowego hasła aplikacji —
  **stare hasło działa dalej**, tylko dochodzi pole „Login".
- Sesja **14 dni ześlizgiem**; wylogowanie kasuje sesję w bazie (nie tylko cookie);
  cookie `rowery_expert_sess` — **własna nazwa forka**, żeby sesje obu wersji
  nie kolidowały (path=/).
- Ekran logowania: pole **Login** + Hasło, komunikat uniwersalny
  („Nieprawidłowy login lub hasło" — nie zdradza, czy login istnieje).
- Limit prób: **5 nieudanych / 15 min** (klucz login+IP), czyszczenie starych prób.
- Zmiana hasła = hasło **własnego konta** (`change_own_password`), pozostałe
  sesje konta wylogowywane; zapomniane → reset przez admina.
- Nagłówek: nazwa konta (`#current-user`); Ustawienia → Ogólne: konto + rola.
- `APP_VERSION = 3.0-logowanie` (znacznik forka w stopce).

### Testy
- `/tmp/opencode/test30.py` — **17/17 zielone**: pola logowania, komunikaty
  ujemne, logowanie admin, cookie, trwałość sesji, konto w nagłówku, zmiana
  hasła (złe aktualne), lockout po 5 (6. próba zablokowana), wylogowanie,
  ponowne logowanie, API 401 bez sesji.
- `/tmp/opencode/test_js_split.py` — 19/19 (regresja UI po podziale JS).
- `/tmp/opencode/test_css_split.py` — regresja po podziale CSS.
- `/tmp/opencode/test_prod_old.py` — produkcja na wspólnej bazie: logowanie
  starym mechanizmem, 7 kart, brak błędów.
- Po podziale na partials oba zestawy przepuszczone ponownie: `test30.py`
  17/17 + `test_js_split.py` 19/19, w tym weryfikacja guardu partiali
  (wejście wprost = pusta odpowiedź) i screenshoty desktop/mobile.

---

## 2.13 — 2026-09-26 (serwis2, serwis)

### Dodane
- **Ostrzeżenie przy80% limitu zdjęć** — próg `FOTO_WARN_PCT =80`:
  - po udanym wgrywaniu (modal Zdjęć oraz przyjęcie z plikami) komunikat
    toast „Zdjęcia: zużytoX% limitu100 MB — zostałoY MB. Usuń część
    starych zdjęć.” (czerwony, tylko gdy próg przekroczony);
  - w statystykach (Ustawienia → Ogólne) czerwony dopisek `#stats-warn`
    z tym samym tekstem i podświetlona wartość „X MB / 100 MB”;
  - poniżej progu statystyki bez zmian (jak2.12).
- `APP_VERSION` → `2.13`.

## 2.12 — 2026-09-26 (serwis2, serwis)

### Zmienione
- **Motyw ciemny domyślnie** — od pierwszego renderu strony, także na ekranie
  logowania (`<body class="dark-theme">` + domyślna wartość `'dark-theme'`
  w JS). Zapisany wcześniej wybór w localStorage nadal ma pierwszeństwo;
  preferencja systemowa (`prefers-color-scheme`) już nie decyduje.
- **Ustawienia → Moduły**: lista przełączników w przewijanym boxie o stałej
  wysokości240 px (wzór: `.service-list` przy Dodaj usługi) —10 modułów
  nie rozpycha karty i mieści się na jednym ekranie.
- Instrukcja: §14 (motyw domyślnie ciemny, lista modułów przewijana),
  §9 (limit zdjęć), §15 (przewijany box).

### Dodane
- **Limit pojemności zdjęć:100 MB łącznie** — stała
  `MAX_PHOTOS_TOTAL_BYTES` w `config.php` (+ `config.example.php`);
  `store_photos()` przed zapisem sumuje bieżące zużycie (`photos_stats()`)
  z rozmiarem partii i po przekroczeniu zwraca błąd z komunikatem.
  Zużycie z limitem widoczne w Ustawieniach → Ogólne („12,4 MB / 100 MB”),
  etykieta wgrywania zdjęć informuje o limicie.
- `APP_VERSION` → `2.12`.

## 2.11 — 2026-09-26 (serwis2, serwis)

### Dodane —10. moduł: Wykonane czynności
- **Wykonane czynności** — przełącznik w Ustawieniach → Moduły (domyślnie
  włączony): po wyłączeniu znika sekcja „Wykonane czynności” z karty
  zgłoszenia (`#detail-done-label` + `#detail-done-list`, także CSS
  `body.off-wykonane`) i sekcja ☑ z obu egzemplarzy wydruku, a API
  `action=services` zwraca403. Checkboxy przy przyjęciu i zakładka
  „Dodaj usługi” zostają przy module Katalog usług — moduły są rozdzielone
  (wcześniej sekcja karty była „przyklejona” do Katalogu usług).
- Dane nietknięte: `services_done` w bazie zostaje, po włączeniu modułu
  wszystko wraca (jak przy każdym module).

### Zmienione
- `moduly_dostepne()` z9 na10 kluczy (`config.php` + `config.example.php`);
  `APP_VERSION` → `2.11`.
- Instrukcja: §15 (lista modułów), §8 (sekcja karty), §11 (wydruk).

---

## 2.10 — 2026-09-26 (serwis2, serwis)

### Dodane —3 nowe przełączniki w Ustawieniach → Moduły (domyślnie włączone)
- **Kolorystyka** (2.4 + 2.5) — po wyłączeniu znika paleta koloru w nagłówku,
  a logo i faviconka wracają do domyślnego żółtego. Wybrany kolor zostaje
  w `localStorage` i wraca po ponownym włączeniu (`setAccent` nie kasuje
  wyboru przy wymuszeniu).
- **Powitanie** (2.6) — okno „Podsumowanie dnia” po zalogowaniu tylko przy
  włączonym module; nadal wymaga Kalendarza (bez terminów nie ma czego
  podsumowywać).
- **Karta wydania** (2.7) — automatyczny druk Karty Wydania Roweru przy
  wydaniu (z listy i z karty) tylko przy włączonych modułach druku
  i Karty wydania; sam przycisk „Wydaj rower” i ręczny druk zostają
  bez zmian.

### Zmienione
- `moduly_dostepne()` w `config.php` (+ `config.example.php`) z6 na9 kluczy;
  brak flagi w bazie = moduł włączony, więc nic się nie zmienia, dopóki
  czegoś nie wyłączysz. Nowe przełączniki blokowane też po stronie API
  (`api/ustawienia.php` waliduje listę).
- Instrukcja §15 (Moduły) i §8 (Karta wydania) zaktualizowane.

---

## 2.9 — 2026-09-26 (serwis2, serwis)

### Zmienione — karta pokazuje tylko zakres z przyjęcia, checkboxy puste na starcie
- **Karta zgłoszenia**: sekcja „Wykonane czynności” to **wyłącznie usługi
  zaznaczone przy przyjęciu** (linie „- ” dopisane do opisu) — nie cały
  katalog. Checkboxy startują **puste**; zaznaczasz je w chwili wykonania
  pracy, autozapis bez zmian (`action=services`). Na Karcie Wydania Roweru
  drukują się jako ☑ tylko zaznaczone.
- Przyjęcie **nie zapisuje już** zaznaczonych usług jako wykonanych
  (`services_done` nowego zgłoszenia = null).
- Jednorazowa korekta danych (marker `korekta_2_9_uslugi` w tabeli
  `ustawienia`): zgłoszenia, których `services_done` w całości pokrywa się
  z zakresem z opisu, wracają do stanu „nic nie zaznaczono”.
- **Notatki w karcie są aktywne**: pole tekstowe z autozapisem
  (debounce 450 ms, nowe `action=notes` w API); w koszu tylko do odczytu.
- Helpery: `getDoneServices()` = tylko faktycznie zapisany stan
  (null → pusto), nowe `getPlannedServices()` = zakres z przyjęcia
  ∪ zaznaczone.

---

## 2.8 — 2026-09-26 (serwis2, serwis)

### Zmienione — kliknięcie w kartę na liście otwiera kartę zgłoszenia
- Kliknięcie w treść dowolnej karty na **liście zgłoszeń** otwiera
  podgląd „Karta zgłoszenia” (dotychczas tylko kalendarz, QR i wyszukiwarka
  na telefonie).
- Przyciski i linki na karcie (Edytuj, Zdjęcia, Kalendarz, Drukuj, kosz,
  odznaka statusu, telefon, miniatury zdjęć, „Potwierdź”) zachowują
  swoją dotychczasową rolę — nie otwierają podglądu.
- Kursor nad kartą: pointer (sygnał, że karta jest klikalna).
- Na telefonie bez zmian — tam lista i tak nie jest pokazywana.

---

## 2.7 — 2026-09-26 (serwis2, serwis)

### Dodane — wykonane czynności (checkboxy) + karta wydania roweru
- **Karta zgłoszenia (podgląd)** ma teraz sekcję „Wykonane czynności" z tymi
  samymi checkboxami co przy przyjęciu — wstępnie zaznaczane tymi samymi
  usługami; zaznaczenia **zapisują się same** (nowe `action=services`,
  debounce). Po wydaniu/z kosza checkboxy tylko do podglądu.
- Dotychczasowe wolne pole tekstowe przemianowane na **„Notatki"**
  (karta, edycja, lista) — dane `service_notes` bez zmian.
- **Karta Wydania Roweru**: przy wydaniu roweru (przycisk „Wydaj rower"
  albo status „Odebrany" z listy) na komputerze drukuje się od razu
  karta z tytułem „Karta Wydania Roweru" i listą zaznaczonych czynności (☑);
  wyłączony moduł druku albo telefon → bez druku.
- Na wydruku: sekcja „Wykonane czynności" pojawia się tylko na karcie wydania
  i tylko gdy coś zaznaczono; **blok „Notatki" nie pojawia się, gdy tekst
  nie jest uzupełniony**.
- Baza: nowa kolumna `services_done` (JSON z nazwami usług, migracja przez
  `db()`); zgłoszenia sprzed zmiany liczą zaznaczenia z linii „- Usługa"
  w opisie. Tworzenie zgłoszenia zapisuje zaznaczone usługi od razu.
- Szukajka uwzględnia nazwy wykonanych czynności.
- Instrukcja: sekcje8 (karta),9 (edycja),11 (druk),12 (wydruk).

## 2.6 — 2026-09-26 (serwis2, serwis)

### Dodane — powitanie po zalogowaniu (podsumowanie dnia)
- **Po każdym zalogowaniu pokazuje się okno „Podsumowanie dnia"** — ile rowerów
  jest zaplanowanych **na dziś** i **na jutro** (te same helpery co kafle
  dashboardu: `isPlannedToday`/`isPlannedTomorrow`), plus ostrzeżenie
  „Po terminie: N" gdy jakikolwiek termin minął; przycisk **OK** (lub ×)
  zamyka okno.
- Mechanizm: udane logowanie → redirect na `?powitanie=1`, flaga w
  `body data-powitanie`, JS pokazuje okno po wczytaniu zgłoszeń i czyści
  parametr z adresu (F5 nie powtarza okna).
- Tylko przy włączonym module **Kalendarza** — przy wyłączonym terminy są
  ukryte w całym panelu, więc i okno się nie pojawia.
- Nowe: modal `#welcome-modal`, CSS `.welcome-grid`/`.welcome-stat`.
- Instrukcja: sekcja1 (Logowanie).

## 2.5 — 2026-09-26 (serwis2, serwis)

### Dodane — akcent w logo i faviconce
- **Rower w logo i ikonka strony (faviconka) zmieniają kolor razem z akcentem**
  z palety w nagłówku — także na ekranie logowania (oba `<img class="logo-img">`).
- Nowe pliki wariantów (4 szt. każdy, generowane z `logo.png`/`favicon.png`
  przez ImageMagick — podmiana żółtego `#FFDD00` na kolor akcentu, krawędzie
  i krycie bez zmian): `logo-zielony/czerwony/niebieski/pomaranczowy.png`,
  `favicon-zielony/czerwony/niebieski/pomaranczowy.png`.
- Domyślny żółty Media Expert bez zmian — oryginalne `logo.png` i `favicon.png`.
- Podmiana w `setAccent()` (jak motyw i klasy `body.accent-*`, per urządzenie
  w `localStorage`); motyw jasny bez zmian — logo dalej czarne (`brightness(0)`).
- Instrukcja: sekcja14 dopisuje o kolorze logo i faviconki.

## 2.4 — 2026-09-26 (serwis2, serwis)

### Dodane — przełącznik koloru akcentu
- **Przycisk palety w nagłówku** (obok przełącznika motywu) z 5 kulkami:
  żółty Media Expert (domyślny), zielony, czerwony, niebieski, pomarańczowy.
  Wybór zapisywany w `localStorage` (jak motyw — per urządzenie).
- Realizacja przez tokeny CSS: klasy `body.accent-nazwa` nadpisują
  `--primary`, `--primary-hover`, `--primary-text`, `--primary-light`
  oraz nowe `--primary-ring` (obwódka focusa) i `--primary-soft`
  (delikatne tło) — wcześniejsze twarde żółte `rgba(255,221,0,…)` i
  `#ffdd00` w kodzie (focus inputów, hover źródła zdjęć, kolarz
  ładowania) podmienione na zmienne.
- Motyw jasny: dla każdego akcentu ciemniejszy wariant `--primary-text`
  (`body.accent-x.light-theme`), analogicznie do `#8a7300` przy żółtym.
- Główne motywy (czarny/biały) bez zmian — zmienia się tylko akcent.
- Instrukcja: sekcja14 opisuje paletę.

## 2.3 — 2026-09-26 (serwis2, serwis)

### Dodane — moduły (wyłączanie opcji przez użytkownika)
- **Zakładka „Moduły" w Ustawieniach** — 6 przełączników: Kalendarz, Zdjęcia,
  Skaner QR, Katalog usług, Drukowanie, Kosz. Flagi żyją w tabeli `ustawienia`
  (wspólne dla PC i telefonu), domyślnie wszystko włączone — brak klucza =
  zachowanie sprzed 2.3. Nowy endpoint `api/ustawienia.php` (GET/POST).
- **Wyłączone = ukryte i zablokowane**: reguły CSS (`body.off-nazwa`),
  warunkowania w JS (przyciski na liście, monit telefoniczny, etykieta
  zapisu) **oraz guardy API** (`api/zdjecia.php` cały, `api/uslugi.php` zapis,
  kosz = `action=restore` + `DELETE`, przyjęcie ze zdjęciami przy wyłączonych
  zdjęciach). Ukończone moduły nie da się obsłużyć „na skróty" przez API.
- **Kalendarz full off** (decyzja): znika też pole „Planowany odbiór"
  (formularz + edycja + karta), kafle „Po terminie/Odbiory dziś/jutro",
  sortowanie po terminie. `date_planned` → kolumna nullable (migracja w
  `db()`), pusty termin = NULL; edycja bez terminu **zachowuje stary**
  (`COALESCE`), więc dane nie ubywają.
- **Kosz off = kasowanie całkiem ukryte** (decyzja): filtr Kosz + przyciski
  przenoszenia znikają, endpointy kasowania zwracają403 — nic nie da się
  skasować przez pomyłkę.
- Etykieta przycisku zapisu budowana z dwóch flag (matryca4 wariantów:
  „Zapisz, Drukuj i Dodaj do Kalendarza" / „Zapisz i drukuj" /
  „Zapisz i dodaj do kalendarza" / „Zapisz").
- Monit telefoniczny po zapisie: wzmianka o kalendarzu/druku pojawia się
  tylko, gdy dany moduł jest włączony.
- Instrukcja: sekcja15 „Moduły" + spis treści; sekcja14 z trzecią zakładką.
- Poprawka: `isOverdue/isPlannedToday/isPlannedTomorrow` ignorują pusty
  termin (wcześniej pusty ciąg daty liczył się jako „po terminie").

## 2.2 — 2026-09-25 (serwis2, serwis)

### Zmienione — poprawki widoku mobilnego (feedback z telefonu)
- **Wyszukiwanie na mobile: minimum 4 znaki numeru serwisowego** i pokazuje
  **tylko to jedno zgłoszenie** — dokładne dopasowanie numeru albo jedyne
  częściowe; przy kilu dopasowaniach karta się nie otwiera, tylko prośba
  „wpisz więcej cyfr numeru"; poniżej 4 znaków — nic się nie dzieje.
  (Wcześniej każde dopasowanie otwierało kartę + toast, a toasty się
  kumulowały i zasłaniały ekran telefonu.)
- **Toasty: zawsze tylko jeden naraz** — nowy komunikat usuwa poprzedni
  (`showToast` nie dokłada już stosu kart do ekranu).
- Karta zgłoszenia: przycisk **„Wydaj rower"** (tryb rozkazujący, zamiast
  „Wydano rower") dopóki rower nie został wydany; po wydaniu „Rower już wydany".
- Instrukcja: sekcje 3, 5 i 8 zaktualizowane.

## 2.1 — 2026-09-25 (serwis2, serwis)

### Zmienione — widok mobilny (telefon = przyjęcie i wydanie)
- **Na mobile ukryte: lista zgłoszeń, kafle dashboardu, filtry i sortowanie**
  (telefon służy do przyjęcia roweru i wydania; zostają formularz,
  wyszukiwarka i skaner QR). Klasa `body.is-mobile` z istniejącego
  `detectMobile()` (UA + dotyk ≤1024 px).
- **Wynik wyszukiwania na mobile = karta zgłoszenia zamiast listy**:
  wpisanie tekstu po 0,7 s (lub Enter) otwiera kartę pierwszego dopasowania,
  skan z kilkoma dopasowaniami też pokazuje pierwsze, brak dopasowania = komunikat.
  Kryteria wyszukiwania wyniesione do wspólnej funkcji `itemMatchesSearch()`.
- **Pełnoekranowy monit po zapisie z telefonu** (zamiast toastu): ile zdjęć
  dodano + „wymaga potwierdzenia na komputerze" + OK; kalendarz i wydruk
  uruchamia dopiero potwierdzenie na PC, więc z telefonu ich nie odpalamy.
- Karta zgłoszenia pokazuje info „zablokowane — wymaga potwierdzenia…"
  (na mobile lista z maską jest ukryta).
- Instrukcja: sekcje 4, 5 i 8 zaktualizowane.

## 2.0 — 2026-09-25 (serwis2, serwis)

### Zmienione — wariant kolorystyczny Media Expert
- **Nowa paleta: żółty `#FFDD00` + czerń + biel** (był pomarańcz na graficie);
  czerwień `#e2001a` na terminy ryzyka, niebieski `#3587ea` na „jutro",
  ciemne złoto `#8a7300` na akcenty tekstowe w jasnym motywie.
- Żółte tło + czarny tekst wszędzie tam, gdzie marka ME tak robi: przycisk
  Zapisz, aktywny filtr, aktywny kafelek dashboardu, status „W serwisie",
  chipy w kalendarzu, zakładki ustawień, licznik Kosza.
- Nagłówek bez zmian konstrukcji (tło jak w reszcie strony); podtytuł
  skrócony do „Panel Serwisowy".
- Logo przez CSS `filter`: czarne na jasnym motywie, żółte na ciemnym;
  nowa favicon — czarna kafelka + żółte koło z kluczem.
- Maska zablokowanego zgłoszenia: neutralna szara zamiast granatowej.
- `instrukcja.html` przebarwiona na tę samą paletę.
- **Restart numeracji: 1.24 → 2.0.** Poprzednia wersja skopiowana na FTP
  jako `/serwis-stary/` (kod bez `uploads/`).

## 1.24 — 2026-09-25 (serwis2)

### Zmienione — pasek filtrów
- **Z paska filtrów zniknęły przyciski zduplikowane z dashboardem**
  (W serwisie, Gotowe do odbioru, Dziś do wydania, Jutro, Po terminie) —
  te same widoki obsługuje się klikaniem kafli. Zostały: **Wszystkie,
  Odebrane, Kosz** (+ select sortowania).
- Usunięte liczniki `count-today/tomorrow/overdue` i nieużywana klasa CSS
  `.filter-count.count-info`; `updateFilterCounts()` liczy tylko Kosz,
  reszta idzie przez `updateDashboard()`. Logika filtrów `today/tomorrow/
  overdue/in_progress/completed` w `renderServicesList()` — bez zmian
  (kafle z niej korzystają).
- `instrukcja.html` — sekcja 5: kafle jako główna segregacja, lista
  pozostałych przycisków.
- `config.php` — `APP_VERSION` → `1.24`.

---

## 1.23 — 2026-09-25 (serwis2)

### Dodane — pakiet UX listy (analiza funkcji 10 + 11 + odbiory)
- **Dashboard podsumowań** — kafle nad listą: „W serwisie”, „Gotowe do
  odbioru”, „Po terminie”, „Odbiory dziś”, „Odbiory jutro” z liczbami
  aktualizowanymi przy każdym renderze. **Kliknięcie kafla włącza filtr**
  (podświetlenie aktywnego kafla), z przewinięciem do listy.
- **Filtr „Dziś do wydania”** — nowy przycisk z licznikiem (termin mija dziś,
  rower jeszcze nieodebrany); wspólny predykat `isPlannedToday()`.
- **Sortowanie listy** — select przy filtrach: termin odbioru (najbliższy /
  odległy), data przyjęcia (najnowsze / najstarsze), nazwa roweru (A–Z,
  `Intl.Collator('pl')`), wg statusu. Domyślnie: **najbliższy termin na
  górze**; wybór zapisywany w `localStorage` (`re_sort`).
- `isPlannedTomorrow()` wyklucza teraz też zgłoszenia odebrane (jak
  `isOverdue()`) — licznik „Jutro” = realnie do wydania jutro.
- `.filters` dostał `flex-wrap` (więcej przycisków + select na wąskich ekranach).
- `instrukcja.html` — sekcja 5 opisuje kafle, filtr „Dziś” i sortowanie.
- `config.php` — `APP_VERSION` → `1.23`.

---

## 1.22 — 2026-09-25 (serwis2)

### Zmienione — dymek kalendarza
- **Dymek „+N więcej” rozrasta się teraz „z komórki dnia”** zamiast
  odpinać się pod spodem: pozycjonowany dokładnie na prostokącie komórki
  (bez odstępu), nachodzi na nią i sąsiednie pole — wrażenie, że samo
  okienko kalendarza się powiększa. `transform-origin` przy górnej
  krawędzi komórki (przy dolnej, gdy brak miejsca na dole ekranu).
- **Animacja przejścia**: pojawianie i znikanie = fade + `scale(0.86 → 1)`
  (0,15–0,2 s, `cubic-bezier(0.2, 0.8, 0.3, 1)`), `visibility` z opóźnieniem
  przy zamykaniu. Stan zamknięty ma `pointer-events: none`, żeby
  zasłonięta komórka nie łapała kliknięć.
- Kliknięcie w tło dymka zamyka go (drugi tap na telefonie); procedura
  chowania sprawdza `:hover` — kursor trzymany nad dymkiem nie zamyka
  go po 0,3 s (dymek leży teraz na ścieżce kursora).
- `config.php` — `APP_VERSION` → `1.22`.

---

## 1.21 — 2026-09-25 (serwis2)

### Dodane — kalendarz
- **Dymek „+N więcej”** — po najechaniu kursorem (lub kliknięciu/tapnięciu
  na dotyku, także z fokusem klawiatury) na napis „+N więcej” w komórce
  dnia pokazuje się **powiększony widok dnia**: dymek z pełną listą
  wszystkich zgłoszeń danego dnia — nazwa, status i termin
  „przyjęcie → odbiór”, kolorywg statusu, ramka przy przekroczonym terminie.
  Kliknięcie pozycji otwiera kartę zgłoszenia (jak chip).
  - pozycjonowanie pod kotwicą, nad nią gdy brak miejsca; ukrywa się
    po ~0,3 s od odejścia kursorem, przy przewijaniu, zmianie miesiąca,
    zamknięciu kalendarza (przycisk / tło / Esc).
- `instrukcja.html` — sekcja 10 uzupełniona o obsługę „+N więcej”.
- `config.php` — `APP_VERSION` → `1.21`.

---

## 1.20 — 2026-09-25 (serwis2)

### Zmienione — favicon
- **`favicon.png` przekolorowany na pomarańcz strony** (`--primary: #f97316`)
  — poprzedni granatowy (`#1A3C5F`) był praktycznie niewidoczny na pasku
  zakładek. Kształt (klucz w kole) i białe akcenty bez zmian; krawędzie
  wygładzone w pomarańczu. Oryginał (granatowy) zachowany poza projektem.
- `config.php` — `APP_VERSION` → `1.20`.

---

## 1.19 — 2026-09-25 (serwis2)

### Zmienione — branding
- **Nowe logo** — `logo.png` (512×322, tło przezroczyste) w nagłówku panelu
  i na ekranie logowania zamiast pomarańczowego kafelka z ikoną SVG.
  Układ bez zmian (obrazek obok napisu „RoweryExpert”), rozmiar nieco większy
  niż dotychczas: 54 px wysokości (na mobile 44 px), szerokość automatyczna.
- **Nowe favicon** — `favicon.png` (291×283, tło przezroczyste) zamiast
  `favicon.svg`; podpięte też w `instrukcja.html`.
- `config.php` — `APP_VERSION` → `1.19`.

---

## 1.18 — 2026-09-25 (serwis2)

### Zmienione — kalendarz
- **Rower już odebrane też widoczne w kalendarzu** — chipy statusu
  „Odebrany” w szarym tle (`--card-lighter` + szara krawędź, opacity .85),
  w tej samej rozciągniętej skali od przyjęcia do odbioru. Kolejność
  chipów w dniu: do wykonania → gotowe → odebrane. Nowa pozycja
  w legendzie („Odebrane”). Nadal bez zgłoszeń z kosza.
- `instrukcja.html` — sekcja o kalendarzu zaktualizowana (szare chipy
  odebranych rowerów).
- `config.php` — `APP_VERSION` → `1.18`.

---

## 1.17 — 2026-09-25 (serwis2)

### Dodane
- **Strona „Instrukcja panelu”** — osobny plik `instrukcja.html`
  (samodzielny, styl wariantu A, `noindex`): 14 sekcji krok po kroku —
  logowanie, przyjęcie roweru, statusy, zgłoszenia z mobile, filtry
  i wyszukiwanie, skaner QR, numer serwisowy, karta podglądu, edycja
  i zdjęcia, kalendarz panelu, Kalendarz Google i druk, wydruk,
  kosz, ustawienia. Spis treści z kotwicami, przycisk „Wróć do panelu”.

### Zmienione
- **Stopka** — delikatny link „Instrukcja” obok numeru wersji
  (podkreślenie na hover w kolorze akcentu).
- **Usunięty opis „Dane przechowywane w lokalnej bazie MySQL”** ze stopki.
- `config.php` — `APP_VERSION` → `1.17`.

---

## 1.16 — 2026-09-25 (serwis2)

### Zmienione — kalendarz
- **Zakres pobytu w serwisie zamiast samego terminu** — zgłoszenie
  przyjęte 25.09 z terminem 27.09 pojawia się jako chip na każdy dzień
  25, 26 i 27 (iteracja od `date_in` do `date_planned`, cap 90 dni;
  odporny na odwrócony zakres). Podpowiedź na chipie pokazuje cały
  zakres „przyjęcie → odbiór”.
- **Mniejsze odstępy w siatce** — `gap` krat kalendarza 4 px → 2 px.
- `config.php` — `APP_VERSION` → `1.16`.

---

## 1.15 — 2026-09-25 (serwis2)

### Dodane
- **Kalendarz terminów (widok miesięczny)** — nowa ikonka kalendarza w nagłówku
  otwiera modal z siatką miesiąca (tydzień od poniedziałku, dzisiejsza data
  podświetlona). W dniach z terminami widnieją chipy zgłoszeń:
  pomarańczowe „W serwisie”, zielone „Gotowe do odbioru”, czerwona ramka
  = „Po terminie”. Pokazywane są tylko zgłoszenia nieodebrane (bez kosza),
  maks. 3 na dzień + licznik „+N więcej”.
- **Nawigacja po miesiącach** (‹ ›) i legenda pod siatką.
- **Kliknięcie chipu** zamyka kalendarz i otwiera kartę podglądu wybranego
  zgłoszenia (z przyciskiem „Wydano rower”).
- `config.php` — `APP_VERSION` → `1.15`.

---

## 1.14 — 2026-09-25 (serwis2)

### Zmienione — wydruk (egzemplarz dla klienta)
- **Podpis klienta i pieczątka serwisu przesunięte na dół** — blok podpisów
  ma `margin-top: auto`, a stopka z adresem przestała go „podciągać”
  (`margin-top: 0`) — podpisy leżą teraz tuż nad grubą kreską
  oddzielającą adres od reszty, zamiast tuż pod tabelą danych.
- `config.php` — `APP_VERSION` → `1.14`.

---


## 1.13 — 2026-09-25 (serwis2)

### Zmienione — wydruk (egzemplarz serwisu)
- **Usunięta sekcja „Dodatkowe uwagi” z kreseczkami** — niepotrzebna,
  bo wykonane czynności są teraz w jednym polu.
- **Pole „Wykonane czynności” wypełnia całą wolną wysokość kolumny**
  (`flex-grow` w wydruku A4) — można pisać dużo więcej niż wcześniej.
- **Kod QR (etykieta roweru) przeniesiony na sam dół** kolumny,
  bez napisu „Etykieta roweru…” — zostaje sam kwadrat QR z numerem.
- Usunięty nieużywany CSS kreseczek (`.service-notes-lines`).
- `config.php` — `APP_VERSION` → `1.13`.

---


## 1.12 — 2026-09-25 (serwis2)

### Dodane
- **Wyszukiwanie po numerze serwisowym** — pole „Szukaj” dopasowuje teraz
  też `serviceNo` (oraz notatki „wykonane czynności”), nie tylko nazwę,
  telefon i opis usterki.
- **Skaner QR z aparatu (mobile)** — ikonka aparatu obok paska wyszukiwania
  otwiera nakładkę z podglądem z kamery i odczytuje kod QR z etykiety
  roweru. Detekcja: natywny `BarcodeDetector` (Chrome/Android),
  fallback `jsQR` z CDN (iOS Safari), komunikat o ręcznym wpisaniu numeru,
  gdy skaner niedostępny. Po odczycie: reset filtra, wpisanie numeru
  w wyszukiwarkę, dopasowanie i otwarcie karty podglądu.
- **Karta podglądu zgłoszenia (bez edycji)** — modal w stylu edycji,
  tylko do odczytu: numer serwisowy, rower, status, daty, telefon
  (klikalny), opis usterki i wykonane czynności + przycisk
  **„Wydano rower”** zmieniający status na „Odebrany” (wyłączony,
  gdy rower już wydany lub zgłoszenie jest w koszu).
- `config.php` — `APP_VERSION` → `1.12`.

---

## 1.11 — 2026-09-24 (serwis2)

### Dodane
- **Edycja zgłoszenia** — przycisk „Edytuj” na karcie otwiera modal
  (nazwa roweru, daty, telefon, opis usterki, wykonane czynności).
  Zmiany zapisuje nowe `action=update` w API (pełna walidacja po stronie serwera).
- **Notatki serwisowe „Wykonane czynności”** — pole `service_notes` w bazie,
  edytowane w modalu, widoczne na karcie zgłoszenia i na wydruku
  (egzemplarz serwisu, pod opisem usterki).
- **Kosz zamiast twardego usuwania** — `DELETE` ustawia `deleted_at`
  (soft delete). Filtr „Kosz” z licznikiem, akcje „Przywróć” i
  „Usuń trwale” (`?purge=1`, usuwa też zdjęcia). Karty w koszu są
  wyszarzone, bez przełączania statusu.
- **Numer serwisowy + etykieta QR** — `service_no` w formacie `RO-ROK-ID`
  (nadawany przy tworzeniu, backfill dla starych zgłoszeń, indeks UNIQUE),
  odznaka na karcie, wiersz „Numer serwisowy” na obu egzemplarzach
  wydruku oraz osobny kod QR do naklejenia na rower.
- **Filtr „Jutro” i „Po terminie”** — liczniki na przyciskach filtrów,
  czerwona odznaka „Po terminie” przy terminie na karcie
  (aktywna do momentu odbioru roweru).
- `config.php` — migracje kolumn `service_no`, `service_notes`, `deleted_at`
  (auto-migracja przez `SHOW COLUMNS`), `APP_VERSION` → `1.11`.

---


## 1.10 — 2026-09-24 (serwis2)

### Naprawione
- **Rowerzysta z nakładki „Zapisywanie” nie pojawia się już na wydruku.**
  Reguła `@media print` ukrywała `#app-container`, toasty i modale, ale
  nie `.upload-overlay` — a druk uruchamia się w trakcie odjazdu
  rowerzysty (nakładka ma wtedy `opacity: 1`). Do listy ukrywanych na
  wydruku dodano: `.upload-overlay`, `.lightbox` i `.login-screen`.
- `config.php` — `APP_VERSION` → `1.10`.

---

## 1.9 — 2026-09-24 (serwis2)

### Zmienione — animacja zapisywania (wg filozofii design engineering)
- **Sylwetka rowerzysty bardziej jak człowiek:**
  - biodro osadzone na siodełku (wcześniej zawisało pod ramą),
  - ramię z wygiętym łokciem prowadzące do kierownicy (wcześniej prosta
    kreska),
  - dwie nogi w pozycji pedałowania na korbie (stopy na promieniu korby),
  - **kask** z daszkiem i krótki odcinek szyi łączący głowę z tułowiem,
  - siodełko z obejmą podsiodłową narysowane na ramie.
- **Zamiast znikania nakładki — odjazd:** po zapisie zamiast fade-out
  całość działa jak sekwencja:
  1. przyciemnienie tła równolegle rozmywa się do zera (450 ms, strong
     ease-out),
  2. rowerzysta **przyspiesza i odjeżdża za prawą krawędź ekranu**
     (700 ms, custom `cubic-bezier(.35,.05,.9,.55)` — start od razu,
     potem przyspieszenie jak przy pedałowaniu),
  3. napis „Zapisywanie" znika szybko (300 ms),
  4. puste okno chowa się na końcu (opóźnienie 680 ms).
- Wyłącznie `transform`/`opacity` na animowanych elementach; sekwencja
  sterowana jedną klasą `.leaving` + jednym timerem w JS (odwołalnym,
  gdyby zapis uruchomił się ponownie w trakcie).
- `config.php` — `APP_VERSION` → `1.9`.

---

## 1.8 — 2026-09-24 (serwis2)

### Poprawione — wygląd wyboru dat
- **`color-scheme`** na `body` (dark) i `body.light-theme` (light) —
  natywny kalendarz systemowy (okno podpowiedzi, klawiatura) teraz
  przejmuje motyw strony zamiast wymuszać systemowy.
- **Ikona kalendarza** przy polach dat: wyrównana do prawej krawędzi
  (padding przycisku, bez przesuwania tekstu), przygaszona do 45%,
  pełna widoczność na hover, w ciemnym motywie odwrócona na jasną
  (`filter: invert(1)`).
- Usunięte natywne strzałki spin/clear przy polu dat.
- Tekst daty dziedziczy kolor i font motywu (`::-webkit-datetime-edit`).
- `config.php` — `APP_VERSION` → `1.8`.

---

## 1.7 — 2026-09-24 (serwis2, wariant A)

### Zmienione — reset tokenów designu
- **Typografia:** Inter + Outfit → **Barlow** (jedna rodzina, 400–700,
  wzorowana na typografii znaków drogowych). font-family wymienione w całym
  pliku łącznie z wydrukiem A4.
- **Paleta:** niebieski slate → **grafit `#15171A`/`#1E2126`** (ciemny) i
  **papier `#F5F4F1`** (jasny). Pomarańcz marki `#F97316` zostaje jako
  główny akcent. Nowa zmienna `--locked: #C8F751` (hi-viz z kamizelki
  rowerowej) — wyłącznie dla zgłoszeń zablokowanych.
- **Płaskie powierzchnie:** usunięto wszystkie gradienty (logo, tekst
  nagłówka, przyciski primary/calendar/danger/confirm) i cienie na
  przyciskach — kolor płaski, hover przez zmianę odcienia.
- **Bez dekoracyjnego ruchu:** usunięto `scale(1.01)` z kart, `translateY`
  z przycisków i `scale(1.05}` z ikon nagłówka.
- **Sentence case:** zdjęto `text-transform: uppercase` z etykiet pól,
  etykiet szczegółów, badge'y statusów i noty maski.
- **Jedna skala radiusów:** 10 / 8 / 6 px (wcześniej 16/12/8 + 20 px),
  pill (999 px) tylko przy statusach i filtrach.
- **Szyna statusu:** karta zgłoszenia ma 4 px kolorowy lewy border —
  pomarańcz (w serwisie), zieleń (gotowy), grafit (odebrany),
  **hi-viz limonka (zablokowane)** — status widać bez czytania.
- **Kontrast:** tło inputów w ciemnym motywie `rgba(0,0,0,.1)` →
  `rgba(255,255,255,.06)`; nota maski w motywie jasnym dostaje ciemny
  tekst zamiast białego.
- **Poprawka:** zmienna `--card-lighter` nie była zdefiniowana (domyślne
  tła elementów wypadały na przezroczyste) — zdefiniowana w obu motywach.
- **Domyślny motyw jasny** zamiast ciemnego; przy pierwszym wejściu
  respektowany `prefers-color-scheme`.
- **`prefers-reduced-motion`** — systemowy wyłącznik animacji.
- **Puste stany:** dwa komunikaty zamiast jednego — zaproszenie do
  formularza (pusta baza) vs. zmiana kryteriów (filtr/wyszukiwarka).
- `config.php` — `APP_VERSION` → `1.7`.

---

## 1.6 — 2026-09-24

### Zmienione
- **Animacja przy zapisywaniu zgłoszenia** — obracające się koło zębate
  zastąpione **prostą animacją rowerzysty jadącego na rowerze**:
  - obracające się oba koła (szprychy, `@keyframes spin`),
  - lekkie bujanie sylwetki kierowcy (`cyclist-bob`),
  - pulsujące linie szybkości za rowerem (`speed-pulse`),
  - kolor i cień jak poprzedni spinner (`--primary`, drop-shadow),
  - nakładka nadal z podpisem „Zapisywanie”.
- `config.php` — `APP_VERSION` → `1.6`.

---

## 1.5 — 2026-09-24

### Zmienione
- **Wszystkie monity potwierdzania w jednym stylu modalnym** — zamieniono
  ostatnie dwa natywne `confirm()` na wspólny modal:
  - **„Usuń usługę”** — potwierdzenie usuwania usługi z katalogu
    (w treści podana nazwa usługi),
  - **„Usuń zdjęcie”** — potwierdzenie usuwania zdjęcia z serwera.
- W aplikacji nie występuje już ani jedno okno systemowe przeglądarki.
- `config.php` — `APP_VERSION` → `1.5`.

---

## 1.4 — 2026-09-24

### Zmienione
- **Potwierdzanie usuwania zgłoszenia** — natywne okno przeglądarki
  (`confirm()`) zastąpione **modalem w stylu strony**: ciemna karta z
  ikoną ostrzeżenia, tytułem, opisem (z nazwą roweru i informacją, że
  operacji nie można cofnąć) oraz przyciskami **Anuluj** (neutralny) i
  **Usuń** (czerwony gradient, styl `.btn-danger` jak reszta UI).
- Modal zamyka się: Esc, kliknięciem w tło lub krzyżykiem — wtedy operacja
  jest anulowana.
- `config.php` — `APP_VERSION` → `1.4`.

---

## 1.3 — 2026-09-24

### Zmienione
- **Opis maski** zgłoszeń oczekujących: „Zgłoszenie zamazane” →
  **„Zgłoszenie zablokowane”** (+ nadal „wymaga potwierdzenia na komputerze”).
- **Etykieta przycisku** formularza: „Tylko zapisz w bazie” →
  **„Zapis do bazy”**.

### Naprawione
- **Ucinana pomarańczowa obramówka przy najechaniu myszką** na zapisane
  zgłoszenie — karty mają `transform: scale(1.01)` na hover, a kontener
  `.services-list` ma `overflow-y: auto` i padding tylko po prawej stronie,
  więc powiększona karta była przycinana po lewej. Zmieniono padding
  kontenera na obustronny (`0.3rem 0.6rem`).
- `config.php` — `APP_VERSION` → `1.3`.

---

## 1.2 — 2026-09-23

### Naprawione
- **Serwer zwracał starą wersję strony** — LiteSpeed cache'ował wyrenderowany
  HTML `serwis.php` (nagłówek `x-litespeed-cache: hit`), więc zmiany w plikach
  nie były widoczne w przeglądarce, dopóki cache nie wygasł. Dodatkowo
  cache'owana była treść zależna od sesji (ekran logowania), co mogło
  powodować błędny stan po zalogowaniu.

### Zmienione
- `serwis.php` — dodano nagłówki `Cache-Control: no-store, no-cache,
  must-revalidate` + `Pragma` + `Expires`, aby strona zawsze ładowała się
  bezpośrednio z PHP.
- `config.php` — `APP_VERSION` → `1.2`.

---

## 1.1 — 2026-09-23

### Dodane
- **Numer wersji w stopce strony** — stała `APP_VERSION` w `config.php`,
  renderowana w `<footer>` jako „Wersja 1.1”.
- **Ten plik (CHANGELOG.md)** — od tej pory każda zmiana na stronie
  otrzymuje wpis z numerem wersji i datą.

### Zmienione
- `config.php` — dodano stałą `APP_VERSION` z komentarzem opisującym
  konwencję wersjonowania.
- `serwis.php` — stopka uzupełniona o linię z numerem wersji.

---

## 1.0 — 2026-09-23

### Dodane
- **Potwierdzanie zgłoszeń z mobile na komputerze:**
  - Nowa kolumna `confirmed` w tabeli `zgloszenia` (migracja automatyczna,
    istniejące rekordy = potwierdzone).
  - Zgłoszenia tworzone na telefonie (`source=mobile`) trafiają jako
    `confirmed = 0` — **zamazane** (blur), z przyciskiem **Potwierdź**
    widocznym wyłącznie na komputerze.
  - Na telefonie nad zamazaniem wyświetlane jest info, że zgłoszenie
    wymaga potwierdzenia na komputerze.
  - Kliknięcie **Potwierdź** na PC jednocześnie:
    1. odblokowuje zgłoszenie (`action=confirm` w API),
    2. otwiera dodanie wydarzenia do Kalendarza Google,
    3. uruchamia drukowanie potwierdzenia A4.
  - Zgłoszenia tworzone na komputerze działają jak wcześniej
    (druk + kalendarz od razu, bez zamazania).

### Zmienione
- `config.php` — kolumna `confirmed`, migracja, `map_zgloszenie()`
  zwraca `confirmed`.
- `api/zgloszenia.php` — parametr `source` przy `action=create`,
  nowa akcja `action=confirm`.
- `serwis.php` — maska `.pending-mask` (backdrop blur), przycisk
  `.btn-confirm`, funkcja `confirmItem()`, wysyłka `source` przy zapisie.
