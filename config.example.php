<?php
declare(strict_types=1);

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
 --------------------------------------------------------------- */
const APP_VERSION = '2.11';

/* ---------------------------------------------------------------
 | Konfiguracja bazy danych (MySQL) i pomocnicze funkcje wspólne
 --------------------------------------------------------------- */

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'host91573_serwis';
const DB_USER = 'UZUPELNIJ';
const DB_PASS = 'UZUPELNIJ';

const UPLOAD_DIR = __DIR__ . '/uploads/zdjecia';
const UPLOAD_URL = 'uploads/zdjecia';
const MAX_PHOTO_BYTES = 10 * 1024 * 1024; // 10 MB na zdjęcie
const MAX_PHOTOS_PER_REQUEST = 20;        // maks. zdjęć w jednym wgraniu (limit serwera max_file_uploads)
const ALLOWED_PHOTO_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
const STATUSES = ['in_progress', 'completed', 'picked_up'];

/* ---------------------------------------------------------------
 | Uwierzytelnianie (hasło podawane raz dziennie, cookie na dobę)
 | APP_PASSWORD  -> początkowe hasło (potem zmienialne w Ustawieniach)
 | APP_SECRET    -> tajny klucz do podpisywania tokenu na dzień
 --------------------------------------------------------------- */
const APP_PASSWORD = 'UZUPELNIJ';
const APP_SECRET = 'UZUPELNIJ';

const AUTH_COOKIE = 'UZUPELNIJ';

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

/** Zmienia hasło aplikacji. Zwraca null przy sukcesie albo komunikat błędu. */
function change_app_password(string $current, string $next): ?string
{
    $currentHash = app_password_hash();
    if (!password_verify($current, $currentHash)) {
        return 'Aktualne hasło jest nieprawidłowe.';
    }
    if (strlen($next) < 6) {
        return 'Nowe hasło musi mieć min. 6 znaków.';
    }
    $newHash = password_hash($next, PASSWORD_DEFAULT);
    db()->prepare('UPDATE ustawienia SET wartosc = ? WHERE klucz = ?')
        ->execute([$newHash, 'app_password_hash']);
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

/** Token autoryzacji ważny tylko dzisiaj. */
function auth_token_today(): string
{
    return hash_hmac('sha256', date('Y-m-d'), APP_SECRET);
}

/** Czy użytkownik jest zalogowany "na dziś". */
function auth_is_authenticated(): bool
{
    return isset($_COOKIE[AUTH_COOKIE])
        && hash_equals(auth_token_today(), (string) $_COOKIE[AUTH_COOKIE]);
}

/** Logowanie - ustawia cookie ważne do końca dzisiejszego dnia. */
function auth_login(string $password): bool
{
    if (!password_verify($password, app_password_hash())) {
        return false;
    }
    setcookie(AUTH_COOKIE, auth_token_today(), [
        'expires'  => strtotime('tomorrow'),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return true;
}

/** Wylogowanie - unieważnia cookie. */
function auth_logout(): void
{
    setcookie(AUTH_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Dla endpointów API - przerywa żądanie 401, gdy brak autoryzacji. */
function auth_require(): void
{
    if (!auth_is_authenticated()) {
        json_fail('Brak autoryzacji - zaloguj się.', 401);
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
function map_zgloszenie(array $row): array
{
    $id = (int) $row['id'];
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
