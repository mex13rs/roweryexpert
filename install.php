<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | Instalator RoweryExpert — graficzny krok po kroku.
 | Uruchamianie: gdy brakuje config.php, serwis.php przekierowuje tutaj.
 | Po instalacji: USUŃ ten plik z serwera (FTP) — to konieczne.
 | Bez config.php nie działa jeszcze db(), więc cały instalator
 | działa na czystym PHP + PDO i sam generuje config.php.
  --------------------------------------------------------------- */

session_start();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const INST_MAX_TRIES = 10; // ile prób testu połączenia z bazą na jedną sesję

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Aktualna wersja aplikacji odczytana ze wzorca (bez ładowania config). */
function inst_app_version(): string
{
    static $ver = null;
    if ($ver === null) {
        $src = @file_get_contents(__DIR__ . '/config.example.php');
        $ver = ($src !== false && preg_match("/const APP_VERSION = '([^']+)'/", $src, $m) === 1)
            ? $m[1] : '?';
    }
    return $ver;
}

/** Lista kontroli wymagań serwera: [nazwa, OK?, podpowiedź gdy brak]. */
function inst_requirements(): array
{
    return [
        ['PHP w wersji 8.0 lub nowszej', PHP_VERSION_ID >= 80000,
            'Masz ' . PHP_VERSION . ' — poproś hosting o aktualizację PHP w panelu.'],
        ['Rozszerzenie pdo_mysql', extension_loaded('pdo_mysql'),
            'Brak — poproś hosting o włączenie rozszerzenia pdo_mysql.'],
        ['Rozszerzenie mbstring (polskie znaki)', extension_loaded('mbstring'),
            'Brak — poproś hosting o włączenie rozszerzenia mbstring.'],
        ['Funkcja password_hash (bezpieczne hasła)', function_exists('password_hash'), ''],
        ['Katalog zapisywalny (powstanie config.php)', is_writable(__DIR__),
            'Brak prawa zapisu — sprawdź uprawnienia katalogu w FTP.'],
        ['Wzorzec konfiguracji config.example.php', is_readable(__DIR__ . '/config.example.php'),
            'Brak pliku — pliki projektu nie zostały kompletne wgrane na serwer.'],
    ];
}

/** Walidacja i test połączenia z bazą. Zwraca komunikat błędu albo null. */
function inst_test_db(array $d): ?string
{
    if (preg_match('/^[A-Za-z0-9._-]{1,100}$/', $d['db_host']) !== 1) {
        return 'Nieprawidłowy host bazy (dozwolone: litery, cyfry, kropka, myślnik).';
    }
    if (preg_match('/^\d{1,5}$/', $d['db_port']) !== 1 || (int) $d['db_port'] < 1 || (int) $d['db_port'] > 65535) {
        return 'Nieprawidłowy port bazy (np. 3306).';
    }
    if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $d['db_name']) !== 1) {
        return 'Nieprawidłowa nazwa bazy (litery, cyfry i podkreślnik).';
    }
    if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $d['db_user']) !== 1) {
        return 'Nieprawidłowa nazwa użytkownika bazy (litery, cyfry i podkreślnik).';
    }
    if ($d['db_pass'] === '') {
        return 'Podaj hasło do bazy danych.';
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $d['db_host'], $d['db_port']),
            $d['db_user'],
            $d['db_pass'],
            [
                PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT         => 8,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->query('SELECT 1');
    } catch (PDOException $e) {
        return 'Nie udało się połączyć z bazą: ' . $e->getMessage()
            . ' Sprawdź dane w panelu hostingu (nazwa bazy, użytkownik, hasło).';
    }
    return null;
}

