<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';

auth_require();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    json_out(['success' => true, 'data' => uslugi_list()]);
}

if ($method === 'POST') {
    // 3.1: katalogiem usług zarządza tylko admin (czytanie = wszyscy)
    auth_require_admin();
    // Moduł "katalog usług" wyłączony = zapis i usuwanie nieczynne
    if (!modul('uslugi')) {
        json_fail('Moduł katalogu usług jest wyłączony w ustawieniach panelu.', 403);
    }
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $error = uslugi_add((string) ($_POST['nazwa'] ?? ''));
        if ($error !== null) {
            json_fail($error, 400);
        }
        json_out(['success' => true, 'data' => uslugi_list()]);
    }

    if ($action === 'remove') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !uslugi_remove($id)) {
            json_fail('Nie znaleziono usługi.', 404);
        }
        json_out(['success' => true, 'data' => uslugi_list()]);
    }

    json_fail('Nieznana akcja.', 400);
}

json_fail('Nieznane żądanie.', 400);