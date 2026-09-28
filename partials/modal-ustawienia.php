<?php defined('SERWIS_PANEL') or exit; ?>
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