/** Buduje treść config.php przez podmianę stałych we wzorcu. Zwraca tekst albo rzuca wyjątek. */
function inst_build_config(array $map): string
{
    $src = @file_get_contents(__DIR__ . '/config.example.php');
    if ($src === false) {
        throw new RuntimeException('Nie można odczytać config.example.php.');
    }
    foreach ($map as $k => $v) {
        $found = 0;
        $src = preg_replace_callback(
            '/const\s+' . preg_quote($k, '/') . '\s*=\s*\'[^\']*\'/',
            static fn(): string => 'const ' . $k . ' = ' . var_export($v, true),
            $src,
            1,
            $found
        );
        if ($found !== 1 || $src === null) {
            throw new RuntimeException('Wzorzec konfiguracji nie zawiera stałej ' . $k . '.');
        }
    }
    // Komentarz wzorca („to jest WZORZEC…”) nie pasuje do gotowego pliku na serwerze.
    $src = preg_replace(
        '/\/\* UWAGA: to jest WZORZEC.*?\*\//s',
        "/* Plik konfiguracji wygenerowany przez install.php.\n"
        . "   Zawiera sekrety (hasla) - nie udostepniaj nikomu i nie wrzucaj do gita. */",
        $src,
        1
    );
    if ($src === null) {
        throw new RuntimeException('Nie udało się przerobić komentarza wzorca konfiguracji.');
    }
    return $src;
}

