<?php
declare(strict_types=1);

// Brak config.php = panel jeszcze nie skonfigurowany -> kierujemy do instalatora.
if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

require __DIR__ . '/config.php';

// Znacznik dla partiali: wejście wprost do pliku partials/*.php niczego nie renderuje
define('SERWIS_PANEL', true);

// Strona jest dynamiczna (zależna od sesji) — nie może być cache'owana.
// Bez tego LiteSpeed zwracał starą wersję HTML po każdej zmianie plików.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Obsługa logowania / wylogowania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $loginError = auth_login(
        (string) ($_POST['login'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );
    if ($loginError === null) {
        // ?powitanie=1 — po zalogowaniu pokazujemy okno podsumowania dnia
        header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?') . '?powitanie=1');
        exit;
    }
}

if (isset($_GET['logout'])) {
    auth_logout();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?'));
    exit;
}

$authenticated = auth_is_authenticated();
$currentUser = auth_user();

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/login.php';
require __DIR__ . '/partials/panel.php';
require __DIR__ . '/partials/modal-ustawienia.php';
require __DIR__ . '/partials/modaly.php';
require __DIR__ . '/partials/modal-karta.php';
require __DIR__ . '/partials/modal-zdjecia.php';
require __DIR__ . '/partials/wydruk.php';
require __DIR__ . '/partials/scripts.php';
