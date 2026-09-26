# Changelog — RoweryExpert Panel Serwisowy (serwis2)

> Kopia rozwojowa projektu `serwis`. Wariant A: pełny reset tokenów designu.

Konwencja: wersja `X.Y`
- `X` — większa zmiana zakresu działania aplikacji
- `Y` — każda kolejna modyfikacja strony

Numer bieżącej wersji zapisywany jest w `config.php` (`APP_VERSION`)
i wyświetlany w stopce strony.

---

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
