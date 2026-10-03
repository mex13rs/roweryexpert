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
// 3.10.0: panel z poufnymi danymi klientów - zakaz ramki (clickjacking),
// zakaz sniffowania typu, brak wysyłania referrera na obce domeny.
naglowki_bezpieczenstwa();

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

// Reset hasła: żądanie wysyłki (bez logowania). Zawsze redirect z flagą -
// odświeżenie strony nie powtórzy wysyłki.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_reset') {
    $resetWynik = reset_wyslij((string) ($_POST['login'] ?? ''));
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?')
        . ($resetWynik === 'limit' ? '?reset_limit=1' : '?reset_wyslane=1'));
    exit;
}

// Potwierdzenie linkiem z maila - hasło zmienia się dopiero tutaj
if (isset($_GET['reset']) && is_string($_GET['reset'])) {
    $resetOk = reset_potwierdz($_GET['reset']) === 'ok';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?')
        . ($resetOk ? '?reset_ok=1' : '?reset_blad=1'));
    exit;
}

// Komunikaty ekranu logowania (ustawiane po redirectu)
$resetMsg = null;
$resetErr = null;
if (isset($_GET['reset_wyslane'])) {
    $resetMsg = 'Jeśli podany login jest kontem administratora, wysłaliśmy nowe hasło '
        . 'na adres mailowy panelu. Sprawdź pocztę (także folder SPAM) i kliknij link potwierdzający.';
    // 3.8.9: maskowany adres (med***rs@gmail.com) - admin rozpozna skrzynkę,
    // a pełny adres nie zdradzamy na publicznym ekranie logowania. Adres
    // pochodzi z ustawień globalnych, więc komunikat jest identyczny także
    // dla nieistniejącego loginu (anty-enumeracja zachowana).
    $mask = reset_email_mask(reset_email_adres());
    if ($mask !== '') {
        $resetMsg .= ' Wysłane na: ' . $mask . '.';
    }
}
if (isset($_GET['reset_limit'])) {
    $resetErr = 'Zbyt wiele prób wysyłki - spróbuj ponownie za kilka minut.';
}
if (isset($_GET['reset_ok'])) {
    $resetMsg = 'Hasło zostało zmienione. Zaloguj się hasłem z wiadomości mailowej '
        . '- od razu poprosimy o ustawienie własnego.';
}
if (isset($_GET['reset_blad'])) {
    $resetErr = 'Link jest nieprawidłowy, wygasł albo został już użyty. '
        . 'Możesz poprosić o nowy przez „Nie pamiętam hasła”.';
}
$pokazReset = isset($_GET['zapomnia']);   // formularz resetu zamiast logowania

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
