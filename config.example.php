<?php
declare(strict_types=1);

/* UWAGA: to jest WZORZEC konfiguracji, nie uruchamiaj go na serwerze.
   install.php generuje z niego plik config.php i uzupełnia pola UZUPELNIJ.
   Ręczna instalacja: skopiuj na config.php i uzupełnij wartości. */

/* ---------------------------------------------------------------
 | Numer wersji aplikacji - pokazywany w stopce strony.
 | Zwiększaj przy każdej istotnej zmianie i dopisuj wpis w CHANGELOG.md
 |   1.0  -> pierwsza udokumentowana wersja
 |   1.1  -> numer wersji w stopce + plik CHANGELOG.md
 |   1.2  -> wylaczenie cache'owania strony (stary HTML z LiteSpeed)
 |   1.3  -> opis "zablokowane", przycisk "Zapis do bazy", fix hovera listy
 |   1.4  -> modal potwierdzania usuwania zgloszenia zamiast okna przegladarki
 |   1.5  -> wszystkie monity potwierdzania w stylu modalnym
 |   1.6  -> animacja rowerzycy zamiast kola zebatego przy zapisywaniu
 |   1.7  -> wariant A: reset tokenow designu (Barlow, grafit/papier, plaskie kolory)
 |   1.8  -> styl wyboru dat zgodny z motywem (color-scheme + ikona kalendarza)
 |   1.9  -> realniejsza sylwetka rowerzysty + odjazd za krawedz ekranu
 |   1.10 -> nakladka rowerzysty i lightbox ukrywane na wydruku
 |   1.11 -> edycja zgloszen, notatki serwisowe, kosz, numer serwisowy QR, filtry terminow
 |   1.12 -> wyszukiwanie po numerze serwisowym, skaner QR z aparatu, karta podgladu z wydaniem
 |   1.13 -> wydruk: kolumna serwisu bez "Dodatkowych uwag", pole czynnosci na cala wysokosc, QR na dole
 |   1.14 -> wydruk: podpis i pieczatka klienta przypiety w dol, tuz nad gruba linia stopki
 |   1.15 -> kalendarz terminow (widok miesieczny) z przejsciem do zgloszenia
 |   1.16 -> kalendarz: wpis na cale pobycie w serwisie (od przyjecia do odbioru), mniejsze odstepy
 |   1.17 -> strona instrukcji + link w stopce, usuniety opis o lokalnej bazie MySQL
 |   1.18 -> kalendarz: rowery odebrane pokazywane na szaro
 |   1.19 -> nowe favicon.png i logo.png w naglowku / na loginie
 |   1.20 -> favicon przekolorowany na pomarancz strony (#f97316)
 |   1.21 -> kalendarz: dymek „wiecej" z pelna lista danego dnia
 |   1.22 -> dymek rozwija sie z komorki dnia + animacja przejscia
 |   1.23 -> dashboard podsumowan, filtr „Dziś do wydania", sortowanie listy
 |   1.24 -> filtry zredukowane do: Wszystkie / Odebrane / Kosz (reszta = kafle)
 |   2.0  -> wariant kolorystyczny Media Expert (zolty #FFDD00 + czern), podtytul "Panel Serwisowy"
 |   2.1  -> mobile: ukryta lista/kafle/filtry, wynik wyszukiwania = karta zgloszenia, monit "potwierdz na PC"
 |   2.2  -> mobile: szukanie po numerze serwisowym od 4 znakow (tylko konkretne zgl.), przycisk "Wydaj rower"
 |   2.3  -> moduly: uzytkownik wylacza opcje w Ustawieniach (kalendarz, zdjecia, skaner, uslugi, druk, kosz)
 |   2.4  -> akcent: przełącznik koloru akcentu przy motywie (zolty ME, zielony, czerwony, niebieski, pomaranczowy)
 |   2.5  -> akcent: rower w logo i faviconka tez zmieniaja kolor razem z akcentem (warianty: logo-* oraz favicon-*.png)
 |   2.6  -> powitanie po zalogowaniu: okno "Podsumowanie dnia" z liczba odbiorow na dzis i jutro + OK
 |   2.7  -> wykonane czynnosci (checkboxy z katalogu) + karta wydania roweru z automatycznym drukiem
 |   3.0  -> konta uzytkownikow + sesje w bazie (14 dni), login i haslo na ekranie
 |           logowania, limit nieudanych prob, wlasna nazwa cookie (fork dziala
 |           rownolegle ze stara wersja) + rozdrobnienie serwis.php na assets/
 |   3.8  -> instalator install.php (wizard), dane instancji jako stale w config
 |           (adres/telefon/link Google/URL), gate brakujacego config.php
 |           w serwis.php i api/*, README + LICENSE (dystrybucja publiczna)
 --------------------------------------------------------------- */
const APP_VERSION = '3.8.4';

/* ---------------------------------------------------------------
 | Konfiguracja bazy danych (MySQL) i pomocnicze funkcje wspólne
 --------------------------------------------------------------- */

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'UZUPELNIJ';
const DB_USER = 'UZUPELNIJ';
const DB_PASS = 'UZUPELNIJ';

/* ---------------------------------------------------------------
 | Dane tej instancji serwisu - ustawiane przez install.php
 |   SERVICE_ADDRESS -> ulica (stopka wydruku zlecenia)
 |   SERVICE_CITY    -> kod pocztowy i miasto (stopka wydruku)
 |   SERVICE_PHONE   -> telefon serwisu (stopka wydruku)
 |   GOOGLE_MAPS_URL -> link do wizytki Google (QR "Oceń nas" na wydruku)
 |   SITE_URL        -> opcjonalny adres URL panelu (pusty = bez zmian)
 | Nazwa/marka RoweryExpert jest stała (sieć serwisów) - nie jest
 | konfigurowalna. Wzorzec tych stalych: config.example.php
 --------------------------------------------------------------- */
const SERVICE_ADDRESS = 'UZUPELNIJ';
const SERVICE_CITY = 'UZUPELNIJ';
const SERVICE_PHONE = 'UZUPELNIJ';
const GOOGLE_MAPS_URL = 'UZUPELNIJ';
const SITE_URL = '';

const UPLOAD_DIR = __DIR__ . '/uploads/zdjecia';
const UPLOAD_URL = 'uploads/zdjecia';
const MAX_PHOTO_BYTES = 10 * 1024 * 1024; // 10 MB na zdjęcie
const MAX_PHOTOS_TOTAL_BYTES = 100 * 1024 * 1024; // 100 MB łącznie na wszystkie zdjęcia
const MAX_PHOTOS_PER_REQUEST = 20;        // maks. zdjęć w jednym wgraniu (limit serwera max_file_uploads)
const ALLOWED_PHOTO_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
const STATUSES = ['in_progress', 'completed', 'picked_up'];

