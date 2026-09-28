<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';

auth_require();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Podsumowanie zdjęć (liczba + zajęte miejsce) - 3.1: tylko admin
if ($method === 'GET') {
    auth_require_admin();
    json_out(['success' => true, 'data' => photos_stats()]);
}

// Zmiana hasła aplikacji
if ($method === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $current = (string) ($_POST['current'] ?? '');
    $next    = (string) ($_POST['next'] ?? '');

    $error = change_own_password($current, $next);
    if ($error !== null) {
        json_fail($error);
    }
    json_out(['success' => true]);
}

json_fail('Nieznane żądanie.', 400);