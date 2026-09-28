<?php defined('SERWIS_PANEL') or exit; ?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoweryExpert - Panel Serwisowy</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Wczytywanie biblioteki QRious bez sumy kontrolnej integrity, aby uniknąć blokowania przez przeglądarkę -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <!-- Dane instancji dla JS (m.in. link do wizytki Google dla QR na wydruku) -->
    <script>window.APP_CFG = <?= json_encode(['mapsUrl' => dane_instancji()['maps_url']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <link rel="stylesheet" href="assets/css/panel.css?v=<?= wersja_aplikacji() ?>">
</head>
<body class="dark-theme"<?= ($authenticated && isset($_GET['powitanie'])) ? ' data-powitanie="1"' : '' ?>>