/* ---------------------------------------------------------------
 | Uwierzytelnianie 3.0: konta użytkowników + sesje w bazie (14 dni)
 | APP_PASSWORD -> hasło konta "admin" tworzonego przy pierwszym
 |                 uruchomieniu (stare hasło aplikacji zostaje)
 | AUTH_COOKIE  -> fork ma WŁASNĄ nazwę cookie (path=/), żeby sesja
 |                 nie kolidowała ze starą wersją działającą równolegle
 --------------------------------------------------------------- */
const APP_PASSWORD = 'UZUPELNIJ';

const AUTH_COOKIE = 'UZUPELNIJ';
const SESSION_TTL = 14 * 86400;         // sesja ważna 14 dni (ślizgająca)
const LOGIN_MAX_ATTEMPTS = 5;           // tyle nieudanych prób logowania...
const LOGIN_ATTEMPT_WINDOW = 15 * 60;   // ...w tym oknie (w sekundach)

/** Zwraca hash hasła aplikacji (seeded z APP_PASSWORD przy pierwszym uruchomieniu). */
function app_password_hash(): string
{
    $stmt = db()->prepare('SELECT wartosc FROM ustawienia WHERE klucz = ?');
    $stmt->execute(['app_password_hash']);
    $hash = $stmt->fetchColumn();

    if ($hash === false || $hash === null || $hash === '') {
        $hash = password_hash(APP_PASSWORD, PASSWORD_DEFAULT);
        db()->prepare(
            'INSERT INTO ustawienia (klucz, wartosc) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)'
        )->execute(['app_password_hash', $hash]);
    }

    return (string) $hash;
}

/** Zmiana hasła BIEŻĄCEGO konta. Zwraca null przy sukcesie albo komunikat błędu. */
function change_own_password(string $current, string $next): ?string
{
    $user = auth_user();
    if ($user === null) {
        return 'Brak sesji - zaloguj się ponownie.';
    }
    if (!password_verify($current, (string) $user['password_hash'])) {
        return 'Aktualne hasło jest nieprawidłowe.';
    }
    if (strlen($next) < 6) {
        return 'Nowe hasło musi mieć min. 6 znaków.';
    }
    db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
        ->execute([password_hash($next, PASSWORD_DEFAULT), $user['id']]);
    // Pozostałe sesje tego konta wylogowujemy (bieżąca zostaje)
    db()->prepare('DELETE FROM sesje WHERE user_id = ? AND token_hash <> ?')
        ->execute([$user['id'], (string) $user['session_hash']]);
    return null;
}

// --- USTAWIENIA I MODUŁY PANELU ---
// Tabela ustawienia (klucz -> wartosc) juz istnieje (trzyma m.in. hash hasla).

/** Odczyt ustawienia z bazy; brak klucza = wartosc domyslna. */
function setting_get(string $klucz, string $domyslna = ''): string
{
    $stmt = db()->prepare('SELECT wartosc FROM ustawienia WHERE klucz = ?');
    $stmt->execute([$klucz]);
    $row = $stmt->fetch();
    return $row ? (string) $row['wartosc'] : $domyslna;
}

/** Zapis ustawienia (nadpisanie istniejacego). */
function setting_set(string $klucz, string $wartosc): void
{
    db()->prepare(
        'INSERT INTO ustawienia (klucz, wartosc) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)'
    )->execute([$klucz, $wartosc]);
}

/* ---------------------------------------------------------------
 | Dane instancji serwisu (adres, telefon, link Google, URL panelu).
 | Wartosci mozna edytowac po instalacji z panelu admina (zakladka
 | "Dane serwisu" w ustawieniach) - zapis do tabeli ustawienia.
 | Brak zapisu w bazie = wartosc domyslna ze stalych config.php.
 | Klucze: service_address, service_city, service_phone,
 |         google_maps_url, site_url
 --------------------------------------------------------------- */
function dane_instancji(): array
{
    return [
        'adres'      => setting_get('service_address', SERVICE_ADDRESS),
        'miasto'     => setting_get('service_city', SERVICE_CITY),
        'telefon'    => setting_get('service_phone', SERVICE_PHONE),
        'maps_url'   => setting_get('google_maps_url', GOOGLE_MAPS_URL),
        'site_url'   => setting_get('site_url', SITE_URL),
    ];
}

/** Wersja aplikacji: z bazy (aktualizowana przez do_update), fallback: stala config. */
function wersja_aplikacji(): string
{
    return setting_get('installed_version', APP_VERSION);
}

/** Pelna lista modulow, ktore moga byc wylaczone przez uzytkownika. */
function moduly_dostepne(): array
{
    return ['kalendarz', 'zdjecia', 'skaner', 'uslugi', 'druk', 'kosz', 'kolorystyka', 'powitanie', 'karta_wydania', 'wykonane'];
}

/**
 * Wlączone moduly (nazwa => bool). Odczyt z cache - jedno zapytanie na żądanie.
 * Brak klucza w bazie = modul wlaczony (kompatybilnosc wstecz: przed 2.3 wszystko bylo widoczne).
 */
function moduly(bool $refresh = false): array
{
    static $cache = null;
    if ($refresh) {
        $cache = null;
    }
    if ($cache === null) {
        $raw = setting_get('moduly', '');
        $saved = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        $cache = [];
        foreach (moduly_dostepne() as $m) {
            $cache[$m] = array_key_exists($m, $saved) ? (bool) $saved[$m] : true;
        }
    }
    return $cache;
}

/** Czy dany modul jest wlaczony? */
function modul(string $nazwa): bool
{
    return (bool) (moduly()[$nazwa] ?? false);
}

/** Statystyki zdjęć: liczba oraz zajęte miejsce (w bajtach). */
function photos_stats(): array
{
    $row = db()->query(
        'SELECT COUNT(*) AS c, COALESCE(SUM(size_bytes), 0) AS bytes FROM zdjecia'
    )->fetch();
    return [
        'photos' => (int) $row['c'],
        'bytes'  => (int) $row['bytes'],
    ];
}

/** Lista skonfigurowanych usług. */
function uslugi_list(): array
{
    $rows = db()->query('SELECT id, nazwa FROM uslugi ORDER BY id ASC')->fetchAll();
    return array_map(static fn(array $r) => [
        'id'    => (int) $r['id'],
        'nazwa' => $r['nazwa'],
    ], $rows);
}

/** Dodaje usługę. Zwraca null przy sukcesie albo komunikat błędu. */
function uslugi_add(string $nazwa): ?string
{
    $nazwa = trim($nazwa);
    if ($nazwa === '') {
        return 'Podaj nazwę usługi.';
    }
    $nazwa = function_exists('mb_substr') ? mb_substr($nazwa, 0, 255) : substr($nazwa, 0, 255);
    try {
        db()->prepare('INSERT INTO uslugi (nazwa) VALUES (?)')->execute([$nazwa]);
    } catch (PDOException) {
        return 'Taka usługa już istnieje.';
    }
    return null;
}

