<?php defined('SERWIS_PANEL') or exit; ?>
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
                Sprzęt zaplanowany do odbioru:
            </p>

            <div class="welcome-grid">
                <div class="welcome-stat">
                    <strong id="welcome-today">–</strong><span>na dziś</span>
                    <!-- 3.9: rozbicie na typy (modul "hulajnogi") - patrz formularz.js -->
                    <small class="welcome-split" id="welcome-today-split" hidden></small>
                </div>
                <div class="welcome-stat">
                    <strong id="welcome-tomorrow">–</strong><span>na jutro</span>
                    <small class="welcome-split" id="welcome-tomorrow-split" hidden></small>
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
                    <label for="edit-bike-name" id="edit-bike-name-label">Nazwa roweru</label>
                    <input type="text" id="edit-bike-name" required>
                </div>
                <!-- 3.9: numer seryjny - tylko przy zgloszeniu hulajnogi -->
                <div class="form-group" id="edit-serial-group" hidden>
                    <label for="edit-serial">Numer seryjny (opcjonalny)</label>
                    <input type="text" id="edit-serial" maxlength="64" autocomplete="off">
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
            <p class="scan-hint" id="scan-hint">Skieruj aparat na kod QR z numerem serwisowym (naklejka na sprzęcie).</p>
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

    <!-- MODAL AKTUALIZACJI (v2): potwierdzenie + postęp -->
    <div class="modal-overlay" id="update-modal">
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-header">
                <h3 style="font-size: 1.15rem;">Aktualizacja panelu</h3>
                <button class="modal-close" id="update-modal-close">&times;</button>
            </div>
            <div id="update-modal-body">
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem;">
                    Dostępna jest nowsza wersja panelu.
                    Przed aktualizacją zostanie utworzona kopia zapasowa plików.
                </p>
                <p id="update-modal-versions" style="font-size: 0.9rem; margin-bottom: 1.5rem;"></p>
            </div>
            <div id="update-modal-progress" hidden style="margin-bottom: 1rem;">
                <p id="update-progress-text" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Pobieranie…</p>
                <div style="height: 6px; background: var(--border); border-radius: 3px; overflow: hidden;">
                    <div id="update-progress-bar" style="height: 100%; width: 0%; background: var(--primary); transition: width 0.3s;"></div>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button class="btn btn-secondary" id="update-modal-cancel" style="flex: 1 1 0; min-width: 0;">Później</button>
                <button class="btn btn-primary" id="update-modal-ok" style="flex: 1 1 0; min-width: 0;">Zaktualizuj</button>
            </div>
        </div>
    </div>

