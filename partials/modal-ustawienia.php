<?php defined('SERWIS_PANEL') or exit; $isAdmin = (($currentUser['rola'] ?? '') === 'admin'); ?>
    <!-- MODAL USTAWIEŃ -->
    <div class="modal-overlay" id="settings-modal">
        <div class="modal-card" style="max-width: 560px;">
            <div class="modal-header">
                <h3 style="font-size: 1.25rem;">Ustawienia</h3>
                <button class="modal-close" id="close-settings-btn">&times;</button>
            </div>

            <div class="settings-tabs">
                <button class="settings-tab active" id="tab-btn-general" data-tab="general">Ogólne</button>
                <?php if ($isAdmin): ?>
                <button class="settings-tab" id="tab-btn-serwis" data-tab="serwis">Dane serwisu</button>
                <button class="settings-tab" id="tab-btn-uslugi" data-tab="uslugi">Dodaj usługi</button>
                <button class="settings-tab" id="tab-btn-moduly" data-tab="moduly">Moduły</button>
                <button class="settings-tab" id="tab-btn-users" data-tab="users">Użytkownicy</button>
                <?php endif; ?>
            </div>

            <!-- ZAKŁADKA: OGÓLNE -->
            <div class="settings-tab-content" id="tab-content-general">
                <?php if ($isAdmin): ?>
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
                <?php endif; // statystyki i limit zdjęć tylko dla admina ?>

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

            <?php if ($isAdmin): ?>
            <!-- ZAKŁADKA: DANE SERWISU (edycja po instalacji) -->
            <div class="settings-tab-content" id="tab-content-serwis" hidden>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0 0 1rem;">
                    Dane trafiają na <strong>potwierdzenie zlecenia (wydruk A4)</strong> oraz do kodu QR
                    „Oceń nas". Zmiana zapisuje się natychmiast — nie trzeba przeinstalowywać panelu.
                    Nazwa <strong>RoweryExpert</strong> jest stała (sieć serwisów).
                </p>
                <div class="form-group">
                    <label for="inst-adres">Ulica</label>
                    <input type="text" id="inst-adres" maxlength="120" placeholder="np. Ostrobramska 81">
                </div>
                <div class="form-group">
                    <label for="inst-miasto">Kod pocztowy i miasto</label>
                    <input type="text" id="inst-miasto" maxlength="120" placeholder="np. 04-175 Warszawa">
                </div>
                <div class="form-group">
                    <label for="inst-telefon">Telefon serwisu (na wydruku)</label>
                    <input type="text" id="inst-telefon" maxlength="32" placeholder="np. 532-561-152">
                </div>
                <div class="form-group">
                    <label for="inst-maps">Link do wizytówki Google (źródło QR „Oceń nas")</label>
                    <input type="url" id="inst-maps" maxlength="255" placeholder="https://maps.app.goo.gl/…">
                </div>
                <div class="form-group">
                    <label for="inst-site">Adres URL panelu (opcjonalnie)</label>
                    <input type="url" id="inst-site" maxlength="255" placeholder="https://serwis.twojadomena.pl">
                </div>
                <div class="form-group">
                    <label for="inst-reset-email">E-mail do resetu hasła administratora</label>
                    <input type="email" id="inst-reset-email" maxlength="255" placeholder="np. serwis@twojadomena.pl">
                    <p class="settings-hint" style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 0.4rem;">
                        Na ten adres przyjdzie nowe hasło, gdy ktoś kliknie „Nie pamiętam hasła”
                        przy logowaniu. Hasło zmieni się dopiero po kliknięciu linku w mailu.
                    </p>
                </div>
                <button class="btn btn-primary" id="save-inst-btn">Zapisz dane serwisu</button>
                <p class="settings-hint" id="inst-hint" style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.75rem;"></p>
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
            <?php endif; // katalog usług i moduły tylko dla admina ?>

            <!-- ZAKŁADKA: UŻYTKOWNICY (3.1, tylko admin) -->
            <?php if ($isAdmin): ?>
            <div class="settings-tab-content" id="tab-content-users" hidden>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0 0 1rem;">
                    Konta pracowników serwisu. Każdy loguje się swoim loginem i hasłem.
                    Zapomniane hasło resetujesz tutaj — konto zostanie wtedy wylogowane
                    na wszystkich urządzeniach.
                </p>

                <label>Nowe konto</label>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="new-user-login">Login (np. imię)</label>
                        <input type="text" id="new-user-login" autocomplete="off" placeholder="np. marek">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="new-user-rola">Rola</label>
                        <select id="new-user-rola" class="sort-select" style="width: 100%;">
                            <option value="pracownik">pracownik</option>
                            <option value="admin">administrator</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new-user-pass">Hasło startowe (min. 6 znaków)</label>
                    <input type="password" id="new-user-pass" autocomplete="new-password">
                </div>
                <button class="btn btn-primary" id="add-user-btn">Dodaj konto</button>
                <p class="settings-hint" id="users-hint" style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.75rem;"></p>

                <div id="users-list" data-me="<?= (int) ($currentUser['id'] ?? 0) ?>"
                     style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.6rem;"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL KONTA UŻYTKOWNIKA (3.3) — szczegóły i akcje w osobnej karcie,
         jak karta zgłoszenia; lista to tylko podgląd -->
    <?php if ($isAdmin): ?>
    <div class="modal-overlay" id="user-modal">
        <div class="modal-card" style="max-width: 520px;">
            <div class="modal-header">
                <h3 style="font-size: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
                    <span id="user-modal-login">—</span>
                    <span id="user-modal-badge"></span>
                </h3>
                <button class="modal-close" id="user-modal-close">&times;</button>
            </div>

            <div class="detail-grid">
                <span class="d-label">Status:</span>
                <span class="d-val" id="user-modal-status">—</span>
                <span class="d-label">Ostatnie logowanie:</span>
                <span class="d-val" id="user-modal-last">—</span>
                <span class="d-label">Konto utworzone:</span>
                <span class="d-val" id="user-modal-created">—</span>
                <span class="d-label">Hasło:</span>
                <span class="d-val" id="user-modal-passflag">—</span>
                <span class="d-label">Założył zgłoszeń:</span>
                <span class="d-val" id="user-modal-zgloszenia">0</span>
                <span class="d-label">Wydanych rowerów:</span>
                <span class="d-val" id="user-modal-wydane">0</span>
                <span class="d-label">Rola:</span>
                <span class="d-val">
                    <select id="user-modal-rola" class="sort-select" style="max-width: 100%;">
                        <option value="pracownik">pracownik</option>
                        <option value="admin">administrator</option>
                    </select>
                </span>
            </div>

            <div class="form-group" style="margin-top: 1.25rem;">
                <label for="user-modal-pass-input">Reset hasła (min. 6 znaków)</label>
                <div class="service-add-row">
                    <input type="password" id="user-modal-pass-input" autocomplete="new-password"
                           placeholder="nowe hasło">
                    <button class="btn btn-primary" id="user-modal-pass-save">Zapisz hasło</button>
                </div>
                <p class="settings-hint" id="user-modal-hint"
                   style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;"></p>
            </div>

            <div class="button-group">
                <button class="btn btn-primary" id="user-modal-toggle">Wyłącz konto</button>
                <button class="btn btn-danger" id="user-modal-delete">Usuń konto</button>
                <button class="btn btn-secondary" id="user-modal-cancel">Zamknij</button>
            </div>
        </div>
    </div>
    <?php endif; // user-modal tylko dla admina ?>