/** Usuwa usługę po id. Zwraca true, jeśli coś skasowano. */
function uslugi_remove(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM uslugi WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

/** Czy token wygląda jak nasz (64 znaki hex)? */
function auth_cookie_plausible(string $token): bool
{
    return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
}

/** Czy żądanie idzie po HTTPS (liczymy też nagłówek reverse proxy). */
function auth_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Opcje cookie sesji (wspólne dla ustawiania i kasowania). */
function auth_cookie_options(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => auth_is_https(),
    ];
}

/**
 * Dane bieżącego użytkownika z sesji albo null. Cache na żądanie.
 * Zwraca wiersz users + 'session_id'/'session_hash' z aktywnej sesji.
 * Token w bazie trzymany jest wyłącznie jako hash (wyciek bazy
 * nie daje możliwości przejęcia sesji).
 */
function auth_user(): ?array
{
    static $cache = null;
    static $loaded = false;
    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    $token = (string) ($_COOKIE[AUTH_COOKIE] ?? '');
    if ($token === '' || !auth_cookie_plausible($token)) {
        return null;
    }
    $hash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT u.id, u.login, u.rola, u.aktywny, u.must_change_password,
                s.id AS session_id, s.token_hash AS session_hash, s.expires_at
         FROM sesje s
         JOIN users u ON u.id = s.user_id
         WHERE s.token_hash = ? AND u.aktywny = 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    if ((string) $row['expires_at'] <= date('Y-m-d H:i:s')) {
        db()->prepare('DELETE FROM sesje WHERE id = ?')->execute([(int) $row['session_id']]);
        return null;
    }
    // Sesja ślizgająca: przedłużamy, gdy została mniej niż połowa czasu
    if (strtotime((string) $row['expires_at']) < time() + intdiv(SESSION_TTL, 2)) {
        $newExp = date('Y-m-d H:i:s', time() + SESSION_TTL);
        db()->prepare('UPDATE sesje SET expires_at = ? WHERE id = ?')
            ->execute([$newExp, (int) $row['session_id']]);
        setcookie(AUTH_COOKIE, $token, auth_cookie_options(time() + SESSION_TTL));
        $row['expires_at'] = $newExp;
    }

    $cache = $row;
    return $cache;
}

/** Czy użytkownik jest zalogowany? */
function auth_is_authenticated(): bool
{
    return auth_user() !== null;
}

/** Zalogowany użytkownik (albo null). */
function current_user(): ?array
{
    return auth_user();
}

/** Czy klucz logowania przekroczył limit nieudanych prób? */
function login_blocked(string $key): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE klucz = ? AND created_at > ?'
    );
    $stmt->execute([$key, date('Y-m-d H:i:s', time() - LOGIN_ATTEMPT_WINDOW)]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Logowanie (login + hasło). Zwraca null przy sukcesie albo komunikat błędu.
 * Komunikat jest celowo uniwersalny - nie zdradza, czy login istnieje.
 */
function auth_login(string $login, string $password): ?string
{
    $login = trim($login);
    $key = $login . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');

    db()->exec('DELETE FROM sesje WHERE expires_at < NOW()');   // sprzątanie starych sesji
    db()->prepare('DELETE FROM login_attempts WHERE created_at < ?')
        ->execute([date('Y-m-d H:i:s', time() - LOGIN_ATTEMPT_WINDOW)]);
    if (login_blocked($key)) {
        return 'Zbyt wiele nieudanych prób - spróbuj ponownie za kilka minut.';
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE login = ? AND aktywny = 1');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        db()->prepare('INSERT INTO login_attempts (klucz) VALUES (?)')->execute([$key]);
        return 'Nieprawidłowy login lub hasło.';
    }
    db()->prepare('DELETE FROM login_attempts WHERE klucz = ?')->execute([$key]);

    $token = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO sesje (token_hash, user_id, expires_at) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $token), (int) $user['id'],
                   date('Y-m-d H:i:s', time() + SESSION_TTL)]);
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
        ->execute([(int) $user['id']]);

    setcookie(AUTH_COOKIE, $token, auth_cookie_options(time() + SESSION_TTL));
    return null;
}

/** Wylogowanie - kasuje sesję w bazie (nie tylko cookie) i czyści cookie. */
function auth_logout(): void
{
    $token = (string) ($_COOKIE[AUTH_COOKIE] ?? '');
    if ($token !== '' && auth_cookie_plausible($token)) {
        db()->prepare('DELETE FROM sesje WHERE token_hash = ?')
            ->execute([hash('sha256', $token)]);
    }
    setcookie(AUTH_COOKIE, '', auth_cookie_options(time() - 3600));
}

/** Dla endpointów API - przerywa żądanie 401, gdy brak autoryzacji. */
function auth_require(): void
{
    if (!auth_is_authenticated()) {
        json_fail('Brak autoryzacji - zaloguj się.', 401);
    }
}

/** Czy bieżący użytkownik jest administratorem? */
function auth_is_admin(): bool
{
    $user = auth_user();
    return $user !== null && (string) $user['rola'] === 'admin';
}

/**
 * Guard dla endpointów admina - mapa uprawnień w jednym miejscu (3.1).
 * Najpierw sprawdza sesję (401), potem rolę (403).
 */
function auth_require_admin(): void
{
    auth_require();
    if (!auth_is_admin()) {
        json_fail('Brak uprawnień administratora.', 403);
    }
}

