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
    <link rel="stylesheet" href="assets/css/panel.css?v=<?= APP_VERSION ?>">
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
                    <label for="login-user">Login</label>
                    <input type="text" id="login-user" name="login" placeholder="np. marek" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="login-password">Hasło</label>
                    <input type="password" id="login-password" name="password" placeholder="******" required autocomplete="current-password">
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
                    <!-- Zalogowane konto -->
                    <span class="header-user" id="current-user" title="Zalogowane konto"><?= htmlspecialchars($currentUser['login'] ?? '') ?></span>
                    <!-- Logout Button -->
                    <button class="btn-icon" id="logout-btn" title="Wyloguj się (<?= htmlspecialchars($currentUser['login'] ?? '') ?>)">
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

                <h4 style="margin: 0 0 1rem; font-size: 1rem;">Zmiana hasła do konta</h4>
                <p class="settings-hint" style="font-size: 0.85rem; color: var(--text-secondary); margin: -0.5rem 0 1rem;">
                    Zalogowano jako <strong><?= htmlspecialchars($currentUser['login'] ?? '') ?></strong>
                    (<?= ($currentUser['rola'] ?? '') === 'admin' ? 'administrator' : 'pracownik' ?>).
                    Zapomniałeś hasła? Poproś administratora o reset.
                </p>
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
    const IS_AUTHENTICATED = <?= $authenticated ? 'true' : 'false' ?>;
    const FOTO_LIMIT_MB = <?= (int) round(MAX_PHOTOS_TOTAL_BYTES / 1048576) ?>;
    </script>
    <script src="assets/js/core.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/motyw.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/api.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/druk.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/formularz.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/lista.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/karta.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/skaner.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/kalendarz.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/zdjecia.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/filtry.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/ustawienia.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>