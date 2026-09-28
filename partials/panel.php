<?php defined('SERWIS_PANEL') or exit; ?>
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
                    <!-- Global Settings Button -->
                    <button class="btn-icon" id="open-settings-btn" title="Ustawienia">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 22px; height: 22px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                    <!-- Zalogowane konto — trybik ustawień przeniesiony obok (3.7); klik w nazwę otwiera ustawienia -->
                    <span class="header-user" id="current-user" role="button" tabindex="0"
                          title="Kliknij, aby otworzyć ustawienia"><?= htmlspecialchars($currentUser['login'] ?? '') ?></span>
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
                            <input type="tel" id="customer-phone" placeholder="np. 500 600 700 lub 500600700" required>
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
                            <button class="filter-btn" data-filter="mine">Moje</button>
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
                                <option value="user">Wg użytkownika (kto założył)</option>
                                <option value="issuer">Wg wydającego</option>
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

