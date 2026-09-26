<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

// Strona jest dynamiczna (zależna od sesji) — nie może być cache'owana.
// Bez tego LiteSpeed zwracał starą wersję HTML po każdej zmianie plików.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Obsługa logowania / wylogowania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (auth_login((string) ($_POST['password'] ?? ''))) {
        // ?powitanie=1 — po zalogowaniu pokazujemy okno podsumowania dnia
        header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?') . '?powitanie=1');
        exit;
    }
    $loginError = 'Nieprawidłowe hasło.';
}

if (isset($_GET['logout'])) {
    auth_logout();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/serwis.php', '?'));
    exit;
}

$authenticated = auth_is_authenticated();
?>

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
    <style>
        :root {
            /* Palette Dark - czern Media Expert */
            --bg-dark: #0b0b0b;
            --card-dark: #171717;
            --text-dark-primary: #f7f7f7;
            --text-dark-secondary: #9d9d9d;
            --border-dark: #2c2c2c;

            /* Palette Light - biel sklepu */
            --bg-light: #f4f4f4;
            --card-light: #ffffff;
            --text-light-primary: #0d0d0d;
            --text-light-secondary: #575757;
            --border-light: #e4e4e4;
            
            /* Shared */
            --primary: #ffdd00; /* Media Expert Yellow */
            --primary-hover: #ecc900;
            --primary-light: rgba(255, 221, 0, 0.16);
            --primary-ring: rgba(255, 221, 0, 0.2);   /* obwódka focusa */
            --primary-soft: rgba(255, 221, 0, 0.09);   /* delikatne tlo akcentu */
            --primary-text: #ffdd00; /* zolty akcent tekstowy */
            --success: #10b981;
            --success-hover: #059669;
            --danger: #e2001a;
            --danger-hover: #bd0016;
            --calendar-color: #3587ea;
            --calendar-hover: #1f6fd6;
            --locked: #c8f751; /* hi-viz z kamizelki rowerowej: zgłoszenie zablokowane */
            --card-lighter: rgba(255, 255, 255, 0.04);
            --radius-lg: 10px;
            --radius-md: 8px;
            --radius-sm: 6px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Default to Dark Mode */
            --bg: var(--bg-dark);
            --card: var(--card-dark);
            --text-primary: var(--text-dark-primary);
            --text-secondary: var(--text-dark-secondary);
            --border: var(--border-dark);
        }

        body.light-theme {
            --primary-text: #8a7300; /* ciemne zloto - czytelne na bialym */
            --bg: var(--bg-light);
            --card: var(--card-light);
            --text-primary: var(--text-light-primary);
            --text-secondary: var(--text-light-secondary);
            --border: var(--border-light);
            --card-lighter: rgba(0, 0, 0, 0.04);
            color-scheme: light;
        }

        /* --- WARIANTY KOLORU AKCENTU (przycisk palety w nagłówku) ---
           Domyślnie żółty Media Expert (bez klasy). Wybór inny = klasa
           body.accent-nazwa nadpisująca tokeny --primary*.
           Motyw jasny dostaje ciemniejszy wariant tekstu (--primary-text),
           żeby akcent był czytelny na białym tle. */
        body.accent-zielony {
            --primary: #4ade80;
            --primary-hover: #22c55e;
            --primary-text: #4ade80;
            --primary-light: rgba(74, 222, 128, 0.16);
            --primary-ring: rgba(74, 222, 128, 0.2);
            --primary-soft: rgba(74, 222, 128, 0.09);
        }

        body.accent-zielony.light-theme { --primary-text: #15803d; }

        body.accent-czerwony {
            --primary: #f87171;
            --primary-hover: #ef4444;
            --primary-text: #f87171;
            --primary-light: rgba(248, 113, 113, 0.16);
            --primary-ring: rgba(248, 113, 113, 0.2);
            --primary-soft: rgba(248, 113, 113, 0.09);
        }

        body.accent-czerwony.light-theme { --primary-text: #b91c1c; }

        body.accent-niebieski {
            --primary: #60a5fa;
            --primary-hover: #3b82f6;
            --primary-text: #60a5fa;
            --primary-light: rgba(96, 165, 250, 0.16);
            --primary-ring: rgba(96, 165, 250, 0.2);
            --primary-soft: rgba(96, 165, 250, 0.09);
        }

        body.accent-niebieski.light-theme { --primary-text: #1d4ed8; }

        body.accent-pomaranczowy {
            --primary: #fb923c;
            --primary-hover: #f97316;
            --primary-text: #fb923c;
            --primary-light: rgba(251, 146, 60, 0.16);
            --primary-ring: rgba(251, 146, 60, 0.2);
            --primary-soft: rgba(251, 146, 60, 0.09);
        }

        body.accent-pomaranczowy.light-theme { --primary-text: #c2410c; }

        /* Paleta kolorów - panel z kulkami pod przyciskiem */
        .accent-picker {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 60;
            display: none;
            gap: 0.55rem;
            padding: 0.65rem 0.75rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.4);
        }

        .accent-picker.open {
            display: flex;
        }

        .accent-swatch {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid transparent;
            background: var(--sw);
            cursor: pointer;
            padding: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .accent-swatch:hover {
            transform: scale(1.15);
        }

        .accent-swatch.active {
            border-color: var(--text-primary);
            box-shadow: 0 0 0 2px var(--card);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Barlow', sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            line-height: 1.5;
            /* Natywne kontrolki (picker dat) w ciemnej kolorystyce */
            color-scheme: dark;
        }

        h1, h2, h3, h4 {
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
        }

        /* Container & Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            width: 100%;
            flex-grow: 1;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Nowe logo graficzne (PNG) — nieco większe niż dawny kafel 48 px */
        .logo-img {
            height: 54px;
            width: auto;
            display: block;
        }

        /* Wariant Media Expert: na jasnym tle logo staje sie czarne */
        body.light-theme .logo-img {
            filter: brightness(0);
        }

        .logo-text h1 {
            font-size: 1.75rem;
            letter-spacing: -0.025em;
            color: var(--text-primary);
        }

        .logo-text p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .header-actions {
            position: relative;   /* kotwica dla panelu palety kolorów */
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Theme Toggle Button */
        .btn-icon {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-icon:hover {
            border-color: var(--primary-text);
            color: var(--primary-text);
        }

        /* Grid System */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 460px 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards and Elements */
        .card {
            background-color: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: var(--transition);
        }

        .card-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            letter-spacing: 0.01em;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        body.light-theme input, 
        body.light-theme textarea,
        body.light-theme select {
            background-color: rgba(0, 0, 0, 0.02);
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary-text);
            box-shadow: 0 0 0 3px var(--primary-ring);
        }

        /* Mobile-specific fixes */
        input[type="text"],
        input[type="tel"],
        input[type="date"],
        textarea,
        select {
            font-size: 16px !important; /* Prevents iOS zoom on focus */
        }
        
        /* Prevent overscroll/bounce on iOS */
        html {
            overscroll-behavior: none;
        }
        
        /* Fix for iOS safe areas */
        @supports (padding: env(safe-area-inset-top)) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
        
        /* Pola dat: ikona kalendarza w kolorze motywu, bez natywnych strzałek */
        input[type="date"] {
            min-height: 43px;
            position: relative;
        }

        input[type="date"]::-webkit-inner-spin-button,
        input[type="date"]::-webkit-clear-button {
            display: none;
            -webkit-appearance: none;
        }

        input[type="date"]::-webkit-datetime-edit {
            color: var(--text-primary);
            font-family: inherit;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.45;
            padding: 5px;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        /* Ciemny motyw: natywna (czarna) ikona odwrócona na jasną */
        body:not(.light-theme) input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Barlow', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.85rem 1.5rem;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #141414;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-calendar {
            background: var(--calendar-color);
            color: white;
        }

        .btn-calendar:hover {
            background: var(--calendar-hover);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-danger-outline {
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            width: auto;
        }

        .btn-danger-outline:hover {
            background: var(--danger);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-hover);
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        /* History & List Section */
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-box input {
            padding-left: 2.5rem;
            padding-right: 2.75rem;
        }

        .search-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
            width: 18px;
            height: 18px;
        }

        .filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Dashboard podsumowań — kafle nad listą (klik = filtr) */
        .dash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
            gap: 8px;
            margin-top: 1rem;
        }

        .dash-tile {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            padding: 0.7rem 0.9rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: left;
            transition: var(--transition);
        }

        .dash-tile:hover {
            border-color: var(--primary-text);
        }

        .dash-tile strong {
            font-size: 1.4rem;
            line-height: 1.1;
            color: var(--text-primary);
        }

        .dash-tile span {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .dash-tile.active {
            border-color: var(--primary);
            box-shadow: inset 0 0 0 1px var(--primary);
            background: var(--primary);
        }

        .dash-tile.active strong {
            color: #141414;
        }

        .dash-tile.active span {
            color: rgba(0, 0, 0, 0.7);
        }

        .dash-tile.tile-ready strong { color: var(--success); }
        .dash-tile.tile-overdue strong { color: var(--danger); }
        .dash-tile.tile-today strong { color: var(--primary-text); }
        .dash-tile.tile-tomorrow strong { color: var(--calendar-color); }

        /* Sortowanie listy */
        .sort-box {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        .sort-select {
            background: var(--card);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.78rem;
            padding: 0.4rem 0.5rem;
            cursor: pointer;
        }

        .filter-btn {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn:hover, .filter-btn.active {
            border-color: var(--primary);
            color: #141414;
            background: var(--primary);
        }

        /* Liczniki na przyciskach filtrów (terminy, kosz) */
        .filter-count {
            display: inline-block;
            min-width: 18px;
            padding: 0 5px;
            margin-left: 0.35rem;
            border-radius: 999px;
            background: var(--danger);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
        }

        .filter-count.count-neutral {
            background: var(--primary);
            color: #141414;
        }

        /* Numer serwisowy na karcie (odpowiednik etykiety QR) */
        .service-no {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            color: var(--text-secondary);
            border: 1px dashed var(--border);
            border-radius: 999px;
            padding: 0.1rem 0.55rem;
            margin-left: 0.4rem;
            vertical-align: middle;
        }

        /* Zgłoszenie po terminie odbioru */
        .overdue-badge {
            display: inline-block;
            margin-left: 0.4rem;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.35);
            font-size: 0.7rem;
            font-weight: 700;
            vertical-align: middle;
        }

        /* Zgłoszenie w koszu */
        .service-item-card.is-deleted {
            opacity: 0.7;
        }

        /* Dwa pola w rzędzie (np. daty w modalu edycji) */
        .form-row {
            display: flex;
            gap: 0.75rem;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 0;
        }

        /* Przycisk aparatu przy wyszukiwarce — skaner kodu QR (mobile) */
        .scan-btn {
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
        }

        .scan-btn:hover, .scan-btn:active {
            color: #141414;
            border-color: var(--primary);
            background: var(--primary);
        }

        /* Skaner kodu QR — nakładka z podglądem z aparatu */
        .scan-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 92%;
            max-width: 420px;
            padding: 1.25rem;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: var(--transition);
        }

        .modal-overlay.active .scan-panel {
            transform: scale(1);
        }

        .scan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .scan-header h3 {
            font-size: 1.05rem;
            margin: 0;
        }

        .scan-video-wrap {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: #000;
            aspect-ratio: 4 / 3;
        }

        .scan-video-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .scan-frame {
            position: absolute;
            inset: 10%;
            border: 2px solid var(--locked);
            border-radius: var(--radius-sm);
            box-shadow: 0 0 0 999px rgba(0, 0, 0, 0.4);
            pointer-events: none;
        }

        .scan-hint {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0.75rem 0;
            min-height: 1.2em;
        }

        /* Karta podglądu zgłoszenia (bez edycji) */
        .detail-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem 1rem;
            margin-bottom: 1.25rem;
        }

        .detail-grid .d-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .detail-grid .d-val {
            font-weight: 600;
            word-break: break-word;
            white-space: pre-wrap;
        }

        .detail-grid .d-val a {
            color: var(--primary-text);
        }

        /* Notatki w karcie — aktywne pole tekstowe z autozapisem */
        .detail-grid .detail-notes-input {
            width: 100%;
            min-height: 4.5rem;
            padding: 0.5rem 0.65rem;
            font: inherit;
            font-weight: 500;
            color: var(--text-primary);
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            resize: vertical;
        }

        .detail-grid .detail-notes-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Kalendarz terminów — widok miesięczny */
        .calendar-panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 94%;
            max-width: 780px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.25rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: var(--transition);
        }

        .modal-overlay.active .calendar-panel {
            transform: scale(1);
        }

        .calendar-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .calendar-header h3 {
            flex: 1;
            margin: 0;
            text-align: center;
            font-size: 1.15rem;
        }

        .calendar-nav {
            width: 34px;
            height: 34px;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text);
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
        }

        .calendar-nav:hover, .calendar-nav:active {
            border-color: var(--primary);
            color: #141414;
            background: var(--primary);
        }

        .calendar-weekdays,
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        .calendar-weekdays span {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary);
            padding-bottom: 4px;
        }

        .cal-cell {
            min-height: 76px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 4px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            overflow: hidden;
        }

        .cal-cell.other-month {
            opacity: 0.4;
        }

        .cal-cell.today {
            border-color: var(--primary-text);
            box-shadow: inset 0 0 0 1px var(--primary);
        }

        .cal-daynum {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
        }

        .cal-cell.today .cal-daynum {
            color: var(--primary-text);
        }

        .cal-chip {
            display: block;
            width: 100%;
            text-align: left;
            border: none;
            cursor: pointer;
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1.2;
            padding: 3px 5px;
            border-radius: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cal-chip.chip-progress {
            background: var(--primary);
            color: #141414;
            border-left: 3px solid rgba(0, 0, 0, 0.55);
        }

        .cal-chip.chip-ready {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border-left: 3px solid var(--success);
        }

        .cal-chip.chip-overdue {
            outline: 1px solid var(--danger);
        }

        /* Odebrany rower — spokojne szare tło (jak przekreślone wykonane) */
        .cal-chip.chip-done {
            background: var(--card-lighter);
            color: var(--text-secondary);
            border-left: 3px solid var(--text-secondary);
            opacity: 0.85;
        }

        .cal-more {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-secondary);
            padding-left: 4px;
            cursor: pointer;
            align-self: flex-start;
        }

        .cal-more:hover,
        .cal-more:focus-visible {
            color: var(--primary-text);
            outline: none;
        }

        /* Dymek „więcej" — powiększony widok dnia ze wszystkimi zgłoszeniami */
        .cal-popover {
            position: fixed;
            z-index: 3000;
            min-width: 250px;
            max-width: 330px;
            max-height: 65vh;
            overflow-y: auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
            padding: 0.6rem;
            /* Schowany, ale mierzalny — po to animacja rozszerzania z komórki dnia */
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: scale(0.86);
            transform-origin: top left;
            transition: opacity 0.15s ease,
                        transform 0.2s cubic-bezier(0.2, 0.8, 0.3, 1),
                        visibility 0s linear 0.2s;
        }

        .cal-popover.open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: scale(1);
            transition: opacity 0.15s ease,
                        transform 0.2s cubic-bezier(0.2, 0.8, 0.3, 1),
                        visibility 0s linear 0s;
        }

        .cal-popover-title {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: capitalize;
            margin-bottom: 0.45rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid var(--border);
        }

        .cal-pop-item {
            display: block;
            width: 100%;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.72rem;
            padding: 5px 7px;
            margin-bottom: 3px;
            border-radius: var(--radius-sm);
            border-left: 3px solid transparent;
        }

        .cal-pop-item:hover,
        .cal-pop-item:focus-visible {
            background: var(--card-lighter);
            outline: none;
        }

        .cal-pop-item .cal-pop-name {
            display: block;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cal-pop-item .cal-pop-meta {
            display: block;
            font-size: 0.65rem;
            color: var(--text-secondary);
        }

        .cal-pop-item.s-progress { border-left-color: var(--primary-text); }
        .cal-pop-item.s-ready { border-left-color: var(--success); }
        .cal-pop-item.s-done { border-left-color: var(--text-secondary); }
        .cal-pop-item.is-overdue { outline: 1px solid var(--danger); }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.25rem;
            margin-top: 0.85rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .calendar-legend i {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 4px;
            vertical-align: middle;
        }

        .cal-dot.dot-progress { background: var(--primary); }
        .cal-dot.dot-ready { background: var(--success); }
        .cal-dot.dot-overdue { background: transparent; border: 2px solid var(--danger); }
        .cal-dot.dot-done { background: var(--text-secondary); opacity: 0.85; }

        /* Service Cards List */
        .services-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            max-height: 700px;
            overflow-y: auto;
            /* Obydwa paddingi: bez lewego obramówka hover (scale 1.01) była
               przycinana przez overflow kontenera z jednej strony */
            padding: 0.3rem 0.6rem;
        }

        /* Custom Scrollbar for list */
        .services-list::-webkit-scrollbar {
            width: 6px;
        }
        .services-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .services-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }
        .services-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .service-item-card {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            /* Szyna statusu po lewej: kolor czytelny peryferyjnym wzrokiem */
            border-left-width: 4px;
            border-left-color: var(--border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: var(--transition);
            /* Kliknięcie w treść karty otwiera kartę zgłoszenia */
            cursor: pointer;
        }

        body.light-theme .service-item-card {
            background: rgba(0, 0, 0, 0.01);
        }

        .service-item-card.card-in_progress {
            border-left-color: var(--primary-text);
        }

        .service-item-card.card-completed {
            border-left-color: var(--success);
        }

        .service-item-card.card-picked_up {
            border-left-color: var(--text-secondary);
        }

        .service-item-card:hover {
            border-top-color: var(--primary-text);
            border-right-color: var(--primary-text);
            border-bottom-color: var(--primary-text);
        }

        .service-item-card.pending-card:hover {
            border-left-color: var(--locked);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .item-info h3 {
            font-size: 1.15rem;
            margin-bottom: 0.25rem;
        }

        .phone-link {
            color: var(--primary-text);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.9rem;
        }

        .phone-link:hover {
            text-decoration: underline;
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            cursor: pointer;
            user-select: none;
            transition: var(--transition);
        }

        .status-in_progress {
            background: var(--primary);
            color: #141414;
            border: 1px solid #e2c400;
        }
        .status-in_progress:hover {
            background: var(--primary);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-completed:hover {
            background: rgba(16, 185, 129, 0.25);
        }

        .status-picked_up {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        .status-picked_up:hover {
            background: rgba(148, 163, 184, 0.25);
        }

        .item-details {
            font-size: 0.9rem;
            color: var(--text-secondary);
            border-top: 1px dashed var(--border);
            border-bottom: 1px dashed var(--border);
            padding: 0.75rem 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .detail-row {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .detail-val {
            color: var(--text-primary);
            font-weight: 500;
        }

        .fault-desc {
            grid-column: span 2;
            margin-top: 0.25rem;
            white-space: pre-wrap;
            background: rgba(0,0,0,0.15);
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            font-family: monospace;
            font-size: 0.85rem;
        }

        body.light-theme .fault-desc {
            background: rgba(0,0,0,0.03);
        }

        .item-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn-action {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.4rem 0.8rem;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: var(--transition);
        }

        .btn-action:hover {
            border-color: var(--primary-text);
            color: var(--primary-text);
        }

        .btn-action.action-calendar:hover {
            border-color: var(--calendar-color);
            color: var(--calendar-color);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        /* ==========================================================================
           ZGŁOSZENIA OCZEKUJĄCE NA POTWIERDZENIE (utworzone na mobile)
           ========================================================================== */
        .service-item-card.pending-card {
            position: relative;
            overflow: hidden;
            border-left-color: var(--locked);
        }

        .pending-mask {
            position: absolute;
            inset: 0;
            z-index: 5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.9rem;
            padding: 1rem;
            text-align: center;
            background: rgba(12, 12, 12, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--radius-md);
        }

        body.light-theme .pending-mask {
            background: rgba(0, 0, 0, 0.12);
        }

        .pending-note {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: #fff;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.7);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        body.light-theme .pending-note {
            color: var(--text-light-primary);
            text-shadow: 0 1px 6px rgba(255, 255, 255, 0.9);
        }

        .pending-note svg {
            width: 16px;
            height: 16px;
            flex: none;
            color: var(--locked);
        }

        .btn-confirm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.8rem 1.75rem;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            width: auto;
            color: #fff;
            background: var(--success);
            transition: var(--transition);
        }

        .btn-confirm:hover {
            background: var(--success-hover);
        }

        .btn-confirm svg {
            width: 18px;
            height: 18px;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Footer */
        footer.app-footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
            border-top: 1px solid var(--border);
            margin-top: 3rem;
        }

        /* Delikatny link do instrukcji w stopce */
        .app-footer .footer-link {
            color: inherit;
            text-decoration: underline;
            text-underline-offset: 2px;
            text-decoration-thickness: 1px;
            transition: var(--transition);
        }

        .app-footer .footer-link:hover {
            color: var(--primary-text);
        }

        /* Powitanie po zalogowaniu — liczniki odbiorów (dziś / jutro) */
        .welcome-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .welcome-stat {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1rem 0.75rem;
            text-align: center;
        }

        .welcome-stat strong {
            display: block;
            font-size: 1.9rem;
            line-height: 1.1;
            color: var(--primary-text);
        }

        .welcome-stat span {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        /* Modal Settings */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            transform: scale(0.95);
            transition: var(--transition);
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--primary-text);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            z-index: 1000;
        }

        .toast {
            background: var(--card);
            color: var(--text-primary);
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateY(20px);
            opacity: 0;
            animation: slideIn 0.3s forwards;
            font-weight: 500;
        }

        @keyframes slideIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .toast.success {
            border-left-color: var(--success);
        }

        .toast.info {
            border-left-color: var(--calendar-color);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        /* ==========================================================================
           PHOTOS: upload previews, thumbnails, gallery modal, lightbox
           ========================================================================== */
        input[type="file"] {
            padding: 0.6rem 0.75rem;
            cursor: pointer;
        }

        input[type="file"]::file-selector-button {
            background: var(--border);
            border: none;
            color: var(--text-primary);
            padding: 0.45rem 0.85rem;
            border-radius: var(--radius-sm);
            margin-right: 0.75rem;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition);
        }

        input[type="file"]::file-selector-button:hover {
            background: var(--primary);
            color: #141414;
        }

        .photo-previews {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .photo-previews:empty {
            display: none;
        }

        .photo-preview {
            position: relative;
            width: 68px;
            height: 68px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photo-preview .remove-photo {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.75);
            color: #fff;
            font-size: 0.75rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-preview .remove-photo:hover {
            background: var(--danger);
        }

        .item-photos {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            grid-column: span 2;
            margin-top: 0.25rem;
        }

        .photo-thumb {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border);
            padding: 0;
            background: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .photo-thumb:hover {
            border-color: var(--primary-text);
            transform: scale(1.05);
        }

        .photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
            max-height: 55vh;
            overflow-y: auto;
        }

        .photo-tile {
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .photo-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            cursor: zoom-in;
        }

        .photo-tile .delete-photo {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.75);
            color: #fff;
            font-size: 0.85rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-tile .delete-photo:hover {
            background: var(--danger);
        }

        .photos-empty {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--text-secondary);
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }

        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0, 0, 0, 0.92);
            display: none;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox img {
            max-width: 92vw;
            max-height: 92vh;
            border-radius: var(--radius-sm);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        }

        /* ==========================================================================
           LOGIN SCREEN (hasło raz dziennie)
           ========================================================================== */
        .login-screen {
            position: fixed;
            inset: 0;
            z-index: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            padding: 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-card .logo-section {
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .login-card h2 {
            margin-bottom: 1.5rem;
            font-size: 1.15rem;
        }

        .login-error {
            color: var(--danger);
            font-size: 0.9rem;
            margin: -0.5rem 0 1rem;
            font-weight: 600;
        }

        /* ==========================================================================
           UPLOAD OVERLAY (wgrywanie zdjęcia)
           ========================================================================== */
        [hidden] {
            display: none !important;
        }

        /* Ukryte inputy plików (wyzwalane przez etykiety przycisków) */
        .file-input-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        /* Wybór źródła zdjęć: galeria / aparat */
        .photo-sources {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
        }

        .photo-source-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.6rem 1rem;
            border: 1px dashed var(--border);
            border-radius: 10px;
            background: var(--card-lighter);
            color: var(--text);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .photo-source-btn:hover,
        .photo-source-btn:active {
            border-color: var(--primary-text);
            background: var(--primary-soft);
        }

        .photo-source-btn svg {
            flex: none;
        }

        /* Zakładki ustawień */
        .settings-tabs {
            display: flex;
            gap: 0.35rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .settings-tab {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .settings-tab.active {
            background: var(--primary);
            border-color: var(--primary-text);
            color: #141414;
        }

        /* Dodawanie usług */
        .service-add-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .service-add-row input {
            flex: 1;
            min-width: 0;
        }

        .service-add-row .btn {
            width: auto;
            flex: none;
            padding: 0.85rem 1.25rem;
            white-space: nowrap;
        }

        .service-list {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 240px;
            overflow-y: auto;
        }

        .service-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: var(--card-lighter);
            font-size: 0.9rem;
        }

        .service-list .remove-service {
            flex: none;
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 6px;
            background: rgba(220, 38, 38, 0.12);
            color: var(--danger);
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        .service-list .remove-service:hover {
            background: rgba(220, 38, 38, 0.25);
        }

        .service-list-empty {
            color: var(--text-secondary);
            font-size: 0.9rem;
            padding: 0.5rem 0;
        }

        /* Checkboxy usług w formularzu zgłoszenia */
        .service-checkbox-list {
            margin-top: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .service-check {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--card-lighter);
            font-size: 0.88rem;
            cursor: pointer;
            transition: border-color 0.15s;
        }

        .service-check:hover {
            border-color: var(--primary-text);
        }

        .service-check input {
            accent-color: var(--primary-text);
            width: 16px;
            height: 16px;
        }

        .service-checkbox-empty {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .upload-overlay {
            position: fixed;
            inset: 0;
            z-index: 400;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(3px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.1rem;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
            transition: opacity 0.3s cubic-bezier(0.23, 1, 0.32, 1),
                        background-color 0.3s cubic-bezier(0.23, 1, 0.32, 1),
                        backdrop-filter 0.3s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .upload-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Zamknięcie: rowerzysta odjeżdża za krawędź ekranu, przyciemnienie
           równolegle znika, na końcu całe okno (bez nagłego znikania) */
        .upload-overlay.leaving {
            pointer-events: none;
            background-color: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0);
            transition: background-color 0.45s cubic-bezier(0.23, 1, 0.32, 1),
                        backdrop-filter 0.45s cubic-bezier(0.23, 1, 0.32, 1);
            animation: overlay-vanish 0.3s cubic-bezier(0.23, 1, 0.32, 1) 0.68s forwards;
        }

        .upload-overlay.leaving .upload-cyclist {
            animation: cyclist-ride-off 0.7s cubic-bezier(0.35, 0.05, 0.9, 0.55) forwards;
        }

        .upload-overlay.leaving .upload-label {
            animation: label-out 0.3s cubic-bezier(0.23, 1, 0.32, 1) 0.12s forwards;
        }

        @keyframes cyclist-ride-off {
            to { transform: translateX(115vw); }
        }

        @keyframes label-out {
            to { opacity: 0; }
        }

        @keyframes overlay-vanish {
            to { opacity: 0; }
        }

        .upload-cyclist {
            width: 150px;
            height: auto;
            color: var(--primary-text);
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.5));
            animation: cyclist-bob 0.45s ease-in-out infinite alternate;
        }

        @keyframes cyclist-bob {
            from { transform: translateY(0); }
            to   { transform: translateY(-4px); }
        }

        /* Obracające się koła roweru (wykorzystuje istniejące @keyframes spin) */
        .upload-cyclist .wheel {
            transform-box: fill-box;
            transform-origin: center;
            animation: spin 0.9s linear infinite;
        }

        /* Linie szybkości za rowerzystą */
        .upload-cyclist .speed-lines {
            animation: speed-pulse 0.7s ease-in-out infinite;
        }

        @keyframes speed-pulse {
            0%, 100% { opacity: 0.25; transform: translateX(5px); }
            50%      { opacity: 1;    transform: translateX(0); }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .upload-label {
            font-family: 'Barlow', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.04em;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* ==========================================================================
           PRINT RECEIPT TEMPLATE (Optimized for A4 landscape, single page, 2 columns)
           ========================================================================== */
        #print-receipt {
            display: none;
        }

        
        /* ===== MOBILE RESPONSIVE ===== */
        /* ====================================================================
           MOBILE (IS_MOBILE z JS): telefon sluzy do przyjecia i wydania,
           bez przegladania listy - ukrywamy liste, kafle, filtry i sortowanie.
           Wyszukiwarka/skaner zostaja: wynik otwiera karte zgloszenia.
           ==================================================================== */
        body.is-mobile .dash-grid,
        body.is-mobile .list-controls,
        body.is-mobile .services-list {
            display: none;
        }

        /* --- MODUŁY WŁĄCZONE/WYŁĄCZONE (Ustawienia -> Moduły) ---
           Reguły ukrywają elementy statyczne; elementy renderowane przez JS
           warunkują się obecnością flag w MODULY. Wyłączone moduły dostają
           na <body> klasę "off-nazwa". */
        body.off-kalendarz #open-calendar-btn,
        body.off-kalendarz #date-planned-group,
        body.off-kalendarz #edit-date-planned-group,
        body.off-kalendarz #detail-date-planned-label,
        body.off-kalendarz #detail-date-planned,
        body.off-kalendarz .dash-tile[data-filter="overdue"],
        body.off-kalendarz .dash-tile[data-filter="today"],
        body.off-kalendarz .dash-tile[data-filter="tomorrow"],
        body.off-zdjecia #photo-upload-group,
        body.off-zdjecia #photos-modal-input-group,
        body.off-zdjecia #photos-stats-group,
        body.off-skaner #scan-qr-btn,
        body.off-uslugi #service-checkbox-list,
        body.off-uslugi #tab-btn-uslugi,
        body.off-kolorystyka #palette-btn,
        body.off-kolorystyka #accent-picker,
        body.off-wykonane #detail-done-label,
        body.off-wykonane #detail-done-list,
        body.off-kosz .filter-btn[data-filter="trash"] {
            display: none !important;
        }

        /* Wiersz przełącznika w zakładce Moduły */
        .mod-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--card-lighter);
            margin-bottom: 0.6rem;
            cursor: pointer;
        }

        .mod-row input[type="checkbox"] {
            margin-top: 0.2rem;
            accent-color: var(--primary);
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        .mod-row small {
            color: var(--text-secondary);
        }

        /* Lista przełączników modułów — box o stałej wysokości ze scrolliem
           (jak .service-list przy Dodaj usługi), żeby10 modułów mieściło           się na jednym ekranie */
        .mod-list {
            max-height: 240px;
            overflow-y: auto;
        }

        .mod-list .mod-row:last-child {
            margin-bottom: 0;
        }

        .mod-row:has(input:checked) {
            border-color: var(--primary);
        }

        /* Info o zgloszeniu z telefonu w karcie podgladu (lista z maska ukryta) */
        .pending-detail {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            background: var(--primary-light);
            border: 1px solid var(--border);
            border-left: 3px solid var(--locked);
            border-radius: var(--radius-md);
            padding: 0.65rem 0.85rem;
            margin-bottom: 1.1rem;
            font-size: 0.88rem;
            color: var(--text-secondary);
        }

        .pending-detail svg {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            color: var(--locked);
        }

        /* Monit po zapisie z telefonu - caly dostepny widok */
        #phone-success-modal .phone-success-card {
            max-width: 460px;
            text-align: center;
        }

        #phone-success-modal.active .phone-success-card {
            width: 100%;
            max-width: none;
            height: 100%;
            border: none;
            border-radius: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.15rem;
            padding: 2rem 1.5rem;
        }

        .phone-success-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: var(--primary);
            color: #141414;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .phone-success-icon svg {
            width: 36px;
            height: 36px;
        }

        .phone-success-card h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .phone-success-card p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.65;
            color: var(--text-secondary);
        }

        .phone-success-card .btn {
            width: 100%;
            max-width: 320px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem 0.75rem;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card {
                padding: 1.25rem;
                border-radius: var(--radius-md);
            }
            
            .card-title {
                font-size: 1.1rem;
                margin-bottom: 1rem;
            }
            
            /* Form adjustments */
            .form-group {
                margin-bottom: 1rem;
            }
            
            label {
                font-size: 0.8rem;
            }
            
            input, textarea, select {
                padding: 0.65rem 0.75rem;
                font-size: 16px; /* Prevents iOS zoom */
            }
            
            .button-group {
                margin-top: 1rem;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            /* History section */
            .history-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .filters {
                flex-wrap: wrap;
            }

            /* Sortowanie pod filtrami na wąskich ekranach */
            .sort-box {
                width: 100%;
                justify-content: space-between;
                margin-top: 0.15rem;
            }
            
            .filter-btn {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }
            
            /* Kalendarz na mobile — mniejsze komórki */
            .calendar-panel {
                padding: 0.9rem;
                max-height: 92vh;
            }

            .cal-cell {
                min-height: 54px;
                padding: 3px;
                gap: 2px;
            }

            .cal-chip {
                font-size: 0.6rem;
                padding: 2px 3px;
            }
            
            /* Service item cards */
            .services-list {
                max-height: 600px;
            }
            
            .service-item-card {
                padding: 1rem;
            }
            
            .item-header {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .item-details {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .item-actions {
                flex-wrap: wrap;
            }
            
            .btn-action {
                flex: 1 1 calc(50% - 0.5rem);
                min-width: 0;
            }
            
            /* Logo */
            .logo-text h1 {
                font-size: 1.4rem;
            }
            
            /* Status badge */
            .status-badge {
                align-self: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0.75rem 0.5rem;
            }
            
            .logo-img {
                height: 44px;
            }
            
            .logo-text h1 {
                font-size: 1.2rem;
            }
            
            .logo-text p {
                font-size: 0.75rem;
            }
            
            .btn-action {
                flex: 1 1 100%;
            }
        }

        /* Szacunek dla ustawień systemowych użytkownika */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media print {
            /* Wymuszenie orientacji poziomej strony wydruku */
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            /* General browser reset for printing - A4 orientation and details */
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                font-family: 'Barlow', 'Helvetica', 'Arial', sans-serif !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 9.5pt !important;
                display: block !important; /* Reset flexbox body display */
            }

            * {
                transition: none !important;
                animation: none !important;
                box-shadow: none !important;
            }

            /* Hide the main website UI entirely */
            #app-container, .toast-container, .modal-overlay,
            .upload-overlay, .lightbox, .login-screen {
                display: none !important;
            }

            /* Wypełnienie całej wysokości strony wydruku */
            html, body {
                height: 100% !important;
            }

            /* Kontener na całą stronę A4 poziomo - dwie oddzielne obramówki obok siebie */
            #print-receipt {
                display: flex !important;
                flex-direction: row !important;
                align-items: stretch !important;
                width: 100% !important;
                height: calc(100vh - 16mm) !important;
                margin: 0 auto !important;
                padding: 0 !important;
                gap: 10mm !important;
                box-sizing: border-box !important;
                background: #fff !important;
            }

            /* Każda z dwóch połówek strony (klient / serwis) - własna obramówka, pełna wysokość */
            .receipt-column {
                width: 50% !important;
                height: 100% !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                border: 2px solid #000 !important;
                border-radius: 8px !important;
                padding: 8mm !important;
                background: #fff !important;
            }

            .receipt-header {
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }

            .receipt-header h1 {
                font-size: 17pt !important;
                font-family: 'Barlow', sans-serif;
                margin-bottom: 2px;
                letter-spacing: -1px;
                color: #000 !important;
            }

            .receipt-title {
                text-align: center;
                font-size: 10.5pt !important;
                font-weight: 800;
                margin: 8px 0 12px 0 !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .receipt-details {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px;
            }

            .receipt-row {
                border-bottom: 1px solid #ddd;
            }

            .receipt-label {
                font-size: 8.5pt !important;
                font-weight: bold;
                padding: 5px 0;
                text-transform: uppercase;
                width: 32%;
                vertical-align: middle;
            }

            .receipt-value {
                font-size: 9.5pt !important;
                padding: 5px 0;
                text-align: left;
                vertical-align: middle;
            }

            .receipt-value-desc {
                text-align: left;
                padding: 8px !important;
                font-size: 9pt !important;
                white-space: pre-wrap;
                font-family: 'Barlow', sans-serif !important;
                border: 1px solid #000 !important;
                border-radius: 4px;
                margin-top: 6px;
                background: #fff;
                min-height: 90px;
            }

            /* Signature section for A4 — przypięte w dół, tuż nad grubą linią stopki */
            .receipt-signatures {
                display: flex !important;
                justify-content: space-between !important;
                margin-top: auto !important;
                margin-bottom: 12px !important;
                page-break-inside: avoid;
            }

            .signature-box {
                width: 45% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
            }

            .signature-line {
                width: 100% !important;
                border-bottom: 1px dashed #000 !important;
                margin-bottom: 4px !important;
                height: 26px !important; /* Space for physical signature and stamp */
            }

            .signature-label {
                font-size: 7.5pt !important;
                font-weight: bold;
                text-transform: uppercase;
                color: #555;
            }

            .receipt-footer {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-end !important;
                margin-top: 0 !important;
                border-top: 2px solid #000;
                padding-top: 8px;
                page-break-inside: avoid;
            }

            .receipt-address {
                font-size: 7.5pt !important;
                font-weight: bold;
                line-height: 1.4;
                text-align: left;
                width: 60%;
            }

            .receipt-qr-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 35%;
            }

            .receipt-qr-element {
                width: 38px !important;
                height: 38px !important;
                margin-bottom: 3px;
            }

            .receipt-qr-desc {
                font-size: 6.5pt !important;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                margin-top: 1px;
            }

            /* Sekcja "Wykonane czynności" dla kolumny serwisu */
            .service-notes-title {
                font-size: 8.5pt !important;
                font-weight: bold;
                text-transform: uppercase;
                margin: 10px 0 6px 0 !important;
                letter-spacing: 0.5px;
            }
        }
    </style>
</head>
<body class="dark-theme"<?= ($authenticated && isset($_GET['powitanie'])) ? ' data-powitanie="1"' : '' ?>>

    <?php if (!$authenticated): ?>
    <!-- LOGIN SCREEN (hasło podawane raz dziennie) -->
    <div class="login-screen">
        <div class="card login-card">
            <div class="logo-section">
                <img class="logo-img" src="logo.png" alt="RoweryExpert">
                <div class="logo-text">
                    <h1>RoweryExpert</h1>
                    <p>Panel Serwisowy</p>
                </div>
            </div>
            <h2>Zaloguj się</h2>
            <?php if (!empty($loginError)): ?>
                <p class="login-error"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/serwis.php') ?>">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-password">Hasło</label>
                    <input type="password" id="login-password" name="password" placeholder="******" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">Zaloguj</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN APP CONTAINER -->
    <div id="app-container"<?= $authenticated ? '' : ' hidden="hidden"' ?>>
        <div class="container">
            <!-- Header Section -->
            <header>
                <div class="logo-section">
                    <img class="logo-img" src="logo.png" alt="RoweryExpert">
                    <div class="logo-text">
                        <h1>RoweryExpert</h1>
                        <p>Panel Serwisowy</p>
                    </div>
                </div>
                <div class="header-actions">
                    <!-- Calendar Button (kalendarz terminów) -->
                    <button class="btn-icon" id="open-calendar-btn" title="Kalendarz terminów">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 8.25h18M4.5 6.75h15A1.5 1.5 0 0 1 21 8.25v11.25a1.5 1.5 0 0 1-1.5 1.5h-15a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 11.25h.008v.008H8.25v-.008Zm3.75 0h.008v.008H12v-.008Zm3.75 0h.008v.008H15.75v-.008ZM8.25 15h.008v.008H8.25V15Zm3.75 0h.008v.008H12V15Z" />
                        </svg>
                    </button>
                    <!-- Global Settings Button -->
                    <button class="btn-icon" id="open-settings-btn" title="Ustawienia">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                    <!-- Theme Toggle Button -->
                    <button class="btn-icon" id="theme-toggle-btn" title="Zmień motyw">
                        <svg id="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px; display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m8.966-8.966h-2.25m-13.5 0h-2.25m15.022-5.022-1.591 1.591M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9ZM5.636 5.636l1.591 1.591m10.136 10.136 1.591 1.591m-10.136 0-1.591-1.591" />
                        </svg>
                        <svg id="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </button>
                    <!-- Accent Color Button (kolor akcentu) -->
                    <button class="btn-icon" id="palette-btn" title="Kolor akcentu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z" />
                            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
                            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
                            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
                            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
                        </svg>
                    </button>
                    <div class="accent-picker" id="accent-picker">
                        <button class="accent-swatch" data-accent="zolty" title="Żółty Media Expert (domyślny)" style="--sw: #ffdd00"></button>
                        <button class="accent-swatch" data-accent="zielony" title="Zielony" style="--sw: #4ade80"></button>
                        <button class="accent-swatch" data-accent="czerwony" title="Czerwony" style="--sw: #f87171"></button>
                        <button class="accent-swatch" data-accent="niebieski" title="Niebieski" style="--sw: #60a5fa"></button>
                        <button class="accent-swatch" data-accent="pomaranczowy" title="Pomarańczowy" style="--sw: #fb923c"></button>
                    </div>
                    <!-- Logout Button -->
                    <button class="btn-icon" id="logout-btn" title="Wyloguj się">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                
                <!-- Left: Service Form Card -->
                <div class="card">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px; color: var(--primary-text);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Przyjmij nowy rower
                    </h2>
                    
                    <form id="service-form">
                        <div class="form-group">
                            <label for="bike-name">Nazwa Roweru</label>
                            <input type="text" id="bike-name" placeholder="np. Kross Hexagon 5.0, Giant Talon 1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="date-in">Data przyjęcia</label>
                            <input type="date" id="date-in" required>
                        </div>
                        
                        <div class="form-group" id="date-planned-group">
                            <label for="date-planned">Planowany odbiór (kalendarz)</label>
                            <input type="date" id="date-planned" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer-phone">Telefon Klienta</label>
                            <input type="tel" id="customer-phone" placeholder="np. 532-561-152 lub 532561152" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fault-description">Opis usterki / zakres naprawy</label>
                            <textarea id="fault-description" placeholder="Opisz usterkę zgłoszoną przez klienta oraz zakres prac serwisowych..."></textarea>
                            <div class="service-checkbox-list" id="service-checkbox-list"></div>
                        </div>

                        <div class="form-group" id="photo-upload-group">
                            <label>Zdjęcia (opcjonalnie, maks. 10 MB za zdjęcie, łącznie do 100 MB)</label>
                            <div class="photo-sources">
                                <label class="photo-source-btn" for="photos-input">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M12 18.75V21m0 0h12M21 12V8.25m0 0h-3.75m3.75 0V4.5m-3.75 3.75h3.75M14.25 7.5h.008v.008h-.008V7.5Z" />
                                    </svg>
                                    Wybierz z galerii
                                </label>
                                <label class="photo-source-btn" for="camera-input">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175A2.021 2.021 0 0 0 2 9.418V17.1A2.1 2.1 0 0 0 4.1 19.2h15.8A2.1 2.1 0 0 0 22 17.1V9.418c0-1.092-.837-2.013-2.052-2.013a26.815 26.815 0 0 1-1.134-.175 2.31 2.31 0 0 1-1.641-1.055L16.42 5.32A2.3 2.3 0 0 0 14.302 4h-4.6a2.3 2.3 0 0 0-2.118 1.32L6.827 6.175Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                    </svg>
                                    Zrób zdjęcie aparatem
                                </label>
                            </div>
                            <input type="file" id="photos-input" accept="image/*" multiple class="file-input-hidden">
                            <input type="file" id="camera-input" accept="image/*" capture="environment" multiple class="file-input-hidden">
                            <div class="photo-previews" id="photo-previews"></div>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary" id="save-print-calendar-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 1.252a1.125 1.125 0 0 1-1.107 1.328H7.218a1.125 1.125 0 0 1-1.107-1.328L6.34 18m11.32 0H6.34m0 0h11.32M18 10.5h.008v.008H18V10.5Zm-1.8-6.177a1.95 1.95 0 0 1 2.593 0c.38.347.607.82.607 1.32V9.75H4.5V5.643c0-.5.227-.973.607-1.32a1.95 1.95 0 0 1 2.593 0L8.53 5.4a1.95 1.95 0 0 0 2.593 0l.707-.643a1.95 1.95 0 0 1 2.593 0l.707.643a1.95 1.95 0 0 0 2.593 0l.707-.643Z" />
                                </svg>
                                <span id="save-btn-label">Zapisz, Drukuj i Dodaj do Kalendarza</span>
                            </button>
                            <button type="button" class="btn btn-secondary" id="save-only-btn">
                                Zapis do bazy
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Right: Active Services Database -->
                <div class="card">
                    <div class="history-header">
                        <h2 class="card-title" style="margin-bottom: 0; border: none; padding: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px; color: var(--primary-text);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 17.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                            Historia i statusy rowerów
                        </h2>
                        
                        <div class="search-box">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.637 10.637Z" />
                            </svg>
                            <input type="text" id="search-input" placeholder="Szukaj roweru, telefonu, numeru...">
                            <button type="button" class="scan-btn" id="scan-qr-btn" title="Zeskanuj kod QR numeru serwisowego">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dashboard podsumowań — kafle są jednocześnie skrótem do filtrów -->
                    <div class="dash-grid" id="dash-grid">
                        <button type="button" class="dash-tile" data-filter="in_progress">
                            <strong id="dash-in-progress">–</strong><span>W serwisie</span>
                        </button>
                        <button type="button" class="dash-tile tile-ready" data-filter="completed">
                            <strong id="dash-completed">–</strong><span>Gotowe do odbioru</span>
                        </button>
                        <button type="button" class="dash-tile tile-overdue" data-filter="overdue">
                            <strong id="dash-overdue">–</strong><span>Po terminie</span>
                        </button>
                        <button type="button" class="dash-tile tile-today" data-filter="today">
                            <strong id="dash-today">–</strong><span>Odbiory dziś</span>
                        </button>
                        <button type="button" class="dash-tile tile-tomorrow" data-filter="tomorrow">
                            <strong id="dash-tomorrow">–</strong><span>Odbiory jutro</span>
                        </button>
                    </div>

                    <div class="history-header list-controls" style="margin-top: 0.85rem; justify-content: space-between;">
                        <div class="filters">
                            <button class="filter-btn active" data-filter="all">Wszystkie</button>
                            <button class="filter-btn" data-filter="picked_up">Odebrane</button>
                            <button class="filter-btn" data-filter="trash">Kosz <span class="filter-count count-neutral" id="count-trash" hidden></span></button>
                        </div>
                        <label class="sort-box">Sortuj
                            <select class="sort-select" id="sort-select">
                                <option value="planned_asc">Termin odbioru (najbliższy)</option>
                                <option value="planned_desc">Termin odbioru (odległy)</option>
                                <option value="dateIn_desc">Przyjęcia: najnowsze</option>
                                <option value="dateIn_asc">Przyjęcia: najstarsze</option>
                                <option value="name">Nazwa roweru A–Z</option>
                                <option value="status">Wg statusu</option>
                            </select>
                        </label>
                    </div>
                    
                    <div class="services-list" id="services-list-container">
                        <!-- Items rendered dynamically from JS -->
                    </div>
                </div>
                
            </div>
            
            <!-- Footer -->
            <footer class="app-footer">
                <p>&copy; 2026 RoweryExpert. Wszystkie prawa zastrzeżone.</p>
                <p style="margin-top: 0.5rem; font-size: 0.75rem; opacity: 0.7;">Wersja <strong><?= APP_VERSION ?></strong> &middot; <a href="instrukcja.html" class="footer-link">Instrukcja</a></p>
            </footer>
        </div>
    </div>

    <!-- MODAL USTAWIEŃ -->
    <div class="modal-overlay" id="settings-modal">
        <div class="modal-card" style="max-width: 560px;">
            <div class="modal-header">
                <h3 style="font-size: 1.25rem;">Ustawienia</h3>
                <button class="modal-close" id="close-settings-btn">&times;</button>
            </div>

            <div class="settings-tabs">
                <button class="settings-tab active" id="tab-btn-general" data-tab="general">Ogólne</button>
                <button class="settings-tab" id="tab-btn-uslugi" data-tab="uslugi">Dodaj usługi</button>
                <button class="settings-tab" id="tab-btn-moduly" data-tab="moduly">Moduły</button>
            </div>

            <!-- ZAKŁADKA: OGÓLNE -->
            <div class="settings-tab-content" id="tab-content-general">
                <div class="form-group" id="photos-stats-group">
                    <label>Zdjęcia rowerów w bazie</label>
                    <div class="stats-box" id="stats-box" style="background: var(--card-lighter); border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.15rem;">
                        <p style="margin: 0; display: flex; justify-content: space-between;">
                            <span>Liczba zdjęć:</span>
                            <strong id="stats-photos" style="color: var(--primary-text);">…</strong>
                        </p>
                        <p style="margin: 0.5rem 0 0; display: flex; justify-content: space-between;">
                            <span>Zajęte miejsce:</span>
                            <strong id="stats-size" style="color: var(--primary-text);">…</strong>
                        </p>
                    </div>
                    <p id="stats-warn" hidden style="margin: 0.6rem 0 0; font-size: 0.85rem; color: var(--danger); font-weight: 600;"></p>
                    <button class="btn btn-secondary" id="refresh-stats-btn" style="margin-top: 0.75rem;">Odśwież statystyki</button>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0;">

                <h4 style="margin: 0 0 1rem; font-size: 1rem;">Zmiana hasła do aplikacji</h4>
                <div class="form-group">
                    <label for="current-password">Aktualne hasło</label>
                    <input type="password" id="current-password" autocomplete="current-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="new-password">Nowe hasło (min. 6 znaków)</label>
                    <input type="password" id="new-password" autocomplete="new-password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Powtórz nowe hasło</label>
                    <input type="password" id="confirm-password" autocomplete="new-password" placeholder="••••••••">
                </div>
                <button class="btn btn-primary" id="save-password-btn">Zmień hasło</button>
                <p class="settings-hint" id="password-hint" style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.75rem;"></p>
            </div>

            <!-- ZAKŁADKA: DODAJ USŁUGI -->
            <div class="settings-tab-content" id="tab-content-uslugi" hidden>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0 0 1rem;">
                    Dodane usługi pojawią się jako checkboxy w polu „Opis usterki”. Zaznaczone usługi
                    zostaną dopisane do zgłoszenia, Kalendarza Google oraz wydruku.
                </p>
                <label for="new-service-input">Nowa usługa</label>
                <div class="service-add-row">
                    <input type="text" id="new-service-input" placeholder="np. Wymiana dętki">
                    <button class="btn btn-primary" id="add-service-btn" style="flex: none;">Dodaj</button>
                </div>
                <ul class="service-list" id="service-list"></ul>
            </div>

            <!-- ZAKŁADKA: MODUŁY -->
            <div class="settings-tab-content" id="tab-content-moduly" hidden>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0 0 1rem;">
                    Wyłącz nieużywane części panelu — znikną z komputera i z telefonu,
                    a ich funkcje zablokują się także po stronie serwera.
                    Zmiana zapisuje się natychmiast.
                </p>
                <div class="mod-list">
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="kalendarz" checked>
                        <span><strong>Kalendarz</strong><br><small>Widok kalendarza, terminy odbioru, kafle „Odbiory" i linki do Kalendarza Google</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="zdjecia" checked>
                        <span><strong>Zdjęcia</strong><br><small>Wgrywanie i podgląd zdjęć z telefonu oraz miniatury na liście</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="skaner" checked>
                        <span><strong>Skaner QR</strong><br><small>Przycisk skanera kodów QR przy wyszukiwarce</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="uslugi" checked>
                        <span><strong>Katalog usług</strong><br><small>Checkboxy usług w formularzu i zakładka „Dodaj usługi"</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="druk" checked>
                        <span><strong>Drukowanie</strong><br><small>Wydruk potwierdzenia przyjęcia dla klienta</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="kosz" checked>
                        <span><strong>Kosz</strong><br><small>Filtr Kosz, przenoszenie do kosza i przywracanie zgłoszeń</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="kolorystyka" checked>
                        <span><strong>Kolorystyka</strong><br><small>Paleta koloru akcentu w nagłówku; po wyłączeniu logo i faviconka wracają do domyślnego żółtego</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="powitanie" checked>
                        <span><strong>Powitanie</strong><br><small>Okno „Podsumowanie dnia” po zalogowaniu (wymaga modułu Kalendarza)</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="karta_wydania" checked>
                        <span><strong>Karta wydania</strong><br><small>Automatyczny druk Karty Wydania Roweru przy wydaniu; sam przycisk „Wydaj rower” zostaje</small></span>
                    </label>
                    <label class="mod-row">
                        <input type="checkbox" class="mod-toggle" data-mod="wykonane" checked>
                        <span><strong>Wykonane czynności</strong><br><small>Checkboxy w karcie zgłoszenia i ☑ na wydruku Karty Wydania; przyjęcie i katalog usług bez zmian</small></span>
                    </label>
                </div>
                <p class="settings-hint" id="moduly-hint" style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.75rem;"></p>
            </div>
        </div>
    </div>

    <!-- MODAL POTWIERDZENIA (zamiast natywnego okna przeglądarki) -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="modal-card" style="max-width: 420px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.6rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px; color: var(--danger); flex: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span id="confirm-modal-title">Potwierdzenie</span>
                </h3>
                <button class="modal-close" id="confirm-modal-close">&times;</button>
            </div>

            <p id="confirm-modal-message" style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;"></p>

            <div style="display: flex; gap: 0.75rem;">
                <button class="btn btn-secondary" id="confirm-modal-cancel" style="flex: 1 1 0; min-width: 0;">Anuluj</button>
                <button class="btn btn-danger" id="confirm-modal-ok" style="flex: 1 1 0; min-width: 0;">Usuń</button>
            </div>
        </div>
    </div>

    <!-- POWITANIE PO ZALOGOWANIU — ile odbiorów dziś / jutro -->
    <div class="modal-overlay" id="welcome-modal">
        <div class="modal-card" style="max-width: 420px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.6rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px; color: var(--primary-text); flex: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 8.25h18M4.5 6.75h15A2.25 2.25 0 0 1 21 8.25v11.25a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 3 19.5v-11.25a2.25 2.25 0 0 1 2.25-2.25Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 11.25h.008v.008h-.008v-.008Zm3.75 0h.008v.008h-.008v-.008Zm3.75 0h.008v.008h-.008v-.008Zm-7.5 3.75h.008v.008h-.008v-.008Zm3.75 0h.008v.008h-.008v-.008Zm3.75 0h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span>Podsumowanie dnia</span>
                </h3>
                <button class="modal-close" id="welcome-modal-close">&times;</button>
            </div>

            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                Rowery zaplanowane do odbioru:
            </p>

            <div class="welcome-grid">
                <div class="welcome-stat">
                    <strong id="welcome-today">–</strong><span>na dziś</span>
                </div>
                <div class="welcome-stat">
                    <strong id="welcome-tomorrow">–</strong><span>na jutro</span>
                </div>
            </div>

            <p id="welcome-overdue" hidden style="color: var(--danger); font-size: 0.9rem; font-weight: 600; margin-top: 0.85rem;"></p>

            <button class="btn btn-primary" id="welcome-modal-ok" style="width: 100%; margin-top: 1.25rem;">OK</button>
        </div>
    </div>

    <!-- MODAL EDYCJI ZGŁOSZENIA -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-card" style="max-width: 560px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem;">Edytuj zgłoszenie</h3>
                <button class="modal-close" id="edit-modal-close">&times;</button>
            </div>
            <form id="edit-form">
                <div class="form-group">
                    <label for="edit-bike-name">Nazwa roweru</label>
                    <input type="text" id="edit-bike-name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-date-in">Data przyjęcia</label>
                        <input type="date" id="edit-date-in" required>
                    </div>
                    <div class="form-group" id="edit-date-planned-group">
                        <label for="edit-date-planned">Planowany odbiór</label>
                        <input type="date" id="edit-date-planned" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-customer-phone">Telefon klienta</label>
                    <input type="tel" id="edit-customer-phone" required>
                </div>
                <div class="form-group">
                    <label for="edit-fault">Opis usterki / zakres naprawy</label>
                    <textarea id="edit-fault"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit-service-notes">Notatki</label>
                    <textarea id="edit-service-notes" style="min-height: 80px;" placeholder="np. Wymiana dętki, regulacja przerzutek, smarowanie łańcucha…"></textarea>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    <button type="button" class="btn btn-secondary" id="edit-modal-cancel">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MONIT PO ZAPISIE Z TELEFONU (caly ekran): zgloszenie wymaga potwierdzenia na PC -->
    <div class="modal-overlay" id="phone-success-modal">
        <div class="modal-card phone-success-card">
            <span class="phone-success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </span>
            <h3>Zgłoszenie zapisane</h3>
            <p id="phone-success-msg"></p>
            <button type="button" class="btn btn-primary" id="phone-success-ok">OK, rozumiem</button>
        </div>
    </div>

    <!-- MODAL SKANERA QR (aparat) -->
    <div class="modal-overlay" id="scan-modal">
        <div class="scan-panel">
            <div class="scan-header">
                <h3>Skanuj kod QR</h3>
                <button class="modal-close" id="scan-modal-close">&times;</button>
            </div>
            <div class="scan-video-wrap">
                <video id="scan-video" playsinline muted autoplay></video>
                <div class="scan-frame"></div>
            </div>
            <p class="scan-hint" id="scan-hint">Skieruj aparat na kod QR z numerem serwisowym (naklejka na rowerze).</p>
            <button type="button" class="btn btn-secondary" id="scan-cancel-btn" style="width: 100%;">Anuluj</button>
        </div>
    </div>

    <!-- MODAL KALENDARZA TERMINÓW (widok miesięczny) -->
    <div class="modal-overlay" id="calendar-modal">
        <div class="calendar-panel">
            <div class="calendar-header">
                <button type="button" class="calendar-nav" id="cal-prev-btn" title="Poprzedni miesiąc">&lsaquo;</button>
                <h3 id="cal-title">—</h3>
                <button type="button" class="calendar-nav" id="cal-next-btn" title="Następny miesiąc">&rsaquo;</button>
                <button class="modal-close" id="calendar-modal-close" title="Zamknij">&times;</button>
            </div>
            <div class="calendar-weekdays">
                <span>Pon</span><span>Wt</span><span>Śr</span><span>Czw</span><span>Pt</span><span>Sob</span><span>Nd</span>
            </div>
            <div class="calendar-grid" id="cal-grid"></div>
            <div class="calendar-legend">
                <span><i class="cal-dot dot-progress"></i> W serwisie (do wykonania)</span>
                <span><i class="cal-dot dot-ready"></i> Gotowe do odbioru</span>
                <span><i class="cal-dot dot-overdue"></i> Po terminie</span>
                <span><i class="cal-dot dot-done"></i> Odebrane</span>
            </div>
        </div>
    </div>

    <!-- MODAL PODGLĄDU ZGŁOSZENIA (bez edycji, z wydaniem roweru) -->
    <div class="modal-overlay" id="detail-modal">
        <div class="modal-card" style="max-width: 560px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    Podgląd zgłoszenia
                    <span class="service-no" id="detail-service-no" style="margin-left: 0;"></span>
                </h3>
                <button class="modal-close" id="detail-modal-close">&times;</button>
            </div>
            <div class="detail-grid">
                <span class="d-label">Rower:</span>
                <span class="d-val" id="detail-bike-name">—</span>
                <span class="d-label">Status:</span>
                <span class="d-val" id="detail-status">—</span>
                <span class="d-label">Przyjęto:</span>
                <span class="d-val" id="detail-date-in">—</span>
                <span class="d-label" id="detail-date-planned-label">Termin:</span>
                <span class="d-val" id="detail-date-planned">—</span>
                <span class="d-label">Telefon:</span>
                <span class="d-val" id="detail-phone">—</span>
                <span class="d-label">Opis usterki:</span>
                <span class="d-val" id="detail-fault">—</span>
                <span class="d-label" id="detail-done-label">Wykonane czynności:</span>
                <span class="d-val" id="detail-done-list">—</span>
                <span class="d-label" id="detail-notes-label">Notatki:</span>
                <textarea class="d-val detail-notes-input" id="detail-notes" rows="3"
                    placeholder="Wpisz swoje uwagi do zgłoszenia..."></textarea>
            </div>
            <div class="pending-detail" id="detail-pending" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span id="detail-pending-text"></span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-primary" id="detail-issue-btn">Wydaj rower</button>
                <button type="button" class="btn btn-secondary" id="detail-cancel-btn">Zamknij</button>
            </div>
        </div>
    </div>

    <!-- MODAL ZDJĘĆ ZGŁOSZENIA -->
    <div class="modal-overlay" id="photos-modal">
        <div class="modal-card" style="max-width: 720px;">
            <div class="modal-header">
                <h3 style="font-size: 1.25rem;">Zdjęcia – <span id="photos-modal-title"></span></h3>
                <button class="modal-close" id="close-photos-btn">&times;</button>
            </div>
            <div class="form-group" id="photos-modal-input-group">
                <label>Dodaj zdjęcia do zgłoszenia</label>
                <div class="photo-sources">
                    <label class="photo-source-btn" for="photos-modal-input">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M12 18.75V21m0 0h12M21 12V8.25m0 0h-3.75m3.75 0V4.5m-3.75 3.75h3.75M14.25 7.5h.008v.008h-.008V7.5Z" />
                        </svg>
                        Wybierz z galerii
                    </label>
                    <label class="photo-source-btn" for="camera-modal-input">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175A2.021 2.021 0 0 0 2 9.418V17.1A2.1 2.1 0 0 0 4.1 19.2h15.8A2.1 2.1 0 0 0 22 17.1V9.418c0-1.092-.837-2.013-2.052-2.013a26.815 26.815 0 0 1-1.134-.175 2.31 2.31 0 0 1-1.641-1.055L16.42 5.32A2.3 2.3 0 0 0 14.302 4h-4.6a2.3 2.3 0 0 0-2.118 1.32L6.827 6.175Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                        </svg>
                        Zrób zdjęcie aparatem
                    </label>
                </div>
                <input type="file" id="photos-modal-input" accept="image/*" multiple class="file-input-hidden">
                <input type="file" id="camera-modal-input" accept="image/*" capture="environment" multiple class="file-input-hidden">
            </div>
            <div class="photos-grid" id="photos-grid"></div>
        </div>
    </div>

    <!-- LIGHTBOX PODGLĄDU ZDJĘCIA -->
    <div class="lightbox" id="lightbox" title="Kliknij, aby zamknąć">
        <img id="lightbox-img" alt="Podgląd zdjęcia">
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container" id="toast-container"></div>

    <!-- UPLOAD OVERLAY: Wgrywanie zdjęcia -->
    <div class="upload-overlay" id="upload-overlay">
        <svg class="upload-cyclist" viewBox="0 0 140 90" fill="none" stroke="currentColor"
             stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <!-- Linie szybkosci -->
            <g class="speed-lines" stroke-width="3">
                <line x1="1" y1="46" x2="15" y2="46" />
                <line x1="6" y1="59" x2="14" y2="59" />
                <line x1="0" y1="73" x2="12" y2="73" />
            </g>

            <!-- Kolo tylne -->
            <g class="wheel">
                <circle cx="34" cy="62" r="19" />
                <line x1="34" y1="43" x2="34" y2="81" />
                <line x1="15" y1="62" x2="53" y2="62" />
                <line x1="21" y1="49" x2="47" y2="75" />
                <line x1="47" y1="49" x2="21" y2="75" />
            </g>

            <!-- Kolo przednie -->
            <g class="wheel">
                <circle cx="108" cy="62" r="19" />
                <line x1="108" y1="43" x2="108" y2="81" />
                <line x1="89" y1="62" x2="127" y2="62" />
                <line x1="95" y1="49" x2="121" y2="75" />
                <line x1="121" y1="49" x2="95" y2="75" />
            </g>

            <!-- Rama roweru -->
            <path d="M34 62 L70 62 L55 40 L96 40 L108 62 M70 62 L96 40" />
            <path d="M96 40 L101 33" />
            <path d="M55 40 L55 37.5" />
            <path d="M50 37 L61 37" />

            <!-- Kierowca -->
            <g class="rider" stroke-width="5">
                <!-- korpus: biodro na siodle -> ramie -->
                <path d="M57 37 L74 24" />
                <!-- szyja -->
                <path d="M74 24 L77.5 21" />
                <!-- glowa -->
                <circle cx="81" cy="17" r="6.5" fill="currentColor" stroke="none" />
                <!-- kask -->
                <path stroke-width="4" d="M75.5 13.5 A7 7 0 0 1 86.5 13.5" />
                <path stroke-width="4" d="M86.5 13.5 L89.5 15" />
                <!-- ramie z lokciem do kierownicy -->
                <path d="M74 25 L86 31 L99 34.5" />
                <!-- nogi: pedaly wokol korby -->
                <path d="M57 37 L70 50 L73 60" />
                <path d="M57 37 L65 53 L67 64" />
            </g>
        </svg>
        <span class="upload-label">Zapisywanie</span>
    </div>

    <!-- ==========================================================================
       PRINT RECEIPT HTML TEMPLATE (A4 poziomo, jedna strona, 2 kolumny)
       ========================================================================== -->
    <div id="print-receipt">
        <!-- LEWA KOLUMNA: egzemplarz dla klienta -->
        <div class="receipt-column receipt-column-client">
            <div class="receipt-header">
                <h1>RoweryExpert</h1>
            </div>

            <div class="receipt-title" id="print-title-client">Potwierdzenie Przyjęcia Roweru</div>

            <table class="receipt-details">
                <tr class="receipt-row">
                    <td class="receipt-label">Rower:</td>
                    <td class="receipt-value" id="print-bike-name" style="font-weight:bold;">Kross Hexagon</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Data przyjęcia:</td>
                    <td class="receipt-value" id="print-date-in">14.07.2026</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Telefon klienta:</td>
                    <td class="receipt-value" id="print-customer-phone">532-561-152</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Numer serwisowy:</td>
                    <td class="receipt-value" id="print-service-no" style="font-weight:bold;">RO-2026-0001</td>
                </tr>
                <tr>
                    <td colspan="2" class="receipt-label" style="padding-top: 8px; padding-bottom: 4px;">Opis usterki / zakres naprawy:</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="receipt-value-desc" id="print-fault-description">Wymiana napędu, regulacja przerzutek.</div>
                    </td>
                </tr>
                <!-- Wykonane czynności: zaznaczone checkboxy; sekcja znika, gdy nic nie zaznaczono -->
                <tr id="print-done-row-client">
                    <td colspan="2" class="receipt-label" style="padding-top: 8px; padding-bottom: 4px;">Wykonane czynności:</td>
                </tr>
                <tr id="print-done-cell-client">
                    <td colspan="2">
                        <div class="receipt-value-desc" id="print-done-client"></div>
                    </td>
                </tr>
            </table>

            <!-- Signature and stamp section -->
            <div class="receipt-signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Podpis klienta</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Podpis i pieczątka serwisu</div>
                </div>
            </div>

            <div class="receipt-footer">
                <div class="receipt-address">
                    RoweryExpert Ostrobramska 81
                    04-175 Warszawa tel. 532-561-152
                </div>

                <div class="receipt-qr-container">
                    <!-- Canvas generujący kod QR mapy Google (lokalnie przez QRious) -->
                    <canvas id="qr-code-canvas" class="receipt-qr-element"></canvas>
                    <!-- Zapasowy obrazek kodu QR (generowany przez darmowe API online) na wypadek gdyby skrypt z cdn został zablokowany -->
                    <img id="qr-code-img" class="receipt-qr-element" style="display: none;" alt="QR Code">
                    <div class="receipt-qr-desc">Oceń nas w Google</div>
                </div>
            </div>
        </div>

        <!-- PRAWA KOLUMNA: egzemplarz dla serwisu -->
        <div class="receipt-column receipt-column-service">
            <div class="receipt-header">
                <h1>RoweryExpert</h1>
            </div>

            <div class="receipt-title" id="print-title-service">Zlecenie Serwisowe - Egzemplarz Serwisu</div>

            <table class="receipt-details">
                <tr class="receipt-row">
                    <td class="receipt-label">Rower:</td>
                    <td class="receipt-value" id="print-bike-name-service" style="font-weight:bold;">Kross Hexagon</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Data przyjęcia:</td>
                    <td class="receipt-value" id="print-date-in-service">14.07.2026</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Telefon klienta:</td>
                    <td class="receipt-value" id="print-customer-phone-service">532-561-152</td>
                </tr>
                <tr class="receipt-row">
                    <td class="receipt-label">Numer serwisowy:</td>
                    <td class="receipt-value" id="print-service-no-service" style="font-weight:bold;">RO-2026-0001</td>
                </tr>
                <tr>
                    <td colspan="2" class="receipt-label" style="padding-top: 8px; padding-bottom: 4px;">Opis usterki / zakres naprawy:</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="receipt-value-desc" id="print-fault-description-service">Wymiana napędu, regulacja przerzutek.</div>
                    </td>
                </tr>
            </table>

            <!-- Wykonane czynności: zaznaczone checkboxy (puste = sekcja znika) -->
            <div class="service-notes-title" id="print-done-title-service" style="margin-top: 0;">Wykonane czynności:</div>
            <div class="receipt-value-desc" id="print-done-service" style="margin-top: 0; margin-bottom: 0;"></div>

            <!-- Notatki: na wydruku pojawiają się tylko po uzupełnieniu -->
            <div id="print-notes-block" style="flex: 1 1 auto; flex-direction: column;">
                <div class="service-notes-title">Notatki:</div>
                <div class="receipt-value-desc" id="print-service-notes" style="flex: 1 1 auto; min-height: 60px; margin-top: 0; margin-bottom: 0;">—</div>
            </div>

            <!-- Wypełniacz wysokości, gdy notatek nie ma — QR trzyma się dołu kolumny -->
            <div id="print-notes-spacer" style="flex: 1 1 auto;"></div>

            <!-- Etykieta QR z numerem serwisowym — sam dół -->
            <div style="display: flex; justify-content: flex-start; margin-top: 6px;">
                <canvas id="qr-service-canvas" class="receipt-qr-element" style="margin-bottom: 0;"></canvas>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
       LOGIKA JAVASCRIPT
       ========================================================================== -->
    <script>
        // --- INICJALIZACJA STANU ---
        const IS_AUTHENTICATED = <?= $authenticated ? 'true' : 'false' ?>;
        let db = []; // Zgłoszenia ładowane z API/PHP i bazy MySQL
        let currentFilter = 'all';
        let currentSort = 'planned_asc';   // domyślnie: najbliższy termin odbioru na górze
        let searchQuery = '';

        // --- ENDPOINTY API (PHP + MySQL) ---
        const API_ZGLOSZENIA = 'api/zgloszenia.php';
        const API_ZDJECIA = 'api/zdjecia.php';
        const API_KONTO = 'api/konto.php';
        const API_USLUGI = 'api/uslugi.php';
        const API_USTAWIENIA = 'api/ustawienia.php';

        // Wrapper fetch z obsługą wygasłej autoryzacji (401)
        async function apiFetch(url, options) {
            const res = await window.fetch(url, options);
            if (res.status === 401) {
                showToast('Brak dostępu - zaloguj się ponownie.', 'error');
                setTimeout(() => window.location.reload(), 1200);
                throw new Error('Unauthorized');
            }
            return res;
        }

        // --- MODUŁY PANELU (Ustawienia -> Moduły) ---
        // Użytkownik może wyłączyć nieużywane opcje; flagi żyją w bazie,
        // więc PC i telefon widzą to samo. Domyślnie wszystko włączone.
        let MODULY = { kalendarz: true, zdjecia: true, skaner: true, uslugi: true, druk: true, kosz: true, kolorystyka: true, powitanie: true, karta_wydania: true, wykonane: true };

        function modulOn(nazwa) {
            return MODULY[nazwa] !== false;
        }

        async function loadModules() {
            try {
                const res = await apiFetch(API_USTAWIENIA);
                const data = await res.json();
                if (data.success && data.data && data.data.moduly) {
                    MODULY = data.data.moduly;
                }
            } catch (err) {
                // Brak API: zostają flagi domyślne (wszystko włączone)
            }
        }

        // Zapis zmienionych modułów do bazy
        async function saveModules() {
            const formData = new FormData();
            formData.append('action', 'modules');
            formData.append('moduly', JSON.stringify(MODULY));
            const res = await apiFetch(API_USTAWIENIA, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Nie udało się zapisać modułów.');
            if (data.data && data.data.moduly) MODULY = data.data.moduly;
        }

        // Etykiety przycisków zapisu zależą od modułów druku i kalendarza
        function updateSaveButtons() {
            const mainBtn = document.getElementById('save-print-calendar-btn');
            const onlyBtn = document.getElementById('save-only-btn');
            const btnLabel = document.getElementById('save-btn-label');
            if (!mainBtn || !onlyBtn || !btnLabel) return;
            const druk = modulOn('druk');
            const kalendarz = modulOn('kalendarz');

            if (IS_MOBILE) {
                mainBtn.hidden = true;
                onlyBtn.hidden = false;
            } else {
                mainBtn.hidden = false;
                onlyBtn.hidden = true;
                if (druk && kalendarz) btnLabel.textContent = 'Zapisz, Drukuj i Dodaj do Kalendarza';
                else if (druk) btnLabel.textContent = 'Zapisz i drukuj';
                else if (kalendarz) btnLabel.textContent = 'Zapisz i dodaj do kalendarza';
                else btnLabel.textContent = 'Zapisz';
            }
        }

        // Zastosowanie flag modułów: klasy na <body> (CSS ukrywa elementy
        // statyczne), przełączniki required przy datach, reset niewidocznych
        // filtrów/sortowań oraz etykiety przycisków.
        function applyModules() {
            Object.keys(MODULY).forEach(function (nazwa) {
                document.body.classList.toggle('off-' + nazwa, !modulOn(nazwa));
            });

            // Kolorystyka: przy wyłączonym module wymuszony domyślny żółty
            // (bez zapisu — wybrany kolor zostaje w localStorage i wraca
            // po ponownym włączeniu modułu)
            setAccent(modulOn('kolorystyka') ? (localStorage.getItem('accent') || 'zolty') : 'zolty',
                      modulOn('kolorystyka'));

            const kalendarz = modulOn('kalendarz');

            // (datePlannedInput istnieje zawsze - applyModules wywoływane
            // dopiero po wczytaniu skryptu, w DOMContentLoaded lub z Ustawień)
            datePlannedInput.required = kalendarz;
            if (!kalendarz) datePlannedInput.value = '';
            const editPlanned = document.getElementById('edit-date-planned');
            if (editPlanned) editPlanned.required = kalendarz;

            // Filtry, które zniknęły, resetujemy do bezpiecznego stanu
            if (currentFilter === 'trash' && !modulOn('kosz')) currentFilter = 'all';
            if (!kalendarz && ['today', 'tomorrow', 'overdue'].includes(currentFilter)) currentFilter = 'all';

            // Opcje sortowania po terminie tylko z włączonym kalendarzem
            const sortEl = document.getElementById('sort-select');
            if (sortEl) {
                sortEl.querySelectorAll('option').forEach(opt => {
                    if (opt.value.indexOf('planned') === 0) opt.hidden = !kalendarz;
                });
                if (currentSort.indexOf('planned') === 0 && !kalendarz) {
                    currentSort = 'dateIn_desc';
                    sortEl.value = 'dateIn_desc';
                    try { localStorage.setItem('re_sort', currentSort); } catch (e) { /* ignore */ }
                }
            }

            applyDeviceLayout();
            updateSaveButtons();

            // Stan aktywnych filtrów po resecie
            document.querySelectorAll('.filter-btn').forEach(btn =>
                btn.classList.toggle('active', btn.dataset.filter === currentFilter));
            document.querySelectorAll('.dash-tile').forEach(tile =>
                tile.classList.toggle('active', tile.dataset.filter === currentFilter));
        }

        // Nakładka "Zapisywanie" + blokada przycisków (zapobiega podwójnemu kliknięciu)
        // Zamknięcie nie znika od razu: rowerzysta odjeżdża za krawędź ekranu,
        // a okno chowa się na końcu sekwencji.
        let uploadHideTimer = null;

        function showUploading(show) {
            const overlay = document.getElementById('upload-overlay');
            const btnSave = document.getElementById('save-print-calendar-btn');
            const btnOnly = document.getElementById('save-only-btn');
            if (show) {
                clearTimeout(uploadHideTimer);
                overlay.classList.remove('leaving');
                overlay.classList.add('active');
                if (btnSave) btnSave.disabled = true;
                if (btnOnly) btnOnly.disabled = true;
            } else {
                if (btnSave) btnSave.disabled = false;
                if (btnOnly) btnOnly.disabled = false;
                if (!overlay.classList.contains('active') || overlay.classList.contains('leaving')) {
                    return;
                }
                overlay.classList.add('leaving');
                uploadHideTimer = setTimeout(() => {
                    overlay.classList.remove('active', 'leaving');
                }, 1050);
            }
        }

        // --- WYKRYWANIE URZĄDZENIA (mobile / desktop) ---
        function detectMobile() {
            const ua = (navigator.userAgent || '').toLowerCase();
            const mobileUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i.test(ua);
            const touch = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
            return mobileUA || (touch && window.innerWidth <= 1024);
        }

        const IS_MOBILE = detectMobile();

        function applyDeviceLayout() {
            // Klasa na <body> — reszta ukrycia listy/kafli/filtrów idzie przez CSS
            document.body.classList.toggle('is-mobile', IS_MOBILE);
            // Mobile: ukryj przycisk "Zapisz, Drukuj i Dodaj do Kalendarza"
            const mainBtn = document.getElementById('save-print-calendar-btn');
            if (mainBtn) mainBtn.hidden = IS_MOBILE;
            // Desktop: ukryj przycisk "Zapisz tylko w bazie"
            const saveOnlyBtn = document.getElementById('save-only-btn');
            if (saveOnlyBtn) saveOnlyBtn.hidden = !IS_MOBILE;
            // Desktop: ukryj wgrywanie zdjęć (formularz + modal)
            const photoUploadGroup = document.getElementById('photo-upload-group');
            const modalUploadGroup = document.getElementById('photos-modal-input-group');
            if (photoUploadGroup) photoUploadGroup.hidden = !IS_MOBILE;
            if (modalUploadGroup) modalUploadGroup.hidden = !IS_MOBILE;
        }

        applyDeviceLayout();

        // Domyślne ustawienia Google API: zawsze szybki link (wyłącznie), bez konfiguracji

        // --- REFERENCJE DOM ---
        const themeToggleBtn = document.getElementById('theme-toggle-btn');
        const themeIconSun = document.getElementById('theme-icon-sun');
        const themeIconMoon = document.getElementById('theme-icon-moon');
        
        const serviceForm = document.getElementById('service-form');
        const bikeNameInput = document.getElementById('bike-name');
        const dateInInput = document.getElementById('date-in');
        const datePlannedInput = document.getElementById('date-planned');
        const customerPhoneInput = document.getElementById('customer-phone');
        const faultDescriptionInput = document.getElementById('fault-description');
        const photosInput = document.getElementById('photos-input');
        const cameraInput = document.getElementById('camera-input');

        const searchInput = document.getElementById('search-input');
        const servicesListContainer = document.getElementById('services-list-container');
        const filterButtons = document.querySelectorAll('.filters .filter-btn');
        
        const openSettingsBtn = document.getElementById('open-settings-btn');
        const closeSettingsBtn = document.getElementById('close-settings-btn');
        const settingsModal = document.getElementById('settings-modal');
        const statsPhotosEl = document.getElementById('stats-photos');
        const statsSizeEl = document.getElementById('stats-size');
        const statsWarnEl = document.getElementById('stats-warn');
        const refreshStatsBtn = document.getElementById('refresh-stats-btn');
        const currentPasswordInput = document.getElementById('current-password');
        const newPasswordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const savePasswordBtn = document.getElementById('save-password-btn');
        const passwordHintEl = document.getElementById('password-hint');

        const tabGeneralBtn = document.getElementById('tab-btn-general');
        const tabUslugiBtn = document.getElementById('tab-btn-uslugi');
        const tabModulyBtn = document.getElementById('tab-btn-moduly');
        const tabGeneralContent = document.getElementById('tab-content-general');
        const tabUslugiContent = document.getElementById('tab-content-uslugi');
        const tabModulyContent = document.getElementById('tab-content-moduly');
        const newServiceInput = document.getElementById('new-service-input');
        const addServiceBtn = document.getElementById('add-service-btn');
        const serviceListEl = document.getElementById('service-list');
        const serviceCheckboxList = document.getElementById('service-checkbox-list');
        
        const photosModal = document.getElementById('photos-modal');
        const photosModalTitle = document.getElementById('photos-modal-title');
        const closePhotosBtn = document.getElementById('close-photos-btn');
        const photosModalInput = document.getElementById('photos-modal-input');
        const cameraModalInput = document.getElementById('camera-modal-input');
        const photosGrid = document.getElementById('photos-grid');
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');

        const toastContainer = document.getElementById('toast-container');

        // --- MODAL POTWIERDZENIA (zamiast natywnego confirm()) ---
        const confirmModal = document.getElementById('confirm-modal');
        const confirmModalTitle = document.getElementById('confirm-modal-title');
        const confirmModalMessage = document.getElementById('confirm-modal-message');
        const confirmModalOk = document.getElementById('confirm-modal-ok');
        const confirmModalCancel = document.getElementById('confirm-modal-cancel');
        const confirmModalClose = document.getElementById('confirm-modal-close');
        let confirmModalResolve = null;

        function showConfirmModal(title, message, okLabel = 'Usuń') {
            confirmModalTitle.textContent = title;
            confirmModalMessage.textContent = message;
            confirmModalOk.textContent = okLabel;
            confirmModal.classList.add('active');
            return new Promise(resolve => { confirmModalResolve = resolve; });
        }

        function closeConfirmModal(result) {
            confirmModal.classList.remove('active');
            if (confirmModalResolve) {
                const resolve = confirmModalResolve;
                confirmModalResolve = null;
                resolve(result);
            }
        }

        confirmModalOk.addEventListener('click', () => closeConfirmModal(true));
        confirmModalCancel.addEventListener('click', () => closeConfirmModal(false));
        confirmModalClose.addEventListener('click', () => closeConfirmModal(false));
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) closeConfirmModal(false);
        });

        // --- INICJALIZACJA STRONY ---
        window.addEventListener('DOMContentLoaded', async () => {
            // Motyw: domyślnie ciemny od pierwszego renderu (także ekran
            // logowania); zapisany wybór w localStorage ma pierwszeństwo.
            const savedTheme = localStorage.getItem('theme') || 'dark-theme';
            setTheme(savedTheme);

            if (!IS_AUTHENTICATED) return; // brak sesji - pokazano ekran logowania

            // Moduły panelu: pobierz flagi z bazy i zastosuj (Ustawienia -> Moduły)
            await loadModules();
            applyModules();

            // Automatyczne ustawienie dat w formularzu
            const now = new Date();
            const planned = new Date();
            planned.setDate(now.getDate() + 3); // Domyślnie termin za 3 dni
            
            dateInInput.value = formatDateForInput(now);
            datePlannedInput.value = modulOn('kalendarz') ? formatDateForInput(planned) : '';

            // Załaduj katalog usług (checkboxy w formularzu) - tylko gdy włączony
            if (modulOn('uslugi')) loadServices();

            // Załaduj zgłoszenia z bazy MySQL przez API
            try {
                const res = await apiFetch(API_ZGLOSZENIA);
                const data = await res.json();
                if (data.success) {
                    db = data.data;
                } else {
                    showToast(data.error || 'Nie udało się wczytać zgłoszeń.', 'error');
                }
            } catch (err) {
                showToast('Nie można połączyć się z serwerem przez API.', 'error');
            }

            renderServicesList();

            // Powitanie po zalogowaniu (?powitanie=1): podsumowanie dnia.
            // Parametr czyścimy z adresu, żeby odświeżenie (F5) nie pokazywało
            // okna ponownie.
            if (document.body.dataset.powitanie) {
                history.replaceState(null, '', location.pathname);
                // Moduł Powitanie decyduje o oknie; Kalendarz pozostaje
                // wymagany (bez terminów nie ma czego podsumowywać)
                if (modulOn('powitanie') && modulOn('kalendarz')) pokazPowitanie();
            }
        });

        // --- MOTYW (DARK / LIGHT) ---
        themeToggleBtn.addEventListener('click', () => {
            if (document.body.classList.contains('light-theme')) {
                setTheme('dark-theme');
            } else {
                setTheme('light-theme');
            }
        });

        function setTheme(theme) {
            if (theme === 'light-theme') {
                document.body.classList.add('light-theme');
                themeIconSun.style.display = 'none';
                themeIconMoon.style.display = 'block';
            } else {
                document.body.classList.remove('light-theme');
                themeIconSun.style.display = 'block';
                themeIconMoon.style.display = 'none';
            }
            localStorage.setItem('theme', theme);
        }

        // --- KOLOR AKCENTU (paleta w nagłówku) ---
        // Domyślnie żółty Media Expert; wybór zapisywany w localStorage
        // (jak motyw - ustawienie per urządzenie).
        const ACCENT_KLASY = ['accent-zielony', 'accent-czerwony', 'accent-niebieski', 'accent-pomaranczowy'];
        // Warianty logo i faviconki przekolorowane na kolor akcentu
        // (żółty #FFDD00 z oryginałów → kolor akcentu, generowane z logo.png/favicon.png)
        const ACCENT_PLIKI = {
            zolty:        { logo: 'logo.png',              fav: 'favicon.png' },
            zielony:      { logo: 'logo-zielony.png',      fav: 'favicon-zielony.png' },
            czerwony:     { logo: 'logo-czerwony.png',     fav: 'favicon-czerwony.png' },
            niebieski:    { logo: 'logo-niebieski.png',    fav: 'favicon-niebieski.png' },
            pomaranczowy: { logo: 'logo-pomaranczowy.png', fav: 'favicon-pomaranczowy.png' }
        };
        const paletteBtn = document.getElementById('palette-btn');
        const accentPicker = document.getElementById('accent-picker');

        function setAccent(accent, zapisz) {
            const a = accent || 'zolty';
            ACCENT_KLASY.forEach(k => document.body.classList.remove(k));
            if (a !== 'zolty') document.body.classList.add('accent-' + a);
            document.querySelectorAll('.accent-swatch').forEach(sw =>
                sw.classList.toggle('active', sw.dataset.accent === a));
            // logo w nagłówku i na loginie + faviconka zmieniają kolor z akcentem
            const plik = ACCENT_PLIKI[a] || ACCENT_PLIKI.zolty;
            document.querySelectorAll('.logo-img').forEach(img => {
                if (!img.src.endsWith(plik.logo)) img.src = plik.logo;
            });
            const fav = document.querySelector('link[rel="icon"]');
            if (fav && !fav.href.endsWith(plik.fav)) fav.href = plik.fav;
            // Zapis tylko przy świadomej zmianie — wymuszenie koloru
            // przy wyłączonym module kolorystyki nie kasuje wyboru
            if (zapisz !== false) localStorage.setItem('accent', a);
        }

        // Wybór zapisany przy starcie (przed pierwszym odrysowaniem)
        setAccent(localStorage.getItem('accent') || 'zolty');

        paletteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            accentPicker.classList.toggle('open');
        });

        document.querySelectorAll('.accent-swatch').forEach(sw =>
            sw.addEventListener('click', () => setAccent(sw.dataset.accent)));

        // Zamknięcie palety po kliknięciu poza nią
        document.addEventListener('click', (e) => {
            if (!accentPicker.contains(e.target) && !paletteBtn.contains(e.target)) {
                accentPicker.classList.remove('open');
            }
        });

        // Helper: formatowanie daty do elementu input type="date"
        function formatDateForInput(date) {
            const pad = (n) => n.toString().padStart(2, '0');
            const yyyy = date.getFullYear();
            const mm = pad(date.getMonth() + 1);
            const dd = pad(date.getDate());
            return `${yyyy}-${mm}-${dd}`;
        }

        // Helper: czytelne formatowanie daty dla użytkownika (PL) - bez godzin
        function formatDateForUser(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const pad = (n) => n.toString().padStart(2, '0');
            return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
        }

        // --- PHONE FORMAT & VALIDATION ---
        function formatPhone(value) {
            // Remove everything except digits
            const digits = value.replace(/\D/g, '');
            // Max 9 digits
            const limited = digits.slice(0, 9);
            // Insert dash every 3 digits: 532 561 152 → 532-561-152
            const parts = [];
            for (let i = 0; i < limited.length; i += 3) {
                parts.push(limited.slice(i, i + 3));
            }
            return parts.join('-');
        }

        function validatePhone(phone) {
            const digits = phone.replace(/\D/g, '');
            return digits.length >= 9;
        }

        // --- TOAST NOTIFICATIONS ---
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            let icon = '';
            if (type === 'success') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--success);"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>`;
            } else if (type === 'info') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--calendar-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>`;
            } else if (type === 'error') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--danger);"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Zm3 0v.008h.008V6.75H6.75Zm7.5 0v.008h.008V6.75h-.984Zm-3.75 0v.008h.008V6.75h-.984Z" /></svg>`;
            }
            
            toast.innerHTML = `${icon}<span>${message}</span>`;
            // Jeden toast na raz - skumulowane toasty zaslaniały ekran telefonu
            toastContainer.querySelectorAll('.toast').forEach(t => t.remove());
            toastContainer.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // --- BEZPIECZNE WPROWADZANIE TEKSTU DO HTML ---
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // --- ZAPISYWANIE DANYCH W BAZIE MYSQL (przez API) ---
        async function saveItemToDB(item, files) {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('bike_name', item.bikeName);
            formData.append('date_in', item.dateIn);
            formData.append('date_planned', item.datePlanned);
            formData.append('customer_phone', item.customerPhone);
            formData.append('fault_description', item.faultDescription);
            formData.append('status', item.status);
            formData.append('source', IS_MOBILE ? 'mobile' : 'desktop');
            // services_done pomijamy: przyjęcie to zakres prac, nie stan wykonania

            if (files && files.length) {
                for (const file of files) {
                    formData.append('photos[]', file);
                }
            }

            const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) {
                throw new Error(data.error || 'Nie udało się zapisać zgłoszenia.');
            }
            db.unshift(data.data);
            renderServicesList();
            if (files && files.length) {
                // Po wgrywaniu zdjęć na przyjęciu: komunikat o progu80% limitu
                const st = await photoStatsNow();
                const warn = st ? fotoWarnText(st.bytes) : null;
                if (warn) showToast('⚠ ' + warn, 'error');
            }
            return data.data;
        }

        // Tworzenie obiektu danych roweru z formularza
        function getFormData() {
            const manual = faultDescriptionInput.value.trim();
            const checked = Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => cb.value);
            let description = manual;
            if (checked.length) {
                const servicesBlock = checked.map(s => '- ' + s).join('\n');
                description = manual ? manual + '\n\n' + servicesBlock : servicesBlock;
            }

            return {
                bikeName: bikeNameInput.value.trim(),
                dateIn: dateInInput.value,
                datePlanned: datePlannedInput.value,
                customerPhone: customerPhoneInput.value.trim(),
                faultDescription: description,
                status: 'in_progress', // Domyślny status: 'in_progress', 'completed', 'picked_up'
                // Na przyjęciu nic jeszcze nie wykonano — zaznaczone usługi
                // jadą do opisu (linie „- ”), checkboxy z karty je wypełnią
                servicesDone: []
            };
        }

        // Czyszczenie formularza
        function resetForm() {
            bikeNameInput.value = '';
            faultDescriptionInput.value = '';
            customerPhoneInput.value = '';
            photosInput.value = '';
            if (cameraInput) cameraInput.value = '';
            document.querySelectorAll('.service-checkbox:checked').forEach(cb => cb.checked = false);

            const now = new Date();
            const planned = new Date();
            planned.setDate(now.getDate() + 2);
            dateInInput.value = formatDateForInput(now);
            datePlannedInput.value = modulOn('kalendarz') ? formatDateForInput(planned) : '';
        }

        // --- INTEGRACJA GOOGLE CALENDAR ---
        // Generowanie linku szybkiego dodawania wydarzenia w Kalendarzu Google
        function generateGoogleCalendarLink(item) {
            const baseUrl = 'https://calendar.google.com/calendar/render';
            const action = 'TEMPLATE';
            
            const title = `🔧 Serwis: ${item.bikeName}`;
            const details = `KLIENT: ${item.customerPhone}\n\nDATA PRZYJĘCIA: ${formatDateForUser(item.dateIn)}\n\nOPIS USTERKI: ${item.faultDescription}`;
            
            const dateStartUTC = convertToUTCFormat(item.dateIn);
            // Zgodnie ze specyfikacją całodniową koniec to dzień następny
            const nextDay = new Date(item.datePlanned);
            nextDay.setDate(nextDay.getDate() + 1);
            const dateEndUTC = convertToUTCFormat(formatDateForInput(nextDay));
            const dates = `${dateStartUTC}/${dateEndUTC}`;
            
            // "remind" ustawiony na pusto nadpisuje domyślne reguły powiadomień konta Google i wyłącza przypomnienia
            const params = new URLSearchParams({
                action: action,
                text: title,
                details: details,
                dates: dates,
                sf: 'true',
                output: 'xml',
                remind: ''
            });

            return `${baseUrl}?${params.toString()}`;
        }

        // Konwersja daty (np. "2026-07-14") na format tekstowy dla Google Calendar całodniowego ("YYYYMMDD")
        function convertToUTCFormat(localDateStr) {
            if (!localDateStr) return '';
            return localDateStr.replace(/-/g, '');
        }

        function openGoogleCalendar(item) {
            const link = generateGoogleCalendarLink(item);
            window.open(link, '_blank');
            showToast('Otwarto okno dodawania do Kalendarza Google!', 'info');
        }

        // --- WSPÓLNA AKCJA: DRUKUJ + DODAJ DO KALENDARZA ---
        // Kolejność działań zależy od modułów: gdy któreś wyłączone,
        // pomijamy tylko tę część.
        function printAndAddToCalendar(item) {
            if (modulOn('kalendarz')) openGoogleCalendar(item);
            if (modulOn('druk')) triggerPrint(item);
        }

        // --- MECHANIZM DRUKOWANIA POTWIERDZENIA ---
        // tryb: 'przyjecie' (domyślnie) — potwierdzenie przyjęcia roweru,
        //       'wydanie'  — karta wydania roweru (drukowana przy wydaniu)
        function triggerPrint(item, tryb) {
            const isIssue = tryb === 'wydanie';

            // Tytuł zależy od okazji
            document.getElementById('print-title-client').textContent =
                isIssue ? 'Karta Wydania Roweru' : 'Potwierdzenie Przyjęcia Roweru';
            document.getElementById('print-title-service').textContent =
                isIssue ? 'Karta Wydania Roweru - Egzemplarz Serwisu'
                        : 'Zlecenie Serwisowe - Egzemplarz Serwisu';

            // Wypełnij template wydruku A4 (dane roweru, telefonu, opis usterki)
            document.getElementById('print-bike-name').textContent = item.bikeName;
            document.getElementById('print-date-in').textContent = formatDateForUser(item.dateIn);
            document.getElementById('print-customer-phone').textContent = item.customerPhone;
            document.getElementById('print-fault-description').textContent = item.faultDescription;
            document.getElementById('print-service-no').textContent = item.serviceNo || '—';

            // Wypełnij template strony 2 (egzemplarz dla serwisu)
            document.getElementById('print-bike-name-service').textContent = item.bikeName;
            document.getElementById('print-date-in-service').textContent = formatDateForUser(item.dateIn);
            document.getElementById('print-customer-phone-service').textContent = item.customerPhone;
            document.getElementById('print-fault-description-service').textContent = item.faultDescription;
            document.getElementById('print-service-no-service').textContent = item.serviceNo || '—';

            // Wykonane czynności: na karcie wydania lista zaznaczonych checkboxów (☑),
            // gdy nic nie zaznaczono lub moduł wyłączony — sekcja się nie pojawia
            const doneList = (modulOn('wykonane') && isIssue) ? getDoneServices(item) : [];
            const doneHtml = doneList.map(n => '☑ ' + escapeHtml(n)).join('<br>');
            const showDone = doneList.length > 0;
            document.getElementById('print-done-row-client').hidden = !showDone;
            document.getElementById('print-done-cell-client').hidden = !showDone;
            document.getElementById('print-done-client').innerHTML = showDone ? doneHtml : '';
            document.getElementById('print-done-title-service').hidden = !showDone;
            document.getElementById('print-done-service').hidden = !showDone;
            document.getElementById('print-done-service').innerHTML = showDone ? doneHtml : '';

            // Notatki: na wydruku tylko po uzupełnieniu (puste pole znika)
            const notes = (item.serviceNotes || '').trim();
            document.getElementById('print-service-notes').textContent = notes || '—';
            // display zamiast hidden: block ma inline flex, przez który [hidden] nie zadziała
            document.getElementById('print-notes-block').style.display = notes ? 'flex' : 'none';
            document.getElementById('print-notes-spacer').hidden = !!notes;

            // Elementy kodu QR
            const qrCanvas = document.getElementById('qr-code-canvas');
            const qrImg = document.getElementById('qr-code-img');
            const qrServiceCanvas = document.getElementById('qr-service-canvas');

            // Wywołanie systemowego okna druku
            const executePrint = () => {
                setTimeout(() => {
                    window.print();
                }, 150);
            };

            try {
                // Bezpieczne sprawdzanie czy biblioteka QRious została załadowana z CDN
                if (typeof QRious !== 'undefined') {
                    qrCanvas.style.display = 'inline-block';
                    qrImg.style.display = 'none';
                    
                    const ctx = qrCanvas.getContext('2d');
                    ctx.clearRect(0, 0, qrCanvas.width, qrCanvas.height);
                    
                    new QRious({
                        element: qrCanvas,
                        value: 'https://maps.app.goo.gl/samSLejTdYzsQAEg8',
                        size: 150,
                        level: 'H',
                        foreground: '#000000',
                        background: '#ffffff'
                    });

                    // Etykieta QR z numerem serwisowym do naklejenia na rower
                    if (item.serviceNo) {
                        qrServiceCanvas.style.display = 'inline-block';
                        new QRious({
                            element: qrServiceCanvas,
                            value: item.serviceNo,
                            size: 150,
                            level: 'H',
                            foreground: '#000000',
                            background: '#ffffff'
                        });
                    } else {
                        qrServiceCanvas.style.display = 'none';
                    }
                    
                    executePrint(); // Rysowanie na canvasie jest natychmiastowe - drukujemy od razu
                } else {
                    throw new Error('QRious library is missing.');
                }
            } catch (err) {
                // W przypadku problemów sieciowych, pobieramy kod QR przez API online
                console.warn('Błąd generowania QR lokalnie, korzystam z API online:', err);
                qrCanvas.style.display = 'none';
                qrImg.style.display = 'inline-block';
                qrServiceCanvas.style.display = 'none';
                
                // Czekamy na pobranie kodu QR przed wywołaniem druku
                qrImg.onload = () => {
                    executePrint();
                };
                qrImg.onerror = () => {
                    console.error('Nie można pobrać kodu QR z zewnętrznego API.');
                    executePrint(); // Drukuj mimo braku obrazka QR
                };
                
                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fmaps.app.goo.gl%2FsamSLejTdYzsQAEg8';
            }
        }

        // Phone auto-format
        customerPhoneInput.addEventListener('input', (e) => {
            const cursorPos = e.target.selectionStart;
            const oldVal = e.target.value;
            const formatted = formatPhone(oldVal);
            e.target.value = formatted;
            // Restore cursor position roughly
            const diff = formatted.length - oldVal.length;
            e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
        });

        // Selected files for the new-report form (with preview)
        let selectedFiles = [];

        photosInput.addEventListener('change', () => {
            selectedFiles = selectedFiles.concat(Array.from(photosInput.files));
            photosInput.value = '';
            renderPhotoPreviews();
        });

        cameraInput.addEventListener('change', () => {
            selectedFiles = selectedFiles.concat(Array.from(cameraInput.files));
            cameraInput.value = '';
            renderPhotoPreviews();
        });

        function renderPhotoPreviews() {
            const container = document.getElementById('photo-previews');
            container.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'photo-preview';
                    div.innerHTML = `<img src="${e.target.result}" alt="Podgląd">
                        <button type="button" class="remove-photo" data-index="${index}" title="Usuń z wyboru">&times;</button>`;
                    const btn = div.querySelector('.remove-photo');
                    btn.addEventListener('click', () => {
                        selectedFiles.splice(index, 1);
                        photosInput.value = '';
                        const dt = new DataTransfer();
                        selectedFiles.forEach(f => dt.items.add(f));
                        photosInput.files = dt.files;
                        renderPhotoPreviews();
                    });
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        // --- MONIT PO ZAPISIE Z TELEFONU (pelny ekran) ---
        // Potwierdzenie zgloszenia (kalendarz + wydruk) robi sie na PC,
        // wiec telefon po zapisie pokazuje tylko informacje + OK.
        const phoneSuccessModal = document.getElementById('phone-success-modal');
        const phoneSuccessMsg = document.getElementById('phone-success-msg');

        function showPhoneSuccess(photoCount) {
            const zdjeciaTxt = photoCount > 0
                ? `Dodano ${photoCount} ${photoCount === 1 ? 'zdjęcie' : (photoCount < 5 ? 'zdjęcia' : 'zdjęć')}.<br><br>`
                : '';
            // Część po potwierdzeniu zależy od włączonych modułów
            const poPotwierdzeniu = [];
            if (modulOn('kalendarz')) poPotwierdzeniu.push('kalendarz');
            if (modulOn('druk')) poPotwierdzeniu.push('wydruk potwierdzenia dla klienta');
            const ogon = poPotwierdzeniu.length
                ? ` — tam uruchomi się też ${poPotwierdzeniu.join(' i ')}.`
                : '.';
            phoneSuccessMsg.innerHTML = zdjeciaTxt
                + 'Zgłoszenie jest zamazane na liście do czasu <strong>potwierdzenia na komputerze</strong>'
                + ogon;
            phoneSuccessModal.classList.add('active');
        }

        document.getElementById('phone-success-ok').addEventListener('click', () => {
            phoneSuccessModal.classList.remove('active');
        });

        // --- OBSŁUGA ZDARZEŃ FORMULARZA ---
        serviceForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateForm()) return;

            const item = getFormData();
            const photoCount = selectedFiles.length;
            showUploading(true);
            try {
                const saved = await saveItemToDB(item, selectedFiles);
                showUploading(false);
                resetForm();
                selectedFiles = [];
                renderPhotoPreviews();

                if (IS_MOBILE) {
                    // Telefon: pełnoekranowy monit zamiast toastu; kalendarz
                    // i wydruk uruchomi potwierdzenie na komputerze.
                    showPhoneSuccess(photoCount);
                } else {
                    showToast('Zapisano zgłoszenie rowerowe w bazie!');

                    // Zapisz, Drukuj i Dodaj do Kalendarza - jedna wspólna akcja
                    printAndAddToCalendar(saved);
                }
            } catch (err) {
                showUploading(false);
                showToast(err.message, 'error');
            }
        });

        document.getElementById('save-only-btn').addEventListener('click', async () => {
            if (validateForm()) {
                const item = getFormData();
                showUploading(true);
                try {
                    const photoCount = selectedFiles.length;
                    await saveItemToDB(item, selectedFiles);
                    showUploading(false);
                    resetForm();
                    selectedFiles = [];
                    renderPhotoPreviews();
                    if (IS_MOBILE) {
                        showPhoneSuccess(photoCount);
                    } else {
                        showToast('Zapisano zgłoszenie w bazie serwisu!');
                    }
                } catch (err) {
                    showUploading(false);
                    showToast(err.message, 'error');
                }
            }
        });

        function validateForm() {
            if (!bikeNameInput.value.trim()) {
                showToast('Wprowadź nazwę roweru!', 'info');
                bikeNameInput.focus();
                return false;
            }
            if (!validatePhone(customerPhoneInput.value)) {
                            showToast('Wprowadź poprawny numer telefonu (min. 9 cyfr)!', 'info');
                customerPhoneInput.focus();
                return false;
            }
            if (!getFormData().faultDescription) {
                showToast('Opisz usterkę lub zaznacz wykonywane usługi!', 'info');
                faultDescriptionInput.focus();
                return false;
            }
            return true;
        }

        // --- TERMINY: zgłoszenia spóźnione i zaplanowane na jutro ---
        function isOverdue(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;   // brak terminu (moduł kalendarza)
            return item.datePlanned < formatDateForInput(new Date());
        }

        function isPlannedToday(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;
            return item.datePlanned === formatDateForInput(new Date());
        }

        function isPlannedTomorrow(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;
            const t = new Date();
            t.setDate(t.getDate() + 1);
            return item.datePlanned === formatDateForInput(t);
        }

        // Liczniki na przyciskach filtrów: Jutro / Po terminie / Kosz
        function setFilterCount(id, count, variant) {
            const el = document.getElementById(id);
            if (!el) return;
            el.hidden = count === 0;
            el.textContent = String(count);
            el.className = 'filter-count' + (variant ? ' ' + variant : '');
        }

        // Licznik na Koszu + odświeżenie kafli dashboardu (reszta filtrów
        // obsługuje się wyłącznie kliknięciem w kafl)
        function updateFilterCounts() {
            setFilterCount('count-trash', db.filter(item => !!item.deleted).length, 'count-neutral');
            updateDashboard();
        }

        // Kafle podsumowań nad listą + podświetlenie kafla aktywnego filtra
        function updateDashboard() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val);
            };
            const live = db.filter(item => !item.deleted);
            set('dash-in-progress', live.filter(item => item.status === 'in_progress').length);
            set('dash-completed', live.filter(item => item.status === 'completed').length);
            set('dash-overdue', db.filter(isOverdue).length);
            set('dash-today', db.filter(isPlannedToday).length);
            set('dash-tomorrow', db.filter(isPlannedTomorrow).length);
            document.querySelectorAll('.dash-tile').forEach(tile =>
                tile.classList.toggle('active', tile.dataset.filter === currentFilter));
        }

        // --- POWITANIE PO ZALOGOWANIU ---
        // Okno „Podsumowanie dnia": ile odbiorów zaplanowanych na dziś i jutro
        // (te same helpery co kafle dashboardu) + ostrzeżenie „Po terminie",
        // gdy jakikolwiek termin minął. Pokazywane tylko po zalogowaniu
        // (flaga ?powitanie=1 z redirectu po udanym logowaniu).
        function pokazPowitanie() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val);
            };
            set('welcome-today', db.filter(isPlannedToday).length);
            set('welcome-tomorrow', db.filter(isPlannedTomorrow).length);
            const poTerminie = db.filter(isOverdue).length;
            const overEl = document.getElementById('welcome-overdue');
            if (overEl) {
                overEl.hidden = poTerminie === 0;
                overEl.textContent = 'Po terminie: ' + poTerminie;
            }
            document.getElementById('welcome-modal').classList.add('active');
        }

        function zamknijPowitanie() {
            document.getElementById('welcome-modal').classList.remove('active');
        }

        document.getElementById('welcome-modal-ok').addEventListener('click', zamknijPowitanie);
        document.getElementById('welcome-modal-close').addEventListener('click', zamknijPowitanie);

        // --- RENDERING LISTY HISTORYCZNEJ ---
        // Wyszukiwanie po tekście (nazwa, telefon, opis, NUMER SERWISOWY, notatki)
        function itemMatchesSearch(item) {
            if (!searchQuery) return true;
            const query = searchQuery.toLowerCase();
            return item.bikeName.toLowerCase().includes(query)
                || item.customerPhone.toLowerCase().includes(query)
                || item.faultDescription.toLowerCase().includes(query)
                || (item.serviceNo || '').toLowerCase().includes(query)
                || (item.serviceNotes || '').toLowerCase().includes(query)
                || (item.servicesDone || []).join(' ').toLowerCase().includes(query);
        }

        function renderServicesList() {
            servicesListContainer.innerHTML = '';
            
            updateFilterCounts();

            // Filtruj i szukaj
            let filteredDb = db.filter(item => {
                const deleted = !!item.deleted;

                if (currentFilter === 'trash') {
                    if (!deleted) return false;
                } else {
                    if (deleted) return false;
                    if (currentFilter === 'tomorrow') {
                        if (!isPlannedTomorrow(item)) return false;
                    } else if (currentFilter === 'today') {
                        if (!isPlannedToday(item)) return false;
                    } else if (currentFilter === 'overdue') {
                        if (!isOverdue(item)) return false;
                    } else if (currentFilter !== 'all' && item.status !== currentFilter) {
                        return false;
                    }
                }
                
                // Wyszukiwanie po tekście (nazwa, telefon, opis, NUMER SERWISOWY)
                return itemMatchesSearch(item);
            });

            // Sortowanie listy (wybór w prawym górnym rogu nad listą)
            const collator = new Intl.Collator('pl');
            const sorters = {
                planned_asc: (a, b) => (a.datePlanned || a.dateIn || '').localeCompare(b.datePlanned || b.dateIn || ''),
                planned_desc: (a, b) => (b.datePlanned || b.dateIn || '').localeCompare(a.datePlanned || a.dateIn || ''),
                dateIn_desc: (a, b) => (b.dateIn || '').localeCompare(a.dateIn || ''),
                dateIn_asc: (a, b) => (a.dateIn || '').localeCompare(b.dateIn || ''),
                name: (a, b) => collator.compare(a.bikeName || '', b.bikeName || ''),
                status: (a, b) => ((STATUS_ORDER[a.status] ?? 9) - (STATUS_ORDER[b.status] ?? 9))
                    || (a.datePlanned || '').localeCompare(b.datePlanned || ''),
            };
            filteredDb.sort((a, b) =>
                (sorters[currentSort] || sorters.planned_asc)(a, b) || (b.id - a.id));

            if (filteredDb.length === 0) {
                const noCriteria = !searchQuery && currentFilter === 'all';
                servicesListContainer.innerHTML = noCriteria
                    ? `
                    <div class="empty-state">
                        <div class="empty-icon">🚲</div>
                        <p>Tu pojawią się przyjęte rowery. Zacznij od formularza „Przyjmij nowy rower”.</p>
                    </div>
                `
                    : currentFilter === 'trash'
                    ? `
                    <div class="empty-state">
                        <div class="empty-icon">🗑️</div>
                        <p>Kosz jest pusty. Usunięte zgłoszenia trafią tutaj i będzie można je przywrócić.</p>
                    </div>
                `
                    : `
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <p>Żadne zgłoszenie nie pasuje do wyszukiwania ani filtru. Zmień kryteria.</p>
                    </div>
                `;
                return;
            }

            filteredDb.forEach(item => {
                const card = document.createElement('div');
                const isPending = !item.confirmed && !item.deleted;
                const overdue = isOverdue(item);
                card.className = 'service-item-card card-' + item.status
                    + (isPending ? ' pending-card' : '')
                    + (item.deleted ? ' is-deleted' : '');
                card.dataset.id = item.id;
                
                let statusLabel = '';
                if (item.deleted) statusLabel = 'W koszu';
                else if (item.status === 'in_progress') statusLabel = 'W serwisie';
                else if (item.status === 'completed') statusLabel = 'Gotowy';
                else if (item.status === 'picked_up') statusLabel = 'Odebrany';

                const photos = Array.isArray(item.photos) ? item.photos : [];

                // Odznaka statusu: w koszu brak przełączania
                const statusHtml = item.deleted
                    ? `<span class="status-badge status-picked_up">${statusLabel}</span>`
                    : `<span class="status-badge status-${escapeHtml(item.status)}" onclick="cycleStatus(${item.id})" title="Kliknij, aby zmienić status">${statusLabel}</span>`;

                // Miniatury zdjęć na karcie (moduł zdjęć może być wyłączony)
                const thumbs = modulOn('zdjecia') ? photos.map(p => `
                    <button class="photo-thumb" onclick="openLightbox('${escapeHtml(p.url)}')" title="${escapeHtml(p.name)}">
                        <img src="${escapeHtml(p.url)}" alt="Zdjęcie zgłoszenia">
                    </button>
                `).join('') : '';

                // Kalendarz/Drukuj dostępne tylko na komputerze (na mobile nie ma przycisków druku/API)
                const calendarBtn = (IS_MOBILE || !modulOn('kalendarz')) ? '' : `
                        <button class="btn-action action-calendar" onclick="openGoogleCalendarFromId(${item.id})" title="Udostępnij do Kalendarza Google">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-3-9v.008H12V9.75h3.75Z" />
                            </svg>
                            Kalendarz
                        </button>`;
                const printBtn = (IS_MOBILE || !modulOn('druk')) ? '' : `
                        <button class="btn-action" onclick="printFromId(${item.id})" title="Drukuj potwierdzenie">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829a42.409 42.409 0 0 0 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 1.252a1.125 1.125 0 0 1-1.107 1.328H7.218a1.125 1.125 0 0 1-1.107-1.328L6.34 18m11.32 0H6.34m0 0h11.32M18 10.5h.008v.008H18V10.5Zm-1.8-6.177a1.95 1.95 0 0 1 2.593 0c.38.347.607.82.607 1.32V9.75H4.5V5.643c0-.5.227-.973.607-1.32a1.95 1.95 0 0 1 2.593 0" />
                            </svg>
                            Drukuj
                        </button>`;

                const photosBtn = modulOn('zdjecia') ? `
                        <button class="btn-action" onclick="openPhotosModal(${item.id})" title="Zobacz i dodaj zdjęcia">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M12 18.75V21m0 0h12M21 12V8.25m0 0h-3.75m3.75 0V4.5m-3.75 3.75h3.75M14.25 7.5h.008v.008h-.008V7.5Z" />
                            </svg>
                            Zdjęcia (${photos.length})
                        </button>` : '';

                const editBtn = `
                        <button class="btn-action" onclick="openEditModal(${item.id})" title="Edytuj zgłoszenie">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            Edytuj
                        </button>`;

                const trashBtn = `
                        <button class="btn-action btn-danger-outline" onclick="deleteItem(${item.id})" title="Przenieś do kosza">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>`;

                const restoreBtn = `
                        <button class="btn-action" onclick="restoreItem(${item.id})" title="Przywróć zgłoszenie z kosza">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            Przywróć
                        </button>`;

                const purgeBtn = `
                        <button class="btn-action btn-danger-outline" onclick="purgeItem(${item.id})" title="Usuń trwale wraz ze zdjęciami">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>`;

                // W koszu tylko podgląd i przywrócenie; poza koszem pełne akcje
                // (kosz może być wyłączony modułem - wtedy nie ma też koszenia)
                const actionsHtml = `<div class="item-actions">${
                    item.deleted
                        ? `${photosBtn}${restoreBtn}${purgeBtn}`
                        : `${photosBtn}${editBtn}${calendarBtn}${printBtn}${modulOn('kosz') ? trashBtn : ''}`
                }</div>`;

                card.innerHTML = `
                    <div class="item-header">
                        <div class="item-info">
                            <h3>${escapeHtml(item.bikeName)}${item.serviceNo ? `<span class="service-no" title="Numer serwisowy">${escapeHtml(item.serviceNo)}</span>` : ''}</h3>
                            <a href="tel:${escapeHtml(item.customerPhone)}" class="phone-link">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.802-5.194-4.174-6.996-7.002l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                tel. ${escapeHtml(item.customerPhone)}
                            </a>
                        </div>
                        ${statusHtml}
                    </div>
                    
                    <div class="item-details">
                        <div class="detail-row">
                            <span class="detail-label">Przyjęto:</span>
                            <span class="detail-val">${formatDateForUser(item.dateIn)}</span>
                        </div>
                        ${modulOn('kalendarz') ? `
                        <div class="detail-row">
                            <span class="detail-label">Termin:</span>
                            <span class="detail-val">${formatDateForUser(item.datePlanned)}${overdue ? ' <span class="overdue-badge">Po terminie</span>' : ''}</span>
                        </div>` : ''}
                        <div class="fault-desc">${escapeHtml(item.faultDescription)}</div>
                        ${item.serviceNotes ? `<div class="detail-row" style="grid-column: span 2;"><span class="detail-label">Notatki:</span><span class="detail-val" style="white-space: pre-wrap;">${escapeHtml(item.serviceNotes)}</span></div>` : ''}
                        ${thumbs ? `<div class="item-photos">${thumbs}</div>` : ''}
                    </div>
                    
                    ${actionsHtml}
                `;

                // Zamazanie zgłoszenia z mobile do czasu potwierdzenia na komputerze
                if (isPending) {
                    card.innerHTML += `
                        <div class="pending-mask">
                            <span class="pending-note">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                Zgłoszenie zablokowane — ${IS_MOBILE ? 'wymaga potwierdzenia na komputerze' : 'wymaga potwierdzenia'}
                            </span>
                            ${IS_MOBILE ? '' : `
                            <button class="btn-confirm" onclick="confirmItem(${item.id})" title="Potwierdź zgłoszenie">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Potwierdź
                            </button>`}
                        </div>
                    `;
                }

                servicesListContainer.appendChild(card);
            });
        }

        // Kliknięcie w treść karty na liście otwiera kartę zgłoszenia.
        // Przyciski, linki, odznaka statusu i maska potwierdzenia zachowują
        // swoją dotychczasową rolę (są pomijane przez closest()).
        servicesListContainer.addEventListener('click', (e) => {
            if (e.target.closest('a, button, .status-badge, .pending-mask, input, label')) return;
            const card = e.target.closest('.service-item-card');
            if (!card) return;
            const id = parseInt(card.dataset.id, 10);
            if (Number.isNaN(id)) return;
            openDetailModal(id);
        });

        // Potwierdzenie zgłoszenia z mobile: odblokowanie + kalendarz + druk
        window.confirmItem = async function(id) {
            const item = db.find(item => item.id === id);
            if (!item || item.confirmed) return;

            try {
                const formData = new FormData();
                formData.append('action', 'confirm');
                formData.append('id', item.id);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się potwierdzić zgłoszenia.');

                item.confirmed = true;
                renderServicesList();
                showToast('Zgłoszenie potwierdzone i odblokowane!');

                // Jednocześnie: dodanie do kalendarza + wydruk potwierdzenia
                // (części wyłączone modułami pomijane)
                printAndAddToCalendar(item);
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Zmiana statusu w cyklu: W serwisie -> Gotowy -> Odebrany -> W serwisie...
        window.cycleStatus = async function(id) {
            const index = db.findIndex(item => item.id === id);
            if (index === -1) return;

            const item = db[index];
            let next;
            if (item.status === 'in_progress') next = 'completed';
            else if (item.status === 'completed') next = 'picked_up';
            else next = 'in_progress';

            try {
                const formData = new FormData();
                formData.append('action', 'status');
                formData.append('id', item.id);
                formData.append('status', next);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zmienić statusu.');

                item.status = next;
                renderServicesList();
                showToast(`Zmieniono status roweru: ${item.bikeName}`);

                // Wydanie roweru z listy — druk karty wydania, gdy włączone
                // moduły druku i Karty wydania
                if (next === 'picked_up' && modulOn('druk') && modulOn('karta_wydania') && !IS_MOBILE) {
                    triggerPrint(item, 'wydanie');
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Narzędziowe akcje na wierszach historii
        window.openGoogleCalendarFromId = function(id) {
            const item = db.find(item => item.id === id);
            if (item) openGoogleCalendar(item);
        };

        window.printFromId = function(id) {
            const item = db.find(item => item.id === id);
            // Po wydaniu przycisk drukuje kartę wydania, wcześniej — potwierdzenie przyjęcia
            if (item) triggerPrint(item, item.status === 'picked_up' ? 'wydanie' : 'przyjecie');
        };

        window.deleteItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            const item = db.find(item => item.id === id);
            const bikeLabel = item ? `„${item.bikeName}”` : 'to zlecenie';
            const confirmed = await showConfirmModal(
                'Przenieś do kosza',
                `Zgłoszenie ${bikeLabel} trafi do kosza — będzie można je przywrócić. Trwałe usunięcie (razem ze zdjęciami) znajdziesz w koszu.`,
                'Do kosza'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZGLOSZENIA}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się przenieść zgłoszenia do kosza.');

                if (item) item.deleted = true;
                renderServicesList();
                showToast('Zgłoszenie przeniesione do kosza.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Przywrócenie zgłoszenia z kosza
        window.restoreItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            try {
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('id', id);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się przywrócić zgłoszenia.');

                const item = db.find(item => item.id === id);
                if (item) item.deleted = false;
                renderServicesList();
                showToast('Przywrócono zgłoszenie z kosza.');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Trwałe usunięcie z kosza (wraz ze zdjęciami)
        window.purgeItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            const item = db.find(item => item.id === id);
            const bikeLabel = item ? `„${item.bikeName}”` : 'to zlecenie';
            const confirmed = await showConfirmModal(
                'Usuń trwale',
                `Zgłoszenie ${bikeLabel} wraz ze zdjęciami zostanie nieodwracalnie usunięte z bazy. Tej operacji nie można cofnąć.`,
                'Usuń trwale'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZGLOSZENIA}?id=${encodeURIComponent(id)}&purge=1`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć zgłoszenia.');

                db = db.filter(item => item.id !== id);
                renderServicesList();
                showToast('Usunięto zgłoszenie trwale.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // --- MODAL EDYCJI ZGŁOSZENIA ---
        const editModal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-form');
        const editBikeNameInput = document.getElementById('edit-bike-name');
        const editDateInInput = document.getElementById('edit-date-in');
        const editDatePlannedInput = document.getElementById('edit-date-planned');
        const editCustomerPhoneInput = document.getElementById('edit-customer-phone');
        const editFaultInput = document.getElementById('edit-fault');
        const editServiceNotesInput = document.getElementById('edit-service-notes');
        let editModalId = null;

        function closeEditModal() {
            editModal.classList.remove('active');
            editModalId = null;
        }

        window.openEditModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item || item.deleted) return;

            editModalId = id;
            editBikeNameInput.value = item.bikeName;
            editDateInInput.value = item.dateIn;
            editDatePlannedInput.value = item.datePlanned;
            editCustomerPhoneInput.value = item.customerPhone;
            editFaultInput.value = item.faultDescription;
            editServiceNotesInput.value = item.serviceNotes || '';
            editModal.classList.add('active');
            editBikeNameInput.focus();
        };

        editCustomerPhoneInput.addEventListener('input', (e) => {
            e.target.value = formatPhone(e.target.value);
        });

        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (editModalId === null) return;

            const item = db.find(item => item.id === editModalId);
            if (!item) { closeEditModal(); return; }

            if (!validatePhone(editCustomerPhoneInput.value)) {
                showToast('Numer telefonu musi mieć co najmniej 9 cyfr.', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', editModalId);
                formData.append('bike_name', editBikeNameInput.value.trim());
                formData.append('date_in', editDateInInput.value);
                formData.append('date_planned', editDatePlannedInput.value);
                formData.append('customer_phone', editCustomerPhoneInput.value.trim());
                formData.append('fault_description', editFaultInput.value.trim());
                formData.append('status', item.status);
                formData.append('service_notes', editServiceNotesInput.value.trim());

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zapisać zmian.');

                const idx = db.findIndex(row => row.id === editModalId);
                if (idx !== -1) db[idx] = data.data;

                closeEditModal();
                renderServicesList();
                showToast('Zaktualizowano zgłoszenie.');
            } catch (err) {
                showToast(err.message, 'error');
            }
        });

        document.getElementById('edit-modal-cancel').addEventListener('click', closeEditModal);
        document.getElementById('edit-modal-close').addEventListener('click', closeEditModal);
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });

        // --- WYKONANE CZYNNOŚCI (checkboxy z katalogu usług) ---
        // Faktycznie wykonane: stan zapisany w bazie.
        // null = jeszcze nie zapisywano (nic nie zaznaczono) -> pusta lista.
        function getDoneServices(item) {
            return Array.isArray(item.servicesDone) ? item.servicesDone : [];
        }

        // Zakres uzgodniony przy przyjęciu: linie „- Usługa” dopisane do opisu
        // przy zakładaniu zgłoszenia. Karta pokazuje tylko te pozycje; dokładamy
        // też to, co jest już zaznaczone jako wykonane (żeby nie zgubić stanu).
        function getPlannedServices(item) {
            const lines = (item.faultDescription || '').split('\n')
                .map(l => l.trim())
                .filter(l => l.startsWith('- '))
                .map(l => l.slice(2).trim())
                .filter(l => l.length > 0);
            const names = services.map(s => s.nazwa);
            const planned = names.length ? lines.filter(l => names.includes(l)) : lines;
            return Array.from(new Set([...planned, ...getDoneServices(item)]));
        }

        // Zapis zaznaczeń z karty zgłoszenia (debounce: jeden request na serię kliknięć)
        let doneSaveTimer = null;
        const donePending = new Map();   // id zgłoszenia -> { item, values, prev }
        function onDoneToggle(item) {
            const before = donePending.get(item.id);
            const values = Array.from(
                document.querySelectorAll('#detail-done-list input:checked')
            ).map(cb => cb.value);
            // prev = stan sprzed całej serii kliknięć (do wycofania przy błędzie)
            donePending.set(item.id, {
                item,
                values,
                prev: before ? before.prev : item.servicesDone
            });
            item.servicesDone = values;

            clearTimeout(doneSaveTimer);
            doneSaveTimer = setTimeout(flushDoneSaves, 450);
        }

        async function flushDoneSaves() {
            const entries = Array.from(donePending.values());
            donePending.clear();
            for (const e of entries) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'services');
                    fd.append('id', e.item.id);
                    fd.append('services_done', JSON.stringify(e.values));
                    const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Nie udało się zapisać wykonanych czynności.');
                    e.item.servicesDone = data.data.servicesDone;
                } catch (err) {
                    e.item.servicesDone = e.prev;   // wycofanie optymistycznej zmiany
                    if (detailModalId === e.item.id) fillDetailModal(e.item);
                    showToast(err.message, 'error');
                }
            }
        }

        // Autozapis notatek z karty zgłoszenia (debounce jak przy checkboxach)
        let notesSaveTimer = null;
        const notesPending = new Map();   // id zgłoszenia -> { item, value, prev }
        document.getElementById('detail-notes').addEventListener('input', () => {
            const item = db.find(x => x.id === detailModalId);
            if (!item) return;
            const value = document.getElementById('detail-notes').value;
            const before = notesPending.get(item.id);
            notesPending.set(item.id, {
                item,
                value,
                prev: before ? before.prev : item.serviceNotes
            });
            item.serviceNotes = value;   // stan lokalny od razu (podgląd/wydruk)

            clearTimeout(notesSaveTimer);
            notesSaveTimer = setTimeout(flushNotesSaves, 450);
        });

        async function flushNotesSaves() {
            const entries = Array.from(notesPending.values());
            notesPending.clear();
            for (const e of entries) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'notes');
                    fd.append('id', e.item.id);
                    fd.append('service_notes', e.value);
                    const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Nie udało się zapisać notatek.');
                    e.item.serviceNotes = data.data.serviceNotes;
                } catch (err) {
                    e.item.serviceNotes = e.prev || '';
                    if (detailModalId === e.item.id) {
                        document.getElementById('detail-notes').value = e.prev || '';
                    }
                    showToast(err.message, 'error');
                }
            }
        }

        // --- MODAL PODGLĄDU ZGŁOSZENIA (bez edycji, z wydaniem roweru) ---
        const detailModal = document.getElementById('detail-modal');
        const detailIssueBtn = document.getElementById('detail-issue-btn');
        let detailModalId = null;

        function statusLabelFor(item) {
            if (item.deleted) return 'W koszu';
            if (item.status === 'in_progress') return 'W serwisie';
            if (item.status === 'completed') return 'Gotowy';
            if (item.status === 'picked_up') return 'Odebrany';
            return '';
        }

        function fillDetailModal(item) {
            const noEl = document.getElementById('detail-service-no');
            noEl.textContent = item.serviceNo || '';
            noEl.hidden = !item.serviceNo;

            document.getElementById('detail-bike-name').textContent = item.bikeName;

            const badge = document.getElementById('detail-status');
            badge.textContent = statusLabelFor(item);

            document.getElementById('detail-date-in').textContent = formatDateForUser(item.dateIn);

            const overdue = isOverdue(item);
            document.getElementById('detail-date-planned').textContent =
                formatDateForUser(item.datePlanned) + (overdue ? ' — PO TERMINIE' : '');

            document.getElementById('detail-phone').innerHTML =
                `<a href="tel:${escapeHtml(item.customerPhone)}">tel. ${escapeHtml(item.customerPhone)}</a>`;

            document.getElementById('detail-fault').textContent = item.faultDescription || '—';

            // Notatki: aktywne pole z autozapisem (w koszu tylko do odczytu)
            const notesEl = document.getElementById('detail-notes');
            notesEl.value = item.serviceNotes || '';
            notesEl.disabled = !!item.deleted;

            // Wydanie roweru dostępne tylko dla zgłoszeń spoza kosza i nieodebranych
            const canIssue = !item.deleted && item.status !== 'picked_up';
            detailIssueBtn.disabled = !canIssue;
            detailIssueBtn.textContent = canIssue ? 'Wydaj rower' : 'Rower już wydany';

            // Wykonane czynności: wyłącznie usługi z pierwotnego zgłoszenia,
            // jako puste checkboxy — zaznaczasz je w chwili wykonania pracy;
            // zaznaczenie zapisuje się samo. Po wydaniu/z kosza tylko podgląd.
            // Brak usług z przyjęcia lub wyłączony moduł — wiersz znika.
            const doneListEl = document.getElementById('detail-done-list');
            const planned = getPlannedServices(item);
            const showDone = modulOn('wykonane') && planned.length > 0;
            document.getElementById('detail-done-label').hidden = !showDone;
            doneListEl.hidden = !showDone;
            doneListEl.textContent = '';
            if (showDone) {
                const checked = getDoneServices(item);
                const wrap = document.createElement('span');
                wrap.className = 'service-checkbox-list';
                wrap.style.marginTop = '0';
                planned.forEach(name => {
                    const label = document.createElement('label');
                    label.className = 'service-check';
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.className = 'service-checkbox';
                    cb.value = name;
                    cb.checked = checked.includes(name);
                    cb.disabled = !canIssue;
                    cb.addEventListener('change', () => onDoneToggle(item));
                    label.appendChild(cb);
                    label.appendChild(document.createTextNode(' ' + name));
                    wrap.appendChild(label);
                });
                doneListEl.appendChild(wrap);
            }

            // Info o zgłoszeniu z telefonu: na mobile lista z maską jest ukryta,
            // więc stan „wymaga potwierdzenia" pokazujemy też w karcie.
            const pendingEl = document.getElementById('detail-pending');
            pendingEl.hidden = item.confirmed || !!item.deleted;
            document.getElementById('detail-pending-text').textContent = IS_MOBILE
                ? 'Zgłoszenie zablokowane — wymaga potwierdzenia na komputerze'
                : 'Zgłoszenie zablokowane — wymaga potwierdzenia';
        }

        window.openDetailModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item) return;
            detailModalId = id;
            fillDetailModal(item);
            detailModal.classList.add('active');
        };

        function closeDetailModal() {
            detailModal.classList.remove('active');
            detailModalId = null;
        }

        // Zatwierdzenie wydania roweru (status -> picked_up)
        detailIssueBtn.addEventListener('click', async () => {
            if (detailModalId === null) return;
            const item = db.find(item => item.id === detailModalId);
            if (!item || item.deleted || item.status === 'picked_up') return;

            try {
                const formData = new FormData();
                formData.append('action', 'status');
                formData.append('id', item.id);
                formData.append('status', 'picked_up');

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zmienić statusu.');

                item.status = 'picked_up';
                fillDetailModal(item);
                renderServicesList();
                showToast(`Rower wydany klientowi: ${item.bikeName}`);

                // Karta wydania roweru: druk od razu (komputer + moduły
                // druku i Karta wydania)
                if (modulOn('druk') && modulOn('karta_wydania') && !IS_MOBILE) triggerPrint(item, 'wydanie');
            } catch (err) {
                showToast(err.message, 'error');
            }
        });

        document.getElementById('detail-modal-close').addEventListener('click', closeDetailModal);
        document.getElementById('detail-cancel-btn').addEventListener('click', closeDetailModal);
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) closeDetailModal();
        });

        // --- SKANER QR (aparat przy pasku wyszukiwania) ---
        const scanModal = document.getElementById('scan-modal');
        const scanVideo = document.getElementById('scan-video');
        const scanHint = document.getElementById('scan-hint');
        let scanStream = null;
        let scanRafId = null;
        let scanBusy = false;
        let scanLastTick = 0;
        let barcodeDetector = null;
        let jsQRPromise = null;

        function stopScanner() {
            scanModal.classList.remove('active');
            scanBusy = false;
            if (scanRafId !== null) {
                cancelAnimationFrame(scanRafId);
                scanRafId = null;
            }
            if (scanStream) {
                scanStream.getTracks().forEach(track => track.stop());
                scanStream = null;
            }
            scanVideo.srcObject = null;
        }

        // Fallback jsQR (np. iOS Safari nie ma natywnego BarcodeDetector) — ładowany na żądanie
        function loadJsQR() {
            if (!jsQRPromise) {
                jsQRPromise = new Promise((resolve, reject) => {
                    if (typeof window.jsQR !== 'undefined') return resolve(window.jsQR);
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                    s.onload = () => resolve(window.jsQR);
                    s.onerror = () => reject(new Error('Nie udało się załadować biblioteki skanera.'));
                    document.head.appendChild(s);
                });
            }
            return jsQRPromise;
        }

        async function detectCodeFromVideo() {
            // 1) Natywny BarcodeDetector (Chrome/Android)
            if ('BarcodeDetector' in window) {
                if (!barcodeDetector) {
                    try {
                        barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
                    } catch (e) {
                        barcodeDetector = null;
                    }
                }
                if (barcodeDetector) {
                    const codes = await barcodeDetector.detect(scanVideo);
                    if (codes.length) return codes[0].rawValue;
                    return null;
                }
            }

            // 2) Fallback: jsQR na klatce pobranej z <video>
            const jsQR = await loadJsQR();
            const w = scanVideo.videoWidth;
            const h = scanVideo.videoHeight;
            if (!w || !h) return null;

            const canvas = detectCodeFromVideo.canvas || (detectCodeFromVideo.canvas = document.createElement('canvas'));
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(scanVideo, 0, 0, w, h);
            const frame = ctx.getImageData(0, 0, w, h);
            const res = jsQR(frame.data, w, h);
            return res ? res.data : null;
        }

        async function scanTick(ts) {
            if (!scanModal.classList.contains('active')) return;

            if (!scanBusy && scanStream && scanVideo.readyState >= 2 && ts - scanLastTick > 300) {
                scanBusy = true;
                scanLastTick = ts;
                try {
                    const code = await detectCodeFromVideo();
                    if (code) {
                        handleScanResult(code);
                        return;
                    }
                } catch (e) {
                    if (!scanTick.warned) {
                        scanTick.warned = true;
                        scanHint.textContent = 'Nie udało się uruchomić odczytu kodu — wpisz numer ręcznie w wyszukiwarce.';
                    }
                }
                scanBusy = false;
            }
            scanRafId = requestAnimationFrame(scanTick);
        }

        // Wynik skanu: przefiltruj listę i otwórz kartę podglądu
        function handleScanResult(raw) {
            const code = String(raw || '').trim();
            stopScanner();

            // Skan ma być widoczny niezależnie od aktywnego filtra
            currentFilter = 'all';
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === 'all');
            });

            searchQuery = code;
            searchInput.value = code;
            renderServicesList();

            const matches = db.filter(item =>
                !item.deleted && (item.serviceNo || '').toLowerCase() === code.toLowerCase()
            );

            if (matches.length === 1) {
                openDetailModal(matches[0].id);
                showToast(`Znaleziono zgłoszenie: ${matches[0].bikeName}`);
            } else if (matches.length > 1) {
                if (IS_MOBILE) {
                    // Lista na telefonie jest ukryta — od razu pokazujemy kartę
                    openDetailModal(matches[0].id);
                    showToast(`Znaleziono ${matches.length} zgłoszeń — pokazano pierwsze.`, 'info');
                } else {
                    showToast(`Znaleziono ${matches.length} zgłoszeń o tym numerze — sprawdź listę.`, 'info');
                }
            } else {
                showToast(`Brak zgłoszenia o numerze ${code}.`, 'error');
            }
        }

        window.openScanModal = async function() {
            if (!modulOn('skaner')) return;   // moduł skanera wyłączony
            // Wyczyść poprzednie wyszukiwanie, żeby wynik skanu był czysty
            searchQuery = '';
            searchInput.value = '';
            scanTick.warned = false;
            scanHint.textContent = 'Skieruj aparat na kod QR z numerem serwisowym (naklejka na rowerze).';
            scanModal.classList.add('active');

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Ta przeglądarka nie udostępnia aparatu.');
                }
                scanStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false
                });
                scanVideo.srcObject = scanStream;
                await scanVideo.play();
                scanLastTick = 0;
                scanRafId = requestAnimationFrame(scanTick);
            } catch (err) {
                stopScanner();
                showToast('Nie udało się uruchomić aparatu: ' + (err.message || err.name || 'błąd'), 'error');
            }
        };

        document.getElementById('scan-qr-btn').addEventListener('click', window.openScanModal);
        document.getElementById('scan-modal-close').addEventListener('click', stopScanner);
        document.getElementById('scan-cancel-btn').addEventListener('click', stopScanner);
        scanModal.addEventListener('click', (e) => {
            if (e.target === scanModal) stopScanner();
        });

        // Skaner tylko na urządzeniach mobilnych (aparat)
        if (!IS_MOBILE) {
            document.getElementById('scan-qr-btn').hidden = true;
        }

        // --- KALENDARZ TERMINÓW (widok miesięczny) ---
        const calendarModal = document.getElementById('calendar-modal');
        const calGrid = document.getElementById('cal-grid');
        const calTitle = document.getElementById('cal-title');
        const MONTHS_PL = [
            'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
        ];
        let calYear = new Date().getFullYear();
        let calMonth = new Date().getMonth();

        // Kolejność chipów w dniu: do wykonania → gotowe → odebrane
        const STATUS_ORDER = { in_progress: 0, completed: 1, picked_up: 2 };

        // Wszystkie zgłoszenia w kalendarzu (bez kosza):
        // nieodebrane kolorowo, odebrane na szaro
        function calendarItems() {
            return db.filter(item => !item.deleted);
        }

        // Kolejność zgłoszeń w dniu: do wykonania → gotowe → odebrane
        function calItemOrder(a, b) {
            return ((STATUS_ORDER[a.status] ?? 9) - (STATUS_ORDER[b.status] ?? 9)) || (a.id - b.id);
        }

        // Listy dni — wypełnia renderCalendar, czyta dymek „więcej"
        let calDayData = {};

        // --- DYMIEK „więcej" — powiększony widok dnia ---
        const calPopover = document.createElement('div');
        calPopover.className = 'cal-popover';
        document.body.appendChild(calPopover);
        let calHideTimer = null;
        let calShownAt = 0;

        function calHidePopover() {
            calPopover.classList.remove('open');
        }

        function calHideSoon() {
            clearTimeout(calHideTimer);
            calHideTimer = setTimeout(() => {
                // Dymek nachodzi na komórkę — jeśli kursor jest nad nim, czekamy
                if (calPopover.matches(':hover')) { calHideSoon(); return; }
                calHidePopover();
            }, 300);
        }

        function calPlural(n) {
            if (n === 1) return 'zgłoszenie';
            const last = n % 10, last2 = n % 100;
            if (last >= 2 && last <= 4 && !(last2 >= 12 && last2 <= 14)) return 'zgłoszenia';
            return 'zgłoszeń';
        }

        function calShowPopover(anchor) {
            const key = anchor.getAttribute('data-cal-day');
            const items = (calDayData[key] || []).slice().sort(calItemOrder);
            if (!items.length) return;

            const d = new Date(key + 'T00:00:00');
            const title = d.toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', weekday: 'long' });
            const rows = items.map(item => {
                let cls = item.status === 'picked_up' ? 's-done'
                    : item.status === 'completed' ? 's-ready' : 's-progress';
                if (item.status !== 'picked_up' && isOverdue(item)) cls += ' is-overdue';
                const range = `${formatDateForUser(item.dateIn)} → ${formatDateForUser(item.datePlanned)}`;
                return `<button type="button" class="cal-pop-item ${cls}" data-cal-id="${item.id}">
                    <span class="cal-pop-name">${escapeHtml(item.bikeName)}</span>
                    <span class="cal-pop-meta">${statusLabelFor(item)} · ${range}</span>
                </button>`;
            }).join('');

            calPopover.innerHTML = `<div class="cal-popover-title">${title} — ${items.length} ${calPlural(items.length)}</div>${rows}`;
            calPopover.dataset.day = key;
            calPopover.classList.add('open');
            calShownAt = Date.now();

            // Dymek rozrasta się "z komórki dnia" — nachodzi na nią bez odstępu,
            // animacja (CSS) robi wrażenie powiększania się samej komórki
            const cell = anchor.closest('.cal-cell') || anchor;
            const cr = cell.getBoundingClientRect();
            const pw = calPopover.offsetWidth;
            const ph = calPopover.offsetHeight;

            let top = cr.top - 2;
            let originY = 'top';
            if (top + ph > window.innerHeight - 8) {      // brak miejsca na dole -> w górę
                top = Math.max(8, cr.bottom + 2 - ph);
                originY = 'bottom';
            }
            const left = Math.max(8, Math.min(cr.left - 2, window.innerWidth - pw - 8));

            calPopover.style.transformOrigin = `${originY} left`;
            calPopover.style.left = left + 'px';
            calPopover.style.top = top + 'px';
        }

        function renderCalendar() {
            calTitle.textContent = `${MONTHS_PL[calMonth]} ${calYear}`;

            // Tydzień zaczyna się od poniedziałku
            const firstDay = new Date(calYear, calMonth, 1);
            const startOffset = (firstDay.getDay() + 6) % 7;
            const start = new Date(calYear, calMonth, 1 - startOffset);

            // Grupowanie: każdy dzień od przyjęcia do planowanego odbioru
            // (wpis 25–27 pojawia się w kalendarzu na 25, 26 i 27)
            const byDay = {};
            calendarItems().forEach(item => {
                const from = (item.dateIn || item.datePlanned);
                let to = item.datePlanned || from;
                if (to < from) to = from;

                let d = new Date(from + 'T00:00:00');
                const end = new Date(to + 'T00:00:00');
                let guard = 0;
                while (d <= end && guard < 90) {
                    const key = formatDateForInput(d);
                    (byDay[key] = byDay[key] || []).push(item);
                    d.setDate(d.getDate() + 1);
                    guard++;
                }
            });
            calDayData = byDay;

            const todayStr = formatDateForInput(new Date());
            let html = '';

            for (let i = 0; i < 42; i++) {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                const key = formatDateForInput(d);
                const otherMonth = d.getMonth() !== calMonth;
                const isToday = key === todayStr;

                const dayItems = (byDay[key] || []).slice().sort(calItemOrder);

                const chips = dayItems.slice(0, 3).map(item => {
                    let cls;
                    if (item.status === 'picked_up') {
                        cls = 'chip-done';                       // odebrany — na szaro
                    } else {
                        cls = (item.status === 'completed' ? 'chip-ready' : 'chip-progress')
                            + (isOverdue(item) ? ' chip-overdue' : '');
                    }
                    const range = `${formatDateForUser(item.dateIn)} → ${formatDateForUser(item.datePlanned)}`;
                    return `<button type="button" class="cal-chip ${cls}" data-cal-id="${item.id}" title="${escapeHtml(item.bikeName)} — w serwisie ${range}">${escapeHtml(item.bikeName)}</button>`;
                }).join('');

                const more = dayItems.length > 3
                    ? `<span class="cal-more" data-cal-day="${key}" role="button" tabindex="0" aria-label="Pokaż wszystkie zgłoszenia tego dnia">+${dayItems.length - 3} więcej</span>`
                    : '';

                html += `
                    <div class="cal-cell${otherMonth ? ' other-month' : ''}${isToday ? ' today' : ''}">
                        <span class="cal-daynum">${d.getDate()}</span>
                        ${chips}${more}
                    </div>`;
            }

            calGrid.innerHTML = html;
        }

        // Kliknięcie wpisu w kalendarzu -> karta podglądu zgłoszenia
        function openCalendarItem(id) {
            calHidePopover();
            calendarModal.classList.remove('active');
            const item = db.find(row => row.id === id);
            if (item) {
                openDetailModal(id);
            } else {
                showToast('To zgłoszenie nie jest już dostępne.', 'error');
            }
        }

        calGrid.addEventListener('click', (e) => {
            const more = e.target.closest('.cal-more');
            if (more) {
                // Na dotyku nie ma najechania — kliknięcie przełącza dymek dnia
                const key = more.getAttribute('data-cal-day');
                if (calPopover.classList.contains('open') && calPopover.dataset.day === key
                        && Date.now() - calShownAt > 600) {
                    calHidePopover();
                } else {
                    clearTimeout(calHideTimer);
                    calShowPopover(more);
                }
                return;
            }
            const chip = e.target.closest('[data-cal-id]');
            if (!chip) return;
            openCalendarItem(parseInt(chip.getAttribute('data-cal-id'), 10));
        });

        // Najechanie/fokus na „więcej" -> dymek z pełną listą dnia
        calGrid.addEventListener('mouseover', (e) => {
            const more = e.target.closest('.cal-more');
            if (!more) return;
            clearTimeout(calHideTimer);
            calShowPopover(more);
        });
        calGrid.addEventListener('mouseout', (e) => {
            const more = e.target.closest('.cal-more');
            if (more && !(e.relatedTarget && more.contains(e.relatedTarget))) calHideSoon();
        });
        calGrid.addEventListener('focusin', (e) => {
            const more = e.target.closest('.cal-more');
            if (more) { clearTimeout(calHideTimer); calShowPopover(more); }
        });
        calGrid.addEventListener('focusout', (e) => {
            if (e.target.closest('.cal-more')) calHideSoon();
        });

        // Dymek trzyma się otwarty pod kursorem; kliknięcie otwiera zgłoszenie
        calPopover.addEventListener('mouseenter', () => clearTimeout(calHideTimer));
        calPopover.addEventListener('mouseleave', calHideSoon);
        calPopover.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-cal-id]');
            if (!btn) {
                // Kliknięcie w tło dymka (np. drugi tap na dotyku) — zamknij
                calHidePopover();
                return;
            }
            openCalendarItem(parseInt(btn.getAttribute('data-cal-id'), 10));
        });

        // Znika przy przewijaniu (dymek jest „przyklejony" do komórki)
        window.addEventListener('scroll', calHideSoon, true);

        function shiftCalendar(delta) {
            calHidePopover();
            calMonth += delta;
            if (calMonth < 0) { calMonth = 11; calYear--; }
            if (calMonth > 11) { calMonth = 0; calYear++; }
            renderCalendar();
        }

        document.getElementById('open-calendar-btn').addEventListener('click', () => {
            const now = new Date();
            calYear = now.getFullYear();
            calMonth = now.getMonth();
            renderCalendar();
            calendarModal.classList.add('active');
        });

        document.getElementById('cal-prev-btn').addEventListener('click', () => shiftCalendar(-1));
        document.getElementById('cal-next-btn').addEventListener('click', () => shiftCalendar(1));
        document.getElementById('calendar-modal-close').addEventListener('click', () => {
            calHidePopover();
            calendarModal.classList.remove('active');
        });
        calendarModal.addEventListener('click', (e) => {
            if (e.target === calendarModal) {
                calHidePopover();
                calendarModal.classList.remove('active');
            }
        });

        // --- MODAL ZDJĘĆ: podgląd, dodawanie, usuwanie ---
        let photosModalId = null;

        window.openPhotosModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item) return;
            photosModalId = id;
            photosModalTitle.textContent = item.bikeName;
            photosModalInput.value = '';
            renderPhotosGrid(item.photos || []);
            photosModal.classList.add('active');
        };

        function renderPhotosGrid(photos) {
            photosGrid.innerHTML = '';
            if (!photos.length) {
                photosGrid.innerHTML = '<div class="photos-empty">Brak zdjęć. Wgraj pierwsze zdjęcie powyżej.</div>';
                return;
            }
            photos.forEach(photo => {
                const tile = document.createElement('div');
                tile.className = 'photo-tile';
                tile.innerHTML = `
                    <img src="${escapeHtml(photo.url)}" alt="${escapeHtml(photo.name || 'Zdjęcie')}" onclick="openLightbox('${escapeHtml(photo.url)}')">
                    <button class="delete-photo" title="Usuń zdjęcie" onclick="deletePhoto(${photo.id})">&times;</button>
                `;
                photosGrid.appendChild(tile);
            });
        }

        async function uploadModalPhotos(files) {
            if (!photosModalId || !files.length) return;

            const formData = new FormData();
            formData.append('zgloszenie_id', photosModalId);
            for (const file of files) {
                formData.append('photos[]', file);
            }

            showUploading(true);
            photosModalInput.disabled = true;
            cameraModalInput.disabled = true;
            try {
                const res = await apiFetch(API_ZDJECIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się wgrać zdjęć.');

                const item = db.find(item => item.id === photosModalId);
                if (item) {
                    item.photos = (item.photos || []).concat(data.data);
                    renderPhotosGrid(item.photos);
                    renderServicesList();
                }
                // Po wgrywaniu: komunikat; gdy próg80% przekroczony — jeden
                // złożony toast z procentem i zapasem (nowy toast kasuje stary)
                const st = await photoStatsNow();
                const warn = st ? fotoWarnText(st.bytes) : null;
                loadPhotoStats(); // odśwież info w statystykach
                if (warn) {
                    showToast(`Dodano zdjęć: ${data.data.length}. ⚠ ${warn}`, 'error');
                } else {
                    showToast(`Dodano zdjęć: ${data.data.length}`);
                }
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                showUploading(false);
                photosModalInput.disabled = false;
                cameraModalInput.disabled = false;
            }
        }

        photosModalInput.addEventListener('change', () => {
            const files = Array.from(photosModalInput.files);
            photosModalInput.value = '';
            uploadModalPhotos(files);
        });

        cameraModalInput.addEventListener('change', () => {
            const files = Array.from(cameraModalInput.files);
            cameraModalInput.value = '';
            uploadModalPhotos(files);
        });

        window.deletePhoto = async function(photoId) {
            const confirmed = await showConfirmModal(
                'Usuń zdjęcie',
                'Czy na pewno chcesz usunąć to zdjęcie? Zostanie trwale usunięte z serwera, a operacji nie można cofnąć.',
                'Usuń'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZDJECIA}?id=${encodeURIComponent(photoId)}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć zdjęcia.');

                const item = db.find(item => item.id === photosModalId);
                if (item) {
                    item.photos = (item.photos || []).filter(p => p.id !== photoId);
                    renderPhotosGrid(item.photos);
                }
                renderServicesList();
                loadPhotoStats(); // zużycie spadło — odśwież ostrzeżenie80%
                showToast('Usunięto zdjęcie.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        closePhotosBtn.addEventListener('click', () => {
            photosModal.classList.remove('active');
            photosModalId = null;
        });

        photosModal.addEventListener('click', (e) => {
            if (e.target === photosModal) {
                photosModal.classList.remove('active');
                photosModalId = null;
            }
        });

        // --- LIGHTBOX ---
        window.openLightbox = function(url) {
            lightboxImg.src = url;
            lightbox.classList.add('active');
        };

        lightbox.addEventListener('click', () => {
            lightbox.classList.remove('active');
            lightboxImg.src = '';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                lightbox.classList.remove('active');
                lightboxImg.src = '';
                photosModal.classList.remove('active');
                photosModalId = null;
                closeConfirmModal(false);
                if (typeof closeEditModal === 'function') closeEditModal();
                if (typeof closeDetailModal === 'function') closeDetailModal();
                if (typeof stopScanner === 'function') stopScanner();
                if (typeof calendarModal !== 'undefined') calendarModal.classList.remove('active');
                if (typeof calHidePopover === 'function') calHidePopover();
            }
        });

        // --- OBSŁUGA FILTRÓW I WYSZUKIWARKI ---
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderServicesList();
            scheduleMobileCard();
        });

        // --- MOBILE: lista ukryta, więc wynik wyszukiwania pokazuje karta zgłoszenia ---
        let mobileCardTimer = null;

        function openSearchCard() {
            if (!IS_MOBILE) return;
            const query = searchQuery.trim();
            // Na telefonie zobowiazujemy min. 4 znaki numeru serwisowego
            // i pokazujemy tylko to jedno zgloszenie (zero dopasowan = nic).
            if (query.length < 4) return;
            const q = query.toLowerCase();
            const byNo = db.filter(item => !item.deleted
                && (item.serviceNo || '').toLowerCase().includes(q));

            if (byNo.length === 0) {
                showToast(`Brak zgłoszenia o numerze ${query}.`, 'error');
                return;
            }
            const exact = byNo.find(item => (item.serviceNo || '').toLowerCase() === q);
            if (exact) {
                openDetailModal(exact.id);
            } else if (byNo.length === 1) {
                openDetailModal(byNo[0].id);
            } else {
                // Kilka dopasowan: nie otwieramy karty - wiecej cyfr numeru
                showToast(`Znaleziono ${byNo.length} zgłoszeń — wpisz więcej cyfr numeru.`, 'info');
            }
        }

        function scheduleMobileCard() {
            if (!IS_MOBILE) return;
            clearTimeout(mobileCardTimer);
            mobileCardTimer = setTimeout(openSearchCard, 700);
        }

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(mobileCardTimer);
                openSearchCard();
            }
        });

        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                renderServicesList();
            });
        });

        // Kafle dashboardu = skrót do filtrów (klik przewija do listy)
        document.getElementById('dash-grid').addEventListener('click', (e) => {
            const tile = e.target.closest('.dash-tile');
            if (!tile) return;
            currentFilter = tile.dataset.filter;
            filterButtons.forEach(btn =>
                btn.classList.toggle('active', btn.dataset.filter === currentFilter));
            renderServicesList();
            servicesListContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Sortowanie listy (preferencja zapisywana lokalnie)
        const sortSelect = document.getElementById('sort-select');
        try {
            const saved = localStorage.getItem('re_sort');
            if (saved && sortSelect.querySelector(`option[value="${saved}"]`)) currentSort = saved;
        } catch (e) { /* localStorage niedostępne */ }
        if (sortSelect) {
            sortSelect.value = currentSort;
            sortSelect.addEventListener('change', () => {
                currentSort = sortSelect.value;
                try { localStorage.setItem('re_sort', currentSort); } catch (e) { /* ignore */ }
                renderServicesList();
            });
        }

        // --- OBSŁUGA SETTINGS MODAL ---
        openSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.add('active');
            loadPhotoStats();
            syncModuleToggles();   // zakładka Moduły: odbij aktualne flagi
        });

        closeSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.remove('active');
            passwordHintEl.textContent = '';
        });

        // Zamknięcie modala po kliknięciu na tło
        settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                settingsModal.classList.remove('active');
                passwordHintEl.textContent = '';
            }
        });

        // PRZEŁĄCZANIE ZAKŁADEK USTAWIEŃ
        function switchSettingsTab(tabName) {
            // Zakładka usług nie istnieje, gdy moduł wyłączony
            if (tabName === 'uslugi' && !modulOn('uslugi')) tabName = 'general';
            tabGeneralBtn.classList.toggle('active', tabName === 'general');
            tabUslugiBtn.classList.toggle('active', tabName === 'uslugi');
            tabModulyBtn.classList.toggle('active', tabName === 'moduly');
            tabGeneralContent.hidden = tabName !== 'general';
            tabUslugiContent.hidden = tabName !== 'uslugi';
            tabModulyContent.hidden = tabName !== 'moduly';
            if (tabName === 'uslugi') renderServiceList();
            if (tabName === 'moduly') syncModuleToggles();
        }

        tabGeneralBtn.addEventListener('click', () => switchSettingsTab('general'));
        tabUslugiBtn.addEventListener('click', () => switchSettingsTab('uslugi'));
        tabModulyBtn.addEventListener('click', () => switchSettingsTab('moduly'));

        // --- MODUŁY: przełączniki w zakładce Ustawienia -> Moduły ---
        const MODUL_NAZWA = {
            kalendarz: 'Kalendarz', zdjecia: 'Zdjęcia', skaner: 'Skaner QR',
            uslugi: 'Katalog usług', druk: 'Drukowanie', kosz: 'Kosz',
            kolorystyka: 'Kolorystyka', powitanie: 'Powitanie', karta_wydania: 'Karta wydania',
            wykonane: 'Wykonane czynności'
        };
        const modToggles = document.querySelectorAll('.mod-toggle');
        const modulyHint = document.getElementById('moduly-hint');

        function syncModuleToggles() {
            modToggles.forEach(cb => { cb.checked = modulOn(cb.dataset.mod); });
            modulyHint.textContent = '';
        }

        modToggles.forEach(cb => cb.addEventListener('change', async () => {
            const nazwa = cb.dataset.mod;
            const poprzedni = MODULY[nazwa];
            MODULY[nazwa] = cb.checked;
            modulyHint.textContent = 'Zapisywanie…';

            try {
                await saveModules();
                applyModules();
                renderServicesList();
                updateDashboard();

                // Włączenie kalendarza: przywróć domyślny termin, gdy pusty
                if (nazwa === 'kalendarz' && cb.checked && !datePlannedInput.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 3);
                    datePlannedInput.value = formatDateForInput(d);
                }

                modulyHint.textContent = `Moduł „${MODUL_NAZWA[nazwa] || nazwa}” `
                    + (cb.checked ? 'włączony.' : 'wyłączony.');
                showToast('Zapisano ustawienia modułów.');
            } catch (err) {
                MODULY[nazwa] = poprzedni;
                cb.checked = poprzedni;
                modulyHint.textContent = '';
                showToast(err.message, 'error');
            }
        }));

        // --- USŁUGI (katalog usług w checkboxach) ---
        let services = [];

        async function loadServices() {
            try {
                const res = await apiFetch(API_USLUGI);
                const data = await res.json();
                if (data.success) {
                    services = data.data;
                    renderFormServiceCheckboxes();
                    renderServiceList();
                }
            } catch (err) {
                showToast('Nie udało się pobrać listy usług.', 'error');
            }
        }

        // Checkboxy pod polem "Opis usterki"
        function renderFormServiceCheckboxes() {
            serviceCheckboxList.innerHTML = '';
            if (!services.length) {
                serviceCheckboxList.innerHTML = '<p class="service-checkbox-empty">Brak skonfigurowanych usług — dodaj je w Ustawieniach.</p>';
                return;
            }
            services.forEach(item => {
                const label = document.createElement('label');
                label.className = 'service-check';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'service-checkbox';
                cb.value = item.nazwa;
                label.appendChild(cb);
                label.appendChild(document.createTextNode(' ' + item.nazwa));
                serviceCheckboxList.appendChild(label);
            });
        }

        // Lista usług w zakładce "Dodaj usługi"
        function renderServiceList() {
            serviceListEl.innerHTML = '';
            if (!services.length) {
                serviceListEl.innerHTML = '<li class="service-list-empty">Brak dodanych usług.</li>';
                return;
            }
            services.forEach(item => {
                const li = document.createElement('li');
                const span = document.createElement('span');
                span.textContent = item.nazwa;
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'remove-service';
                rm.title = 'Usuń usługę';
                rm.textContent = '×';
                rm.addEventListener('click', () => removeService(item.id));
                li.appendChild(span);
                li.appendChild(rm);
                serviceListEl.appendChild(li);
            });
        }

        async function removeService(id) {
            const service = services.find(s => s.id === id);
            const confirmed = await showConfirmModal(
                'Usuń usługę',
                `Czy na pewno chcesz usunąć usługę „${service ? service.nazwa : ''}” z listy? Zniknie również z checkboxów w formularzu zgłoszenia.`,
                'Usuń'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(API_USLUGI, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=remove&id=' + encodeURIComponent(id)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć usługi.');
                services = data.data;
                renderFormServiceCheckboxes();
                renderServiceList();
                showToast('Usunięto usługę.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        }

        addServiceBtn.addEventListener('click', addService);
        newServiceInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addService();
            }
        });

        async function addService() {
            const nazwa = newServiceInput.value.trim();
            if (!nazwa) {
                showToast('Wpisz nazwę usługi.', 'info');
                newServiceInput.focus();
                return;
            }
            addServiceBtn.disabled = true;
            try {
                const res = await apiFetch(API_USLUGI, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=add&nazwa=' + encodeURIComponent(nazwa)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się dodać usługi.');
                services = data.data;
                newServiceInput.value = '';
                renderFormServiceCheckboxes();
                renderServiceList();
                showToast('Dodano usługę!');
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                addServiceBtn.disabled = false;
            }
        }

        // STATYSTYKI ZDJĘĆ
        // Łączny limit pojemności na zdjęcia (MAX_PHOTOS_TOTAL_BYTES z config.php)
        // — widoczny przy statystykach w Ustawieniach -> Ogólne
        const FOTO_LIMIT_MB = <?= (int) round(MAX_PHOTOS_TOTAL_BYTES / 1048576) ?>;
        // Próg ostrzegawczy: od >=80% limitu komunikat po wgrywaniu
        // i czerwone info w statystykach
        const FOTO_WARN_PCT = 80;

        // Tekst ostrzeżenia o zbliżaniu się do limitu (null = poniżej progu)
        function fotoWarnText(bytes) {
            if (!FOTO_LIMIT_MB) return null;
            const limitBytes = FOTO_LIMIT_MB * 1024 * 1024;
            const pct = Math.round(bytes / limitBytes * 100);
            if (pct < FOTO_WARN_PCT) return null;
            return 'Zdjęcia: zużyto ' + pct + '% limitu ' + FOTO_LIMIT_MB
                + ' MB — zostało ' + formatBytes(Math.max(0, limitBytes - bytes))
                + '. Usuń część starych zdjęć.';
        }

        // Bieżące zużycie prosto z API (do ostrzeżeń po wgrywaniu)
        async function photoStatsNow() {
            try {
                const res = await apiFetch(API_KONTO);
                const data = await res.json();
                if (!data.success) return null;
                return { photos: Number(data.data.photos) || 0, bytes: Number(data.data.bytes) || 0 };
            } catch (e) {
                return null;
            }
        }

        async function loadPhotoStats() {
            statsPhotosEl.textContent = '…';
            statsSizeEl.textContent = '…';
            try {
                const res = await apiFetch(API_KONTO);
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Błąd statystyk');
                const photos = Number(data.data.photos) || 0;
                const bytes = Number(data.data.bytes) || 0;
                statsPhotosEl.textContent = String(photos);
                statsSizeEl.textContent = formatBytes(bytes) + ' / ' + FOTO_LIMIT_MB + ' MB';
                // Info o zużyciu >= progu ostrzegawczego (80% limitu)
                const warn = fotoWarnText(bytes);
                statsWarnEl.hidden = !warn;
                statsWarnEl.textContent = warn ? '⚠ ' + warn : '';
                statsSizeEl.style.color = warn ? 'var(--danger)' : '';
            } catch (err) {
                statsPhotosEl.textContent = '—';
                statsSizeEl.textContent = '—';
                statsWarnEl.hidden = true;
                showToast('Nie udało się pobrać statystyk.', 'error');
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 MB';
            const mb = bytes / (1024 * 1024);
            if (mb >= 1) return mb >= 100 ? Math.round(mb) + ' MB' : mb.toFixed(1).replace('.', ',') + ' MB';
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        refreshStatsBtn.addEventListener('click', loadPhotoStats);

        // ZMIANA HASŁA
        savePasswordBtn.addEventListener('click', async () => {
            const current = currentPasswordInput.value;
            const next = newPasswordInput.value;
            const confirmPass = confirmPasswordInput.value;

            if (!current || !next || !confirmPass) {
                passwordHintEl.textContent = 'Uzupełnij wszystkie pola.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }
            if (next.length < 6) {
                passwordHintEl.textContent = 'Nowe hasło musi mieć min. 6 znaków.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }
            if (next !== confirmPass) {
                passwordHintEl.textContent = 'Nowe hasła nie są identyczne.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }

            passwordHintEl.textContent = 'Zapisywanie…';
            passwordHintEl.style.color = 'var(--text-secondary)';
            savePasswordBtn.disabled = true;

            try {
                const res = await fetch(API_KONTO, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=password&current=' + encodeURIComponent(current) + '&next=' + encodeURIComponent(next),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    passwordHintEl.textContent = 'Hasło zostało zmienione.';
                    passwordHintEl.style.color = 'var(--success)';
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmPasswordInput.value = '';
                    showToast('Hasło zmienione!');
                } else {
                    passwordHintEl.textContent = data.error || 'Nie udało się zmienić hasła.';
                    passwordHintEl.style.color = 'var(--danger)';
                }
            } catch (err) {
                passwordHintEl.textContent = 'Błąd połączenia.';
                passwordHintEl.style.color = 'var(--danger)';
            } finally {
                savePasswordBtn.disabled = false;
            }
        });

        // Wylogowanie (koniec sesji dziennej)
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                window.location.href = window.location.pathname + '?logout=1';
            });
        }
    </script>
</body>
</html>