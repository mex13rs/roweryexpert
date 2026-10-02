<?php defined('SERWIS_PANEL') or exit; ?>
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
                <span class="d-label" id="detail-bike-label">Rower:</span>
                <span class="d-val" id="detail-bike-name">—</span>
                <!-- 3.9: numer seryjny - widoczny tylko przy hulajnodze z numerem -->
                <span class="d-label" id="detail-serial-label" hidden>Numer seryjny:</span>
                <span class="d-val" id="detail-serial" hidden>—</span>
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
                <span class="d-label" id="detail-created-label">Założył:</span>
                <span class="d-val" id="detail-created-by">—</span>
                <span class="d-label" id="detail-issued-label">Wydanie:</span>
                <span class="d-val" id="detail-confirmed-by">—</span>
            </div>
            <div class="pending-detail" id="detail-pending" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span id="detail-pending-text"></span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-primary" id="detail-issue-btn">Wydaj rower</button>
                <!-- tekst przycisku ustala karta.js wg typu zgloszenia -->
                <button type="button" class="btn btn-secondary" id="detail-cancel-btn">Zamknij</button>
            </div>
        </div>
    </div>