/** @return PDO połączenie z bazą (auto-tworzenie bazy i tabel) */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` ' .
        'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $pdo->exec('USE `' . DB_NAME . '`');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS zgloszenia (
            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            bike_name         VARCHAR(255) NOT NULL,
            date_in           DATE NOT NULL,
            date_planned      DATE NOT NULL,
            customer_phone    VARCHAR(32)  NOT NULL,
            fault_description TEXT NOT NULL,
            service_notes     TEXT DEFAULT NULL,
            services_done     TEXT DEFAULT NULL,
            status            ENUM("in_progress","completed","picked_up")
                              NOT NULL DEFAULT "in_progress",
            service_no        VARCHAR(32) DEFAULT NULL,
            confirmed         TINYINT(1) NOT NULL DEFAULT 1,
            created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at        TIMESTAMP DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_service_no (service_no),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS zdjecia (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            zgloszenie_id INT UNSIGNED NOT NULL,
            filename      VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            size_bytes    BIGINT UNSIGNED DEFAULT NULL,
            created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_zgloszenie (zgloszenie_id),
            CONSTRAINT fk_zdjecia_zgloszenie
                FOREIGN KEY (zgloszenie_id) REFERENCES zgloszenia(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ustawienia (
            klucz   VARCHAR(64)  NOT NULL,
            wartosc VARCHAR(255) NOT NULL DEFAULT "",
            PRIMARY KEY (klucz)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS uslugi (
            id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nazwa VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_nazwa (nazwa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // --- Konta użytkowników i sesje (3.0) ---
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
            login                VARCHAR(64)  NOT NULL,
            password_hash        VARCHAR(255) NOT NULL,
            rola                 ENUM("admin","pracownik") NOT NULL DEFAULT "pracownik",
            aktywny              TINYINT(1) NOT NULL DEFAULT 1,
            must_change_password TINYINT(1) NOT NULL DEFAULT 0,
            created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login_at        TIMESTAMP DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_login (login)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS sesje (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            token_hash CHAR(64) NOT NULL,
            user_id    INT UNSIGNED NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_token (token_hash),
            KEY idx_sesje_user (user_id),
            CONSTRAINT fk_sesje_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            klucz      VARCHAR(160) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_klucz_czas (klucz, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Seed konta admin z dotychczasowego hasła aplikacji - wykonywane tylko
    // raz, gdy baza nie ma jeszcze żadnych użytkowników
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO users (login, password_hash, rola) VALUES (?, ?, "admin")')
            ->execute(['admin', app_password_hash()]);
    }

    // Migracja na istniejących bazach: dodanie kolumny size_bytes + uzupełnienie z dysku
    $hasSize = false;
    foreach ($pdo->query('SHOW COLUMNS FROM zdjecia') as $col) {
        if (($col['Field'] ?? '') === 'size_bytes') {
            $hasSize = true;
        }
    }
    if (!$hasSize) {
        $pdo->exec('ALTER TABLE zdjecia ADD COLUMN size_bytes BIGINT UNSIGNED DEFAULT NULL AFTER original_name');
        $upd = $pdo->prepare('UPDATE zdjecia SET size_bytes = ? WHERE id = ?');
        foreach ($pdo->query('SELECT id, filename FROM zdjecia') as $row) {
            $bytes = @filesize(UPLOAD_DIR . '/' . $row['filename']);
            if ($bytes !== false) {
                $upd->execute([$bytes, $row['id']]);
            }
        }
    }

    // Migracje tabeli zgloszenia (wykrywanie kolumn przez SHOW COLUMNS):
    // confirmed  - potwierdzenie zgloszenia z mobile na PC
    // service_no - numer serwisowy (etykieta QR na rowerze)
    // service_notes - notatki serwisowe (tekst)
    // services_done - wykonane czynnosci jako JSON z nazwami uslug (checkboxy)
    // deleted_at - kosz (soft delete zamiast twardego usuwania)
    $zgCols = [];
    foreach ($pdo->query('SHOW COLUMNS FROM zgloszenia') as $col) {
        $zgCols[] = $col['Field'];
    }

    if (!in_array('confirmed', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN confirmed TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
    }

    if (!in_array('service_no', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN service_no VARCHAR(32) DEFAULT NULL AFTER status');
        // Uzupelnienie numerow dla istniejacych zgloszen: RO-ROK-NUMER_ID
        foreach ($pdo->query('SELECT id, created_at FROM zgloszenia') as $row) {
            $year = substr((string) $row['created_at'], 0, 4);
            $no = sprintf(
                'RO-%s-%04d',
                (ctype_digit($year) ? $year : date('Y')),
                (int) $row['id']
            );
            $pdo->prepare('UPDATE zgloszenia SET service_no = ? WHERE id = ?')
                ->execute([$no, $row['id']]);
        }
        $pdo->exec('ALTER TABLE zgloszenia ADD UNIQUE KEY uk_service_no (service_no)');
    }

    if (!in_array('service_notes', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN service_notes TEXT DEFAULT NULL AFTER fault_description');
    }

    if (!in_array('services_done', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN services_done TEXT DEFAULT NULL AFTER service_notes');
    }

    // Wersje 2.7/2.8 zapisywały zaznaczone przy przyjęciu usługi od razu jako
    // wykonane. Jednorazowa korekta (marker w ustawieniach, żeby nie kasować
    // nowych zaznaczeń): jeśli cały stan zgadza się z zakresem z opisu
    // (linie „- ”), czyścimy go do null — karta pokaże puste checkboxy.
    $mk = $pdo->prepare('SELECT wartosc FROM ustawienia WHERE klucz = ?');
    $mk->execute(['korekta_2_9_uslugi']);
    $mkRow = $mk->fetch();
    if (!$mkRow || (string) $mkRow['wartosc'] !== '1') {
        $fix = $pdo->prepare('UPDATE zgloszenia SET services_done = NULL WHERE id = ?');
        foreach ($pdo->query('SELECT id, fault_description, services_done FROM zgloszenia WHERE services_done IS NOT NULL') as $r) {
            $done = json_decode((string) $r['services_done'], true);
            if (!is_array($done) || $done === []) {
                continue;
            }
            $lines = [];
            foreach (explode("\n", (string) $r['fault_description']) as $ln) {
                $ln = trim($ln);
                if (str_starts_with($ln, '- ')) {
                    $lines[] = trim(substr($ln, 2));
                }
            }
            $poza = array_filter($done, fn ($n) => !in_array($n, $lines, true));
            if ($poza === []) {
                $fix->execute([$r['id']]);
            }
        }
        $pdo->prepare(
            'INSERT INTO ustawienia (klucz, wartosc) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)'
        )->execute(['korekta_2_9_uslugi', '1']);
    }

    if (!in_array('deleted_at', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN deleted_at TIMESTAMP DEFAULT NULL AFTER created_at');
    }

    // created_by - kto zalozyl zgloszenie (wlasciciel; uprawnienia pracownika)
    // confirmed_by - kto wydal rower (klikniecie "Wydaj rower")
    // Oba nullable: stare zgloszenia (sprzed wdrozenia) = NULL = "—" na karcie.
    if (!in_array('created_by', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER deleted_at');
    }
    if (!in_array('confirmed_by', $zgCols, true)) {
        $pdo->exec('ALTER TABLE zgloszenia ADD COLUMN confirmed_by INT UNSIGNED DEFAULT NULL AFTER created_by');
    }

    // Modul "kalendarz" moze byc wylaczony przez uzytkownika - wtedy termin
    // odbioru jest pusty, wiec kolumna musi dopuszczac NULL.
    foreach ($pdo->query("SHOW COLUMNS FROM zgloszenia LIKE 'date_planned'") as $col) {
        if (($col['Null'] ?? 'NO') === 'NO') {
            $pdo->exec('ALTER TABLE zgloszenia MODIFY COLUMN date_planned DATE DEFAULT NULL');
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    return $pdo;
}

/** Wysyła odpowiedź JSON i kończy skrypt. */
function json_out(mixed $data, int $httpCode = 200): never
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    // 3.8.3: bez tego LiteSpeed cache'uje GET api/ustawienia.php i panel
    // dostaje STALE wersje/dane instancji (jak wczesniej z serwis.php).
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_fail(string $error, int $httpCode = 400): never
{
    json_out(['success' => false, 'error' => $error], $httpCode);
}

/** Lista zdjęć (z bazą plików) przypisanych do zgłoszenia. */
function photos_for(int $zgloszenieId): array
{
    $stmt = db()->prepare(
        'SELECT id, filename, original_name, created_at
           FROM zdjecia
          WHERE zgloszenie_id = ?
          ORDER BY id ASC'
    );
    $stmt->execute([$zgloszenieId]);

    $photos = [];
    foreach ($stmt->fetchAll() as $row) {
        $photos[] = [
            'id'      => (int) $row['id'],
            'url'     => UPLOAD_URL . '/' . $row['filename'],
            'name'    => $row['original_name'],
            'created' => $row['created_at'],
        ];
    }
    return $photos;
}

/** Zapisuje wgrane pliki ($_FILES['photos']) i wpisy w tabeli zdjecia. */
function store_photos(int $zgloszenieId, array $files): array
{
    if (!isset($files['error']) || !is_array($files['error'])) {
        return [];
    }

    $allowedErrors = [
        UPLOAD_ERR_OK         => 'OK',
        UPLOAD_ERR_INI_SIZE   => 'Plik przekracza limit serwera (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'Plik przekracza limit formularza.',
        UPLOAD_ERR_PARTIAL    => 'Plik został wgrany tylko częściowo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Brak katalogu tymczasowego serwera.',
        UPLOAD_ERR_CANT_WRITE => 'Serwer nie mógł zapisać pliku na dysk.',
    ];

    $insert = db()->prepare(
        'INSERT INTO zdjecia (zgloszenie_id, filename, original_name, size_bytes)
         VALUES (?, ?, ?, ?)'
    );

    $saved = [];
    $uploadedCount = count(array_filter($files['error'] ?? [], static fn($e) => $e !== UPLOAD_ERR_NO_FILE));
    if ($uploadedCount > MAX_PHOTOS_PER_REQUEST) {
        json_fail('Za dużo zdjęć w jednym wgraniu (maks. ' . MAX_PHOTOS_PER_REQUEST . '). Dodaj mniejszą partię.');
    }

    // Łączny limit pojemności na zdjęcia wszystkich zgłoszeń razem —
    // sprawdzamy przed pętlą, żeby nie zapisać części partii po przekroczeniu
    $incoming = array_sum(array_map('intval', $files['size'] ?? []));
    if (photos_stats()['bytes'] + $incoming > MAX_PHOTOS_TOTAL_BYTES) {
        json_fail('Osiągnięto limit ' . (int) round(MAX_PHOTOS_TOTAL_BYTES / 1048576)
            . ' MB na zdjęcia. Usuń część starych zdjęć, żeby dodać nowe.');
    }

    foreach ($files['name'] as $i => $originalName) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $msg = $allowedErrors[$files['error'][$i]] ?? 'Nieznany błąd wgrywania pliku.';
            json_fail('Zdjęcie "' . $originalName . '": ' . $msg);
        }
        if ($files['size'][$i] > MAX_PHOTO_BYTES) {
            json_fail('Zdjęcie "' . $originalName . '" przekracza 10 MB.');
        }

        $tmp = $files['tmp_name'][$i];
        if (!is_uploaded_file($tmp)) {
            json_fail('Nieprawidłowy plik tymczasowy.');
        }

        // Określenie MIME (z fallbackiem, gdy rozszerzenie fileinfo nie jest dostępne)
        $mime = '';
        if (class_exists('finfo')) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        } else {
            $info = @getimagesize($tmp);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : '';
        }
        if (!isset(ALLOWED_PHOTO_MIME[$mime])) {
            json_fail('Zdjęcie "' . $originalName . '": niedopuszczalny format. Wymagane: JPG, PNG, WEBP lub GIF.');
        }
        // Walidacja, że plik faktycznie jest obrazem
        if (@getimagesize($tmp) === false) {
            json_fail('Zdjęcie "' . $originalName . '" jest uszkodzone lub nie jest obrazem.');
        }

        $ext = ALLOWED_PHOTO_MIME[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = UPLOAD_DIR . '/' . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            json_fail('Nie udało się zapisać zdjęcia na serwerze.');
        }
        @chmod($dest, 0644);

        $originalNameShort = function_exists('mb_substr')
            ? mb_substr($originalName, 0, 255)
            : substr($originalName, 0, 255);
        $insert->execute([$zgloszenieId, $filename, $originalNameShort, $files['size'][$i]]);

        $saved[] = [
            'id'      => (int) db()->lastInsertId(),
            'url'     => UPLOAD_URL . '/' . $filename,
            'name'    => $originalName,
            'created' => date('Y-m-d H:i:s'),
        ];
    }

    return $saved;
}

/** Usuwa plik zdjęcia z dysku i wiersz z bazy. */
function delete_photo(int $photoId): bool
{
    $stmt = db()->prepare('SELECT id, zgloszenie_id, filename FROM zdjecia WHERE id = ?');
    $stmt->execute([$photoId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    $path = UPLOAD_DIR . '/' . $row['filename'];
    if (is_file($path)) {
        @unlink($path);
    }

    db()->prepare('DELETE FROM zdjecia WHERE id = ?')->execute([$photoId]);
    return true;
}

/** Usuwa wszystkie zdjęcia zgłoszenia (pliki + wpisy). */
function delete_photos_of(int $zgloszenieId): void
{
    $stmt = db()->prepare('SELECT filename FROM zdjecia WHERE zgloszenie_id = ?');
    $stmt->execute([$zgloszenieId]);
    foreach ($stmt->fetchAll() as $row) {
        $path = UPLOAD_DIR . '/' . $row['filename'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    db()->prepare('DELETE FROM zdjecia WHERE zgloszenie_id = ?')->execute([$zgloszenieId]);
}

/** Buduje rekord zgłoszenia w formacie oczekiwanym przez frontend. */
/** 
 * Mapa loginów użytkowników (id -> login) do pokazywania na karcie
 * kto zalozyl i kto wydal rower. Statyczny cache - jedno zapytanie
 * na zapytanie strony zamiast N+1 przy liscie zgloszen.
 */
function users_login_map(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (db()->query('SELECT id, login FROM users') as $u) {
            $map[(int) $u['id']] = (string) $u['login'];
        }
    }
    return $map;
}

function map_zgloszenie(array $row): array
{
    $id = (int) $row['id'];
    $logins = users_login_map();
    $createdById = isset($row['created_by']) ? (int) $row['created_by'] : null;
    $confirmedById = isset($row['confirmed_by']) ? (int) $row['confirmed_by'] : null;
    // 3.5: "kto wydal" widoczne wylacznie gdy rower jest faktycznie wydany
    // (status picked_up). Cofniecie wydania kasuje confirmed_by w bazie
    // (action=status), a bramka nizej zdejmuje tez stare rekordy, ktore
    // zostawily stara wartosc po cofnieciu.
    if ((string) ($row['status'] ?? '') !== 'picked_up') {
        $confirmedById = null;
    }
    return [
        'id'               => $id,
        'bikeName'         => $row['bike_name'],
        'dateIn'           => $row['date_in'],
        'datePlanned'      => (string) ($row['date_planned'] ?? ''),
        'customerPhone'    => $row['customer_phone'],
        'faultDescription' => $row['fault_description'],
        'status'           => $row['status'],
        'confirmed'        => !empty($row['confirmed']),
        'serviceNo'        => $row['service_no'] ?? '',
        'serviceNotes'     => (string) ($row['service_notes'] ?? ''),
        // NULL = jeszcze nie zapisywano (frontend pokazuje pusto),
        // [] = zapisano i nic nie jest zaznaczone
        'servicesDone'     => array_key_exists('services_done', $row) && $row['services_done'] !== null
            ? (json_decode((string) $row['services_done'], true) ?: [])
            : null,
        'deleted'          => !empty($row['deleted_at']),
        'createdAt'        => $row['created_at'],
        // 3.2: kto zalozyl / kto wydal rower (stare zgloszenia = null = "—")
        'createdById'      => $createdById,
        'createdBy'        => $createdById !== null ? ($logins[$createdById] ?? null) : null,
        'confirmedById'    => $confirmedById,
        'confirmedBy'      => $confirmedById !== null ? ($logins[$confirmedById] ?? null) : null,
        'photos'           => photos_for($id),
    ];
}

/** Walidacja danych zgłoszenia z formularza. Zwraca oczyszczone dane. */
function validate_zgloszenie(array $in): array
{
    $bikeName    = trim((string) ($in['bike_name'] ?? ''));
    $dateIn      = trim((string) ($in['date_in'] ?? ''));
    $datePlanned = trim((string) ($in['date_planned'] ?? ''));
    $phone       = trim((string) ($in['customer_phone'] ?? ''));
    $fault       = trim((string) ($in['fault_description'] ?? ''));
    $status      = trim((string) ($in['status'] ?? 'in_progress'));

    if ($bikeName === '') {
        json_fail('Podaj nazwę roweru.');
    }
    if (mb_strlen($bikeName) > 255) {
        json_fail('Nazwa roweru jest za długa (max 255 znaków).');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateIn) || !checkdate(
        (int) substr($dateIn, 5, 2),
        (int) substr($dateIn, 8, 2),
        (int) substr($dateIn, 0, 4)
    )) {
        json_fail('Nieprawidłowa data przyjęcia.');
    }
    $plannedOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePlanned) === 1 && checkdate(
        (int) substr($datePlanned, 5, 2),
        (int) substr($datePlanned, 8, 2),
        (int) substr($datePlanned, 0, 4)
    );
    if (modul('kalendarz')) {
        if (!$plannedOk) {
            json_fail('Nieprawidłowa data planowanego odbioru.');
        }
    } elseif (!$plannedOk) {
        // Modul kalendarza wylaczony: pusty termin dozwolony (NULL w bazie)
        $datePlanned = '';
    }
    $phoneDigits = preg_replace('/\D/', '', $phone) ?? '';
    if (strlen($phoneDigits) < 9) {
        json_fail('Podaj poprawny numer telefonu (min. 9 cyfr).');
    }
    if ($fault === '') {
        json_fail('Opisz usterkę roweru.');
    }
    if (!in_array($status, STATUSES, true)) {
        json_fail('Nieprawidłowy status zgłoszenia.');
    }

    return [
        $bikeName,
        $dateIn,
        $datePlanned !== '' ? $datePlanned : null,   // null = brak terminu (modul kalendarza wylaczony)
        $phone,
        $fault,
        $status,
    ];
}

/**
 * Walidacja listy wykonanych czynnosci (checkboxy z katalogu uslug).
 * Wejscie: string JSON z tablica nazw. Zwraca oczyszczona tablice nazw.
 */
function validate_services_done(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        json_fail('Nieprawidłowa lista wykonanych czynności.');
    }
    $clean = [];
    foreach ($decoded as $name) {
        if (!is_string($name)) {
            continue;
        }
        $name = trim($name);
        if ($name === '') {
            continue;
        }
        if (mb_strlen($name) > 200) {
            json_fail('Nazwa wykonanej czynności jest za długa (max 200 znaków).');
        }
        $clean[] = $name;
        if (count($clean) > 50) {
            json_fail('Za dużo wykonanych czynności (max 50).');
        }
    }
    return array_values(array_unique($clean));
}

/* ---------------------------------------------------------------
 | AUTOMATYCZNE AKTUALIZACJE (v2)
 |  check_update() - sprawdza GitHub (max raz / 24h, cache w ustawienia)
 |  do_update()     - pobiera Release, backup, podmiana, migracje
 --------------------------------------------------------------- */

/**
 * Normalizuje wersje do postaci X.Y.Z (semver).
 * "3.8.1" -> "3.8.1", "3.8-instalator" -> "3.8.0", "3.8" -> "3.8.0".
 * Bez tego version_compare dostaje sufixy typu "-instalator" i porownuje zle.
 */
function wersja_normalizuj(string $v): string
{
    if (preg_match('/(\d+\.\d+\.\d+)/', $v, $m)) {
        return $m[1];
    }
    if (preg_match('/(\d+\.\d+)/', $v, $m)) {
        return $m[1] . '.0';
    }
    return '0.0.0';
}


/**
 * Przebudowuje config.php z NOWEGO wzorca (config.example.php, juz podmienionego
 * przez aktualizacje), przenoszac wartosci stale ze STAREGO config.php.
 *
 * Bez tego do_update() nigdy nie aktualizowal logiki z config.php (db(),
 * check_update(), do_update()...) - poprawki nie docieraly do instalacji,
 * a panel zostawal przy kodzie z dnia instalacji.
 *
 * Sekrety (hasla, dane bazy) sa przenoszone 1:1 ze starego pliku, wiec
 * config.php nigdy nie powstaje z paczki GitHuba.
 *
 * Zwraca ['ok'=>bool, 'tresc'=>string, 'blad'=>string].
 */
function config_przebuduj(string $staryCfg, string $wzorzec): array
{
    // Stale, ktore wypelnia instalator (wzorzec ma tu UZUPELNIJ)
    $klucze = [
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'APP_PASSWORD', 'AUTH_COOKIE',
        'SERVICE_ADDRESS', 'SERVICE_CITY', 'SERVICE_PHONE',
        'GOOGLE_MAPS_URL', 'SITE_URL',
    ];

    $tresc = $wzorzec;
    $brak = [];
    foreach ($klucze as $k) {
        // Wyciagnij wartosc ze starego config.php (cudzyslowy z escape'ami)
        if (preg_match("/const\s+" . preg_quote($k, '/') . "\s*=\s*('(?:[^'\\\\]|\\\\.)*')/", $staryCfg, $m) !== 1) {
            continue;   // starego pliku nie obslugiwala ta stala - zostaje wartosc wzorca
        }
        $nowy = 0;
        $tresc = preg_replace(
            "/const\s+" . preg_quote($k, '/') . "\s*=\s*'[^']*'/",
            'const ' . $k . ' = ' . $m[1],
            $tresc,
            1,
            $nowy
        );
        if ($tresc === null || $nowy !== 1) {
            return ['ok' => false, 'tresc' => '', 'blad' => 'Wzorzec nie zawiera stalej ' . $k . '.'];
        }
    }

    // Twarda walidacja: bez tego nie wolno nadpisac dzialajacego config.php.
    // Liczymy same stale - komentarz wzorca tez zawiera slowo "UZUPELNIJ".
    if (preg_match_all('/const\s+(\w+)\s*=\s*\'UZUPELNIJ\'/', $tresc, $zle) > 0) {
        return ['ok' => false, 'tresc' => '',
            'blad' => 'Nie udalo sie przeniesc stalych: ' . implode(', ', $zle[1]) . '.'];
    }
    if (!str_contains($tresc, 'function db(') || !str_contains($tresc, 'const APP_VERSION')) {
        return ['ok' => false, 'tresc' => '', 'blad' => 'Przebudowany config jest niepelny.'];
    }

    // Komentarz wzorca („to jest WZORZEC…”) nie pasuje do gotowego pliku
    $tresc = preg_replace(
        '/\/\* UWAGA: to jest WZORZEC.*?\*\//s',
        "/* Plik konfiguracji RoweryExpert (zaktualizowany przez automatyczna aktualizacje).\n"
        . "   Zawiera sekrety (hasla) - nie udostepniaj nikomu i nie wrzucaj do gita. */",
        $tresc,
        1
    );
    // Sprawdzamy sam naglowek - fraza zywije tez w komentarzu tej funkcji
    if ($tresc === null || preg_match('/^\/\* UWAGA: to jest WZORZEC/m', $tresc) === 1) {
        return ['ok' => false, 'tresc' => '', 'blad' => 'Nie udalo sie przerobic komentarza wzorca.'];
    }

    return ['ok' => true, 'tresc' => $tresc, 'blad' => ''];
}

/** Sprawdza dostepnosc aktualizacji. Zwraca tablica z danymi lub null. */
function check_update(bool $force = false): ?array
{
    $cacheKey = 'update_check_at';
    $resultKey = 'update_check_result';

    if (!$force) {
        $lastCheck = (int) setting_get($cacheKey, '0');
        if (time() - $lastCheck < 86400) {
            $cached = setting_get($resultKey, '');
            if ($cached !== '') {
                return json_decode($cached, true);
            }
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: RoweryExpert-Updater\r\nAccept: application/vnd.github.v3+json\r\n",
        ],
    ]);

    $response = @file_get_contents(
        'https://api.github.com/repos/mex13rs/roweryexpert/releases/latest',
        false,
        $context
    );

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['tag_name'])) {
        return null;
    }

    $latest = ltrim($data['tag_name'], 'v');
    $current = wersja_aplikacji();

    // Normalizacja do X.Y.Z (semver; installed_version moze miec sufix, np. "3.8-instalator")
    $latestNum = wersja_normalizuj($latest);
    $currentNum = wersja_normalizuj($current);

    $result = [
        'dostepna' => version_compare($latestNum, $currentNum, '>'),
        'nowa_wersja' => $latest,
        'obecna_wersja' => $current,
        'url' => $data['html_url'] ?? '',
        'opis' => $data['body'] ?? '',
    ];

    // Cache bez 'opis' — kolumna ustawienia.wartosc to VARCHAR(255)
    $cache = $result;
    unset($cache['opis']);
    setting_set($cacheKey, (string) time());
    setting_set($resultKey, json_encode($cache));

    return $result;
}

/** Pobiera i instaluje aktualizacje. Zwraca tablica wyniku. */
function do_update(): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: RoweryExpert-Updater\r\nAccept: application/vnd.github.v3+json\r\n",
        ],
    ]);

    $response = @file_get_contents(
        'https://api.github.com/repos/mex13rs/roweryexpert/releases/latest',
        false,
        $context
    );

    if ($response === false) {
        return ['success' => false, 'error' => 'Nie udało się połączyć z GitHub.'];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['tag_name'])) {
        return ['success' => false, 'error' => 'Nieprawidłowa odpowiedź z GitHub.'];
    }

    $tag = $data['tag_name'];
    // Tag moze byc vX.Y.Z (semver) lub vX.Y (starsze) — oba akceptowane
    if (!preg_match('/^v\d+\.\d+(\.\d+)?$/', $tag)) {
        return ['success' => false, 'error' => 'Nieprawidłowy format tagu: ' . $tag];
    }

    // Nie aktualizuj wstecz ani w petli: tag musi byc nowszy niz obecna wersja
    $nowaW = wersja_normalizuj(ltrim($tag, 'v'));
    $obecnaW = wersja_normalizuj(wersja_aplikacji());
    if (version_compare($nowaW, $obecnaW, '<=')) {
        return ['success' => false, 'error' => 'Masz juz wersje ' . wersja_aplikacji()
            . ' (na GitHubie ' . $nowaW . ') - nie ma czego aktualizowac.'];
    }

    $zipUrl = $data['zipball_url'] ?? '';
    if ($zipUrl === '') {
        return ['success' => false, 'error' => 'Brak URL do pobrania.'];
    }

    // Pobierz zip
    $zipData = @file_get_contents($zipUrl, false, stream_context_create([
        'http' => ['timeout' => 60, 'header' => "User-Agent: RoweryExpert-Updater\r\n"],
    ]));

    if ($zipData === false) {
        return ['success' => false, 'error' => 'Nie udało się pobrać paczki aktualizacji.'];
    }

    // Zapisz do pliku tymczasowego
    $tmpFile = sys_get_temp_dir() . '/roweryexpert-update-' . time() . '.zip';
    if (@file_put_contents($tmpFile, $zipData) === false) {
        return ['success' => false, 'error' => 'Nie udało się zapisać pliku tymczasowego.'];
    }

    // Weryfikacja zipa
    $zip = new ZipArchive();
    if ($zip->open($tmpFile) !== true) {
        @unlink($tmpFile);
        return ['success' => false, 'error' => 'Pobrany plik nie jest poprawnym archiwum ZIP.'];
    }

    $hasSerwis = false;
    $hasInstall = false;
    $wzorzec = null;   // nowy wzorzec konfiguracji - odczytany zanim zamkniemy zip
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_ends_with($name, '/serwis.php')) $hasSerwis = true;
        if (str_ends_with($name, '/install.php')) $hasInstall = true;
        // GitHub zipball ma prefix katalogu (repo-<sha>/) - szukamy po sufiksie
        if (str_ends_with($name, 'config.example.php') && !str_ends_with($name, '/')) {
            $wzorzec = $zip->getFromIndex($i);
        }
    }
    $zip->close();

    if (!$hasSerwis || !$hasInstall) {
        @unlink($tmpFile);
        return ['success' => false, 'error' => 'Paczka nie zawiera wymaganych plików.'];
    }

    // Przebuduj config.php z NOWEGO wzorca + stare sekrety - ZANIM cokolwiek
    // podmienimy. Bez tego aktualizacja aktualizowalaby tylko frontend/api,
    // a logika z config.php (db(), auth, wlasnie do_update) zostawalaby
    // przy starej wersji i poprawki nigdy nie trafialyby do instalacji.
    // Wzorzec czytamy wprost z paczki, bo na dysku leci jeszcze stary.
    $cfgPath = __DIR__ . '/config.php';
    $staryCfg = @file_get_contents($cfgPath);
    if (is_string($wzorzec) && $wzorzec !== '' && $staryCfg !== false) {
        $nowyCfg = config_przebuduj($staryCfg, $wzorzec);
        if (!$nowyCfg['ok']) {
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'Aktualizacja przerwana przed podmiana plikow: '
                . $nowyCfg['blad'] . ' Nic sie nie zmienilo - config.php i pliki zostaja po staremu.'];
        }
    } else {
        $nowyCfg = null;   // brak config.php lub wzorca - podmieniamy same pliki
    }

    // Kopia zapasowa
    $backupDir = __DIR__ . '/uploads/backup';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }
    // 3.8.4: backupy configa (sekrety!) leza w katalogu widocznym z sieci.
    // Bez tego .htaccess kazdy mogl pobrac kopie z haslami Bazy i haslem
    // aplikacji - wykryte i zablokowane recznie 2026-09-29.
    $ht = $backupDir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Require all denied\n");
    }
    $backupFile = $backupDir . '/backup-' . date('Y-m-d_H-i-s') . '.zip';
    $backup = new ZipArchive();
    if ($backup->open($backupFile, ZipArchive::CREATE) === true) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $path = $file->getPathname();
            $relative = substr($path, strlen(__DIR__) + 1);
            // Nie backupuj config.php, uploads/, .git, backupów
            if (str_starts_with($relative, 'config.php') ||
                str_starts_with($relative, 'uploads/') ||
                str_starts_with($relative, '.git/') ||
                str_starts_with($relative, 'uploads/backup/')) {
                continue;
            }
            $backup->addFile($path, $relative);
        }
        $backup->close();
    }

    // Rozpakuj nowe pliki (z pominięciem config.php i uploads/)
    $zip = new ZipArchive();
    if ($zip->open($tmpFile) !== true) {
        @unlink($tmpFile);
        return ['success' => false, 'error' => 'Nie udało się otworzyć paczki.'];
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        // GitHub zipball ma prefix katalogu: repo-branch/
        if (preg_match('#^[^/]+/(.+)$#', $name, $m)) {
            $relative = $m[1];
        } else {
            continue;
        }
        // Wpisy katalogow (koncza sie na /) - file_put_contents na katalogu
        // zwraca false, wiec musza byc pominiete przed kontrola zapisu
        if (str_ends_with($relative, '/')) {
            continue;
        }
        // Nie nadpisuj config.php, uploads/, .user.ini
        if ($relative === 'config.php' ||
            str_starts_with($relative, 'uploads/') ||
            $relative === '.user.ini') {
            continue;
        }
        $dest = __DIR__ . '/' . $relative;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $content = $zip->getFromIndex($i);
        if ($content === false) {
            continue;
        }
        // Cicha porazka zapisu = panel polowowo zaktualizowany, wiec zglos blad
        // zamiast udawac sukces (backup jest juz zrobiony - mozna przywrocic).
        if (@file_put_contents($dest, $content) === false) {
            $zip->close();
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'Nie udalo sie zapisac pliku '
                . $relative . ' (brak prawa zapisu?). Aktualizacja przerwana -'
                . ' pliki przed zmiana sa w kopii zapasowej w uploads/backup/.'];
        }
        // OpCache trzyma stary kod - wymusz odswiezenie tego pliku
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($dest, true);
        }
    }
    $zip->close();
    @unlink($tmpFile);

    // config.php zapisujemy NA KONCU - dopiero teraz mamy pewnosc, ze pliki
    // zostaly podmienione. Najpierw kopia starego, zeby dalo sie przywrocic.
    if ($nowyCfg !== null) {
        $bakCfg = $backupDir . '/config-' . date('Y-m-d_H-i-s') . '.php.bak';
        @file_put_contents($bakCfg, $staryCfg);
        if (@file_put_contents($cfgPath, $nowyCfg['tresc']) === false) {
            return ['success' => false, 'error' => 'Pliki zaktualizowane, ale nie udalo sie zapisac nowego config.php'
                . ' (brak prawa zapisu?). Stary config zostal nietkniety, kopia: uploads/backup/'];
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($cfgPath, true);
        }
    }

    // Pelny reset OpCache - bez tego hosting (revalidate_freq=60) przez ~minute
    // serwuje STARY kod i panel wydaje sie niezaktualizowany
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    // Migracje bazy
    try {
        db();
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Aktualizacja plików OK, ale błąd migracji: ' . $e->getMessage()];
    }

    // Zaktualizuj installed_version
    setting_set('installed_version', ltrim($tag, 'v'));

    // Wyczysc cache aktualizacji (wynik + znacznik) - inaczej baner przez do 24h
    // pokazuje stara wersje jako "dostepna"
    setting_set('update_check_at', '0');
    setting_set('update_check_result', '');

    return ['success' => true, 'data' => ['nowa_wersja' => ltrim($tag, 'v')]];
}
