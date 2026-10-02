<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API ustawien panelu (moduly)
 |  GET                    -> lista wlaczonych modulow
 |  POST action=modules    -> zapis listy modulow (JSON w polu "moduly")
 --------------------------------------------------------------- */

// Brak config.php = instalacja nieukonczona -> JSON zamiast bledu PHP.
if (!is_file(__DIR__ . '/../config.php')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Instalacja nie zostala zakonczona - uruchom install.php']);
    exit;
}

require __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    auth_require();

    if ($method === 'GET') {
        json_out(['success' => true, 'data' => [
            'moduly' => moduly(),
            'dane_instancji' => dane_instancji(),
            'update' => check_update(),
        ]]);
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'check_update') {
        json_out(['success' => true, 'data' => ['update' => check_update(true)]]);
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'do_update') {
        auth_require_admin();
        json_out(do_update());
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'modules') {
        auth_require_admin();   // 3.1: zmiana modułów tylko przez admina
        $incoming = json_decode((string) ($_POST['moduly'] ?? ''), true);
        if (!is_array($incoming)) {
            json_fail('Nieprawidłowa lista modułów.');
        }

        $clean = [];
        foreach (moduly_dostepne() as $m) {
            // Brak klucza w zadaniu = wartosc domyslna modulu
            // (patrz modul_domyslnie: "hulajnogi" startuje wylaczony)
            $clean[$m] = array_key_exists($m, $incoming) ? (bool) $incoming[$m] : modul_domyslnie($m);
        }

        setting_set('moduly', json_encode($clean));
        moduly(true);   // odswiez cache w tym zapytaniu

        json_out(['success' => true, 'data' => ['moduly' => moduly()]]);
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'dane_instancji') {
        auth_require_admin();   // edycja danych serwisu tylko przez admina
        $pola = [
            'service_address'  => ['adres', 120],
            'service_city'     => ['miasto', 120],
            'service_phone'    => ['telefon', 32],
            'google_maps_url'  => ['maps_url', 255],
            'site_url'         => ['site_url', 255],
            'reset_email'      => ['e-mail do resetu', 255],
        ];
        $zapis = [];
        foreach ($pola as $klucz => [$etykieta, $max]) {
            $wartosc = trim((string) ($_POST[$klucz] ?? ''));
            if (mb_strlen($wartosc) > $max) {
                json_fail('Pole ' . $etykieta . ' jest za dlugie (maks. ' . $max . ' znakow).');
            }
            if ($klucz === 'google_maps_url' && $wartosc !== ''
                && filter_var($wartosc, FILTER_VALIDATE_URL) === false) {
                json_fail('Link do wizytowki Google musi byc poprawnym adresem URL.');
            }
            if ($klucz === 'site_url' && $wartosc !== ''
                && filter_var($wartosc, FILTER_VALIDATE_URL) === false) {
                json_fail('Adres URL panelu musi byc poprawnym adresem URL.');
            }
            if ($klucz === 'reset_email' && $wartosc !== ''
                && filter_var($wartosc, FILTER_VALIDATE_EMAIL) === false) {
                json_fail('Adres e-mail do resetu musi byc poprawny (np. serwis@domena.pl).');
            }
            setting_set($klucz, $wartosc);
            $zapis[$klucz] = $wartosc;
        }

        json_out(['success' => true, 'data' => ['dane_instancji' => dane_instancji()]]);
    }

    json_fail('Nieznane żądanie.', 400);
} catch (Throwable $e) {
    error_log('[ustawienia.php] ' . $e->getMessage());
    json_fail('Błąd serwera: ' . $e->getMessage(), 500);
}