// =================================================================
//  Blokada: instalacja już wykonana
// =================================================================
if (is_file(__DIR__ . '/config.php') && empty($_SESSION['inst_done'])) {
    inst_render(null, '', '
        <div class="box ok">
            <strong>Panel jest już skonfigurowany.</strong><br>
            Instalator nie jest już potrzebny.
        </div>
        <div class="box">
            <strong>Zrób porządek:</strong> usuń plik <code>install.php</code> z serwera (FTP),
            żeby nikt nie mógł uruchomić instalatora ponownie.<br>
            <a class="btn" href="serwis.php">Przejdź do panelu</a>
        </div>');
    exit;
}

// =================================================================
//  Obsługa POST (kolejne kroki)
// =================================================================
$err = '';
$vals = null;
$postStep = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = ($_SESSION['inst_csrf'] ?? '') !== ''
        && hash_equals($_SESSION['inst_csrf'], (string) ($_POST['csrf'] ?? ''));
    if (!$csrfOk) {
        $err = 'Sesja wygasła — odśwież stronę i spróbuj ponownie.';
        $postStep = max(1, (int) ($_SESSION['inst']['max'] ?? 1));
    } else {
        $inst = &$_SESSION['inst'];
        $inst = is_array($inst) ? $inst : ['max' => 1, 'tries' => 0];
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'step1') {
            $bad = array_filter(inst_requirements(), fn(array $c): bool => $c[1] === false);
            if ($bad === []) {
                $inst['max'] = max($inst['max'] ?? 1, 2);
                header('Location: install.php?krok=2');
                exit;
            }
            $postStep = 1;
            $err = 'Serwer nie spełnia wymagań: ' . implode(' ', array_map(fn(array $c): string => $c[2] ?: $c[0], $bad));

        } elseif ($action === 'step2') {
            $dbPass = (string) ($_POST['db_pass'] ?? '');
            if ($dbPass === '' && ($inst['db']['db_pass'] ?? '') !== '') {
                $dbPass = $inst['db']['db_pass']; // powrót do kroku — hasło zostaje z sesji
            }
            $vals = [
                'db_host' => trim((string) ($_POST['db_host'] ?? 'localhost')),
                'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
                'db_name' => trim((string) ($_POST['db_name'] ?? '')),
                'db_user' => trim((string) ($_POST['db_user'] ?? '')),
                'db_pass' => $dbPass,
            ];
            $postStep = 2;
            if (($inst['tries_at'] ?? 0) < time() - 900) {
                $inst['tries'] = 0; // okno 15 minut — po nim limit pró się resetuje
            }
            if (($inst['tries'] ?? 0) >= INST_MAX_TRIES) {
                $err = 'Zbyt wiele prób testu połączenia — odczekaj 15 minut i spróbuj ponownie.';
            } else {
                $inst['tries'] = ($inst['tries'] ?? 0) + 1;
                $inst['tries_at'] = time();
                $err = inst_test_db($vals);
                if ($err === null) {
                    $inst['db'] = $vals;
                    $inst['max'] = max($inst['max'] ?? 1, 3);
                    header('Location: install.php?krok=3');
                    exit;
                }
            }

        } elseif ($action === 'step3') {
            if (!isset($inst['db'])) {
                header('Location: install.php?krok=2');
                exit;
            }
            $postStep = 3;
            $vals = [
                'addr' => trim((string) ($_POST['addr'] ?? '')),
                'city' => trim((string) ($_POST['city'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'maps' => trim((string) ($_POST['maps'] ?? '')),
                'site' => trim((string) ($_POST['site'] ?? '')),
            ];
            if ($vals['addr'] === '' || $vals['city'] === '') {
                $err = 'Podaj adres serwisu (ulica oraz kod i miasto) — trafia na potwierdzenie zlecenia.';
            } elseif (preg_match('/^[0-9 +\-().]{6,32}$/', $vals['phone']) !== 1) {
                $err = 'Podaj prawidłowy telefon serwisu (6–32 znaki: cyfry, spacje, myślniki).';
            } elseif (filter_var($vals['maps'], FILTER_VALIDATE_URL) === false
                || !in_array(strtolower(substr($vals['maps'], 0, 4)), ['http'], true)) {
                $err = 'Podaj pełny link do wizytówki Google (zaczyna się od https://…) — z niego powstanie QR „Oceń nas” na wydruku.';
            } elseif ($vals['site'] !== '' && filter_var($vals['site'], FILTER_VALIDATE_URL) === false) {
                $err = 'Adres URL panelu musi być pełnym adresem (https://…) albo pusty.';
            } else {
                $vals['site'] = $vals['site'] !== '' ? rtrim($vals['site'], '/') : '';
                $inst['dane'] = $vals;
                $inst['max'] = max($inst['max'] ?? 1, 4);
                header('Location: install.php?krok=4');
                exit;
            }

        } elseif ($action === 'step4') {
            if (!isset($inst['dane'])) {
                header('Location: install.php?krok=3');
                exit;
            }
            $postStep = 4;
            $p1 = (string) ($_POST['pass1'] ?? '');
            $p2 = (string) ($_POST['pass2'] ?? '');
            if (strlen($p1) < 6) {
                $err = 'Hasło administratora musi mieć co najmniej 6 znaków (zalecane 8+).';
            } elseif ($p1 !== $p2) {
                $err = 'Hasła nie są identyczne.';
            } else {
                $inst['pass'] = $p1;
                $inst['max'] = max($inst['max'] ?? 1, 5);
                header('Location: install.php?krok=5');
                exit;
            }

        } elseif ($action === 'step5') {
            if (!isset($inst['db'])) {
                header('Location: install.php?krok=2');
                exit;
            }
            if (!isset($inst['dane'])) {
                header('Location: install.php?krok=3');
                exit;
            }
            if (!isset($inst['pass'])) {
                header('Location: install.php?krok=4');
                exit;
            }
            $postStep = 5;
            try {
                inst_write_config($inst['db'], $inst['dane'], (string) $inst['pass']);
            } catch (Throwable $e) {
                $err = $e->getMessage();
            }
            if ($err === '') {
                try {
                    require __DIR__ . '/config.php'; // na poziomie pliku: stałe + funkcje wspólne
                    db();                            // tworzy bazę, tabele, migracje i konto admin
                    setting_set('installed_version', APP_VERSION);
                    setting_set('installed_at', gmdate('Y-m-d H:i:s'));
                    unset($_SESSION['inst']);
                    $_SESSION['inst_done'] = true;
                    inst_render_step6();
                    exit;
                } catch (Throwable $e) {
                    @unlink(__DIR__ . '/config.php'); // cofamy, żeby instalator dało się powtórzyć
                    $err = 'Konfiguracja została zapisana, ale setup bazy danych nie powiódł się: '
                        . $e->getMessage()
                        . ' Najczęstsza przyczyna: konto bazy nie ma uprawnień do tworzenia bazy —'
                        . ' utwórz bazę w panelu hostingu i podaj jej dane jeszcze raz.';
                }
            }
        }
    }
}

// =================================================================
//  Wyświetlanie kroku
// =================================================================
if (!empty($_SESSION['inst_done'])) {
    inst_render_step6();
    exit;
}

$inst = $_SESSION['inst'] ?? ['max' => 1, 'tries' => 0];
$krok = $postStep > 0
    ? $postStep
    : (int) ($_GET['krok'] ?? (int) ($inst['max'] ?? 1)); // domyślnie: kontynuacja tam, gdzie skończyło się
$krok = max(1, min($krok, (int) ($inst['max'] ?? 1)));
if ($vals === null) {
    $vals = array_merge(
        ['db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
         'addr' => '', 'city' => '', 'phone' => '', 'maps' => '', 'site' => ''],
        $inst['db'] ?? [],
        $inst['dane'] ?? []
    );
}
$_SESSION['inst_csrf'] = $_SESSION['inst_csrf'] ?? bin2hex(random_bytes(16));

inst_render($krok, $err, null, $vals);

// =================================================================
//  Widoki
// =================================================================

/** Szkielet strony + pasek kroków + treść. */
function inst_render(?int $step, string $err = '', ?string $body = null, array $vals = []): void
{
    $labels = ['Wymagania', 'Baza danych', 'Dane serwisu', 'Konto admin', 'Instalacja', 'Gotowe'];

    $stepper = '';
    if ($step !== null && $step > 0) {
        $stepper = '<ol class="steps">';
        foreach ($labels as $i => $lbl) {
            $n = $i + 1;
            $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
            $stepper .= '<li class="' . $cls . '"><span>' . $n . '</span>' . h($lbl) . '</li>';
        }
        $stepper .= '</ol>';
    }

    if ($body === null) {
        $body = match ($step) {
            1 => inst_step1(),
            2 => inst_step2($vals, $err),
            3 => inst_step3($vals, $err),
            4 => inst_step4($err),
            5 => inst_step5($vals),
            default => inst_step6_body(),
        };
        if ($err !== '' && $step !== 2 && $step !== 3 && $step !== 4) {
            $body = '<div class="box err">' . h($err) . '</div>' . $body;
        }
    }

    echo '<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Instalator — RoweryExpert</title>
<link rel="icon" type="image/png" href="favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: flex; align-items: flex-start; justify-content: center;
         background: #101216; color: #e7e9ee; font: 400 16px/1.5 Barlow, system-ui, sans-serif; padding: 32px 16px; }
  .card { width: 100%; max-width: 640px; background: #1b1e25; border: 1px solid #2a2f3a;
          border-radius: 14px; padding: 28px 28px 22px; }
  h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: .3px; }
  h1 b { color: #ffdd00; }
  .sub { margin: 2px 0 18px; color: #9aa2b1; font-size: 14px; }
  .steps { list-style: none; display: flex; flex-wrap: wrap; gap: 6px 14px; margin: 0 0 22px; padding: 0;
           font-size: 12.5px; color: #6d7686; }
  .steps li { display: flex; align-items: center; gap: 6px; }
  .steps li span { display: inline-flex; align-items: center; justify-content: center;
                   width: 20px; height: 20px; border-radius: 50%; background: #262b34; color: #9aa2b1;
                   font-size: 11px; font-weight: 600; }
  .steps li.active { color: #ffdd00; }
  .steps li.active span { background: #ffdd00; color: #111; }
  .steps li.done { color: #7d8698; }
  .steps li.done span { background: #2ea043; color: #fff; }
  h2 { font-size: 19px; margin: 0 0 12px; }
  label { display: block; margin: 12px 0 4px; font-size: 14px; color: #b9c0cc; }
  label small { color: #6d7686; }
  input[type=text], input[type=password], input[type=url], input[type=number] {
      width: 100%; padding: 9px 11px; background: #12151a; color: #e7e9ee;
      border: 1px solid #333a46; border-radius: 8px; font: inherit; }
  input:focus { outline: none; border-color: #ffdd00; }
  .row { display: flex; gap: 12px; }
  .row > div { flex: 1; }
  .btn { display: inline-block; margin-top: 18px; padding: 10px 22px; background: #ffdd00; color: #111;
         border: none; border-radius: 8px; font: 600 15px Barlow, sans-serif; cursor: pointer; text-decoration: none; }
  .btn:hover { filter: brightness(1.06); }
  .btn.ghost { background: transparent; color: #9aa2b1; border: 1px solid #333a46; }
  .box { margin: 12px 0; padding: 12px 14px; border-radius: 8px; font-size: 14.5px; border: 1px solid #333a46;
         background: #161a21; }
  .box.err { border-color: #e2001a; background: #241417; color: #ff9ba3; }
  .box.ok { border-color: #2ea043; background: #12240f; color: #9be7a8; }
  .checks { list-style: none; margin: 0; padding: 0; }
  .checks li { padding: 7px 0; border-bottom: 1px dashed #2a2f34; font-size: 15px; }
  .checks li:last-child { border-bottom: none; }
  .checks .yes { color: #9be7a8; font-weight: 600; }
  .checks .no { color: #ff9ba3; font-weight: 600; }
  details { margin-top: 14px; background: #161a21; border: 1px solid #2a2f3a; border-radius: 8px; padding: 10px 14px; }
  summary { cursor: pointer; color: #ffdd00; font-weight: 600; font-size: 14.5px; }
  details .hint { font-size: 14px; color: #9aa2b1; margin-top: 8px; }
  details .hint b { color: #cbd2de; }
  code { background: #12151a; border: 1px solid #333a46; border-radius: 5px; padding: 1px 6px;
         font-size: 13.5px; color: #ffdd00; }
  .sum { width: 100%; border-collapse: collapse; font-size: 14.5px; margin-top: 6px; }
  .sum td { padding: 7px 8px; border-bottom: 1px dashed #2a2f34; }
  .sum td:first-child { color: #9aa2b1; width: 45%; }
  footer { margin-top: 20px; padding-top: 14px; border-top: 1px solid #2a2f3a;
           font-size: 12.5px; color: #6d7686; display: flex; justify-content: space-between; gap: 10px; }
</style>
</head>
<body>
<main class="card">
  <h1><b>RoweryExpert</b></h1>
  <p class="sub">Instalator panelu serwisowego — konfiguracja krok po kroku</p>
  ' . $stepper . '
  ' . $body . '
  <footer>
    <span>Instalator v' . h(inst_app_version()) . '</span>
    <span>Po instalacji usuń plik install.php</span>
  </footer>
</main>
</body>
</html>';
}

function inst_step1(): string
{
    $items = '';
    $allOk = true;
    foreach (inst_requirements() as [$name, $ok, $hint]) {
        $allOk = $allOk && $ok;
        $items .= '<li><span class="' . ($ok ? 'yes' : 'no') . '">' . ($ok ? '✓' : '✗') . '</span> '
            . h($name) . ($ok || $hint === '' ? '' : '<br><small style="color:#ff9ba3">' . h($hint) . '</small>') . '</li>';
    }
    $next = $allOk
        ? '<form method="post"><input type="hidden" name="csrf" value="' . h($_SESSION['inst_csrf'] ?? '') . '">
           <input type="hidden" name="action" value="step1">
           <button class="btn" type="submit">Dalej — konfiguracja bazy</button></form>'
        : '<div class="box err">Usuń wskazane problemy i odśwież stronę.</div>';

    return '<h2>Krok 1/6 — Wymagania serwera</h2>
        <ul class="checks">' . $items . '</ul>' . $next;
}

function inst_step2(array $v, string $err): string
{
    return '<h2>Krok 2/6 — Baza danych MySQL</h2>
        <div class="box">Utwórz bazę w panelu swojego hostingu <strong>zanim</strong> uzupełnisz formularz,
        a potem wpisz jej dane. Panel hostingu zawsze podaje: host, nazwę bazy, użytkownika i hasło.</div>
        ' . ($err !== '' ? '<div class="box err">' . h($err) . '</div>' : '') . '
        <form method="post">
            <input type="hidden" name="csrf" value="' . h($_SESSION['inst_csrf'] ?? '') . '">
            <input type="hidden" name="action" value="step2">
            <div class="row">
                <div><label>Host bazy</label>
                    <input type="text" name="db_host" value="' . h($v['db_host']) . '" required></div>
                <div style="max-width:120px"><label>Port</label>
                    <input type="number" name="db_port" value="' . h($v['db_port']) . '" min="1" max="65535" required></div>
            </div>
            <label>Nazwa bazy</label>
            <input type="text" name="db_name" value="' . h($v['db_name']) . '" placeholder="np. twoj_prefix_serwis" required>
            <label>Użytkownik bazy</label>
            <input type="text" name="db_user" value="' . h($v['db_user']) . '" required>
            <label>Hasło bazy</label>
            <input type="password" name="db_pass" value="" placeholder="' . ($v['db_pass'] !== '' ? '•••••• (wprowadzone — zostaw puste, żeby nie zmieniać)' : '') . '">
            <button class="btn" type="submit">Testuj połączenie i dalej</button>
            <a class="btn ghost" href="install.php?krok=1">Wstecz</a>
        </form>
        <details>
            <summary>Jak założyć bazę danych?</summary>
            <div class="hint">
                <b>IQHS (iqhs.pl):</b> panel klienta → zakładka „Bazy danych” → „Dodaj bazę MySQL”.<br>
                <b>home.pl:</b> panel home.pl → „Bazy danych” → „Dodaj nową bazę MySQL”.<br>
                <b>OVH:</b> panel OVH → „Bazy danych SQL” → „Utwórz bazę”.<br>
                <b>nazwa.pl:</b> panel klienta → „Bazy MySQL” → „Dodaj”.<br><br>
                Zapisz wszystkie dane z potwierdzenia (host to zwykle <code>localhost</code>).
                Szczegóły mogą się różnić — szukaj w panelu fraz „baza danych”, „MySQL” albo „SQL”.
                Uwaga: sam panel instalatora utworzy tabele — wystarczy pusta baza z kontem użytkownika.
            </div>
        </details>';
}

function inst_step3(array $v, string $err): string
{
    return '<h2>Krok 3/6 — Dane serwisu</h2>
        <div class="box">Te dane trafią na <strong>potwierdzenie zlecenia (wydruk A4)</strong> oraz do
        kodu QR „Oceń nas w Google”. Nazwa RoweryExpert jest stała — zmieniasz tylko lokalne dane.</div>
        ' . ($err !== '' ? '<div class="box err">' . h($err) . '</div>' : '') . '
        <form method="post">
            <input type="hidden" name="csrf" value="' . h($_SESSION['inst_csrf'] ?? '') . '">
            <input type="hidden" name="action" value="step3">
            <label>Ulica <small>(wymagane)</small></label>
            <input type="text" name="addr" value="' . h($v['addr']) . '" placeholder="np. Ostrobramska 81" required>
            <label>Kod pocztowy i miasto <small>(wymagane)</small></label>
            <input type="text" name="city" value="' . h($v['city']) . '" placeholder="np. 04-175 Warszawa" required>
            <label>Telefon serwisu <small>(wymagane — na wydruku)</small></label>
            <input type="text" name="phone" value="' . h($v['phone']) . '" placeholder="np. 532-561-152" required>
            <label>Link do wizytówki Google <small>(wymagane — źródło QR „Oceń nas”)</small></label>
            <input type="url" name="maps" value="' . h($v['maps']) . '" placeholder="https://maps.app.goo.gl/…" required>
            <label>Adres URL panelu <small>(opcjonalny)</small></label>
            <input type="url" name="site" value="' . h($v['site']) . '" placeholder="https://serwis.twojadomena.pl">
            <button class="btn" type="submit">Dalej</button>
            <a class="btn ghost" href="install.php?krok=2">Wstecz</a>
        </form>';
}

function inst_step4(string $err): string
{
    return '<h2>Krok 4/6 — Konto administratora</h2>
        <div class="box">Tworzone jest konto <strong>admin</strong> (login stały). Hasło możesz zmienić
        później w panelu. Zapisz je w bezpiecznym miejscu.</div>
        ' . ($err !== '' ? '<div class="box err">' . h($err) . '</div>' : '') . '
        <form method="post">
            <input type="hidden" name="csrf" value="' . h($_SESSION['inst_csrf'] ?? '') . '">
            <input type="hidden" name="action" value="step4">
            <label>Hasło admina <small>(min. 6 znaków, zalecane 8+)</small></label>
            <input type="password" name="pass1" autocomplete="new-password" required>
            <label>Powtórz hasło</label>
            <input type="password" name="pass2" autocomplete="new-password" required>
            <button class="btn" type="submit">Dalej — podsumowanie</button>
            <a class="btn ghost" href="install.php?krok=3">Wstecz</a>
        </form>';
}

function inst_step5(array $v): string
{
    $sum = fn(string $k, string $l): string =>
        '<tr><td>' . $l . '</td><td>' . h($v[$k]) . '</td></tr>';

    return '<h2>Krok 5/6 — Instalacja</h2>
        <div class="box">Sprawdź dane. Po kliknięciu „Rozpocznij instalację” powstanie
        <code>config.php</code>, baza i tabele oraz konto admin.</div>
        <table class="sum">
            ' . $sum('db_host', 'Host / port bazy') . '
            ' . $sum('db_name', 'Nazwa bazy') . '
            ' . $sum('db_user', 'Użytkownik bazy') . '
            ' . $sum('addr', 'Ulica') . '
            ' . $sum('city', 'Kod i miasto') . '
            ' . $sum('phone', 'Telefon') . '
            ' . $sum('maps', 'Link Google') . '
            ' . ($v['site'] !== '' ? $sum('site', 'URL panelu') : '<tr><td>URL panelu</td><td>—</td></tr>') . '
            <tr><td>Konto</td><td>admin + hasło (nie wyświetlamy)</td></tr>
        </table>
        <form method="post">
            <input type="hidden" name="csrf" value="' . h($_SESSION['inst_csrf'] ?? '') . '">
            <input type="hidden" name="action" value="step5">
            <button class="btn" type="submit">Rozpocznij instalację</button>
            <a class="btn ghost" href="install.php?krok=4">Wstecz</a>
        </form>';
}

function inst_step6_body(): string
{
    $site = '';
    if (defined('SITE_URL') && SITE_URL !== '') {
        $site = rtrim(SITE_URL, '/') . '/serwis.php';
    } else {
        $site = 'serwis.php';
    }
    return '<h2>Krok 6/6 — Gotowe!</h2>
        <div class="box ok"><strong>Instalacja zakończona.</strong> Baza, tabele i konto
        <strong>admin</strong> zostały utworzone.</div>
        <div class="box">
            <strong>1.</strong> Zapisz dane logowania: login <code>admin</code>, hasło nadane w kroku 4.<br>
            <strong>2.</strong> <span style="color:#ff9ba3"><strong>Usuń plik
            <code>install.php</code> z serwera (FTP)</strong></span> — to konieczne dla bezpieczeństwa.<br>
            <strong>3.</strong> Plik <code>config.php</code> zawiera sekrety — nie udostępniaj go nikomu.
            <a class="btn" href="' . h($site) . '">Przejdź do panelu</a>
        </div>';
}

function inst_render_step6(): void
{
    inst_render(6);
}

/** Zapisuje config.php z podanymi danymi — bez uruchamiania setupu bazy.
 *  (require config.php i db() muszą działać na poziomie pliku, nie w funkcji.) */
function inst_write_config(array $db, array $dane, string $pass): void
{
    $cfg = inst_build_config([
        'DB_HOST'        => $db['db_host'],
        'DB_PORT'        => $db['db_port'],
        'DB_NAME'        => $db['db_name'],
        'DB_USER'        => $db['db_user'],
        'DB_PASS'        => $db['db_pass'],
        'APP_PASSWORD'   => $pass,
        'AUTH_COOKIE'    => 're_sess_' . bin2hex(random_bytes(8)),
        'SERVICE_ADDRESS'=> $dane['addr'],
        'SERVICE_CITY'   => $dane['city'],
        'SERVICE_PHONE'  => $dane['phone'],
        'GOOGLE_MAPS_URL'=> $dane['maps'],
        'SITE_URL'       => $dane['site'],
    ]);

    if (@file_put_contents(__DIR__ . '/config.php', $cfg) === false) {
        throw new RuntimeException('Nie udało się zapisać pliku config.php (brak prawa zapisu?).');
    }
    @chmod(__DIR__ . '/config.php', 0644);
}
