<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API ustawien panelu (moduly)
 |  GET                    -> lista wlaczonych modulow
 |  POST action=modules    -> zapis listy modulow (JSON w polu "moduly")
 --------------------------------------------------------------- */

require __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    auth_require();

    if ($method === 'GET') {
        json_out(['success' => true, 'data' => ['moduly' => moduly()]]);
    }

    if ($method === 'POST' && ($_POST['action'] ?? '') === 'modules') {
        auth_require_admin();   // 3.1: zmiana modułów tylko przez admina
        $incoming = json_decode((string) ($_POST['moduly'] ?? ''), true);
        if (!is_array($incoming)) {
            json_fail('Nieprawidłowa lista modułów.');
        }

        $clean = [];
        foreach (moduly_dostepne() as $m) {
            // Brak klucza w zadaniu = bez zmian (modul domyslnie wlaczony)
            $clean[$m] = array_key_exists($m, $incoming) ? (bool) $incoming[$m] : true;
        }

        setting_set('moduly', json_encode($clean));
        moduly(true);   // odswiez cache w tym zapytaniu

        json_out(['success' => true, 'data' => ['moduly' => moduly()]]);
    }

    json_fail('Nieznane żądanie.', 400);
} catch (Throwable $e) {
    error_log('[ustawienia.php] ' . $e->getMessage());
    json_fail('Błąd serwera: ' . $e->getMessage(), 500);
}
