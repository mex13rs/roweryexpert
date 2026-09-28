<?php defined('SERWIS_PANEL') or exit; ?>
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
                    <td class="receipt-value" id="print-customer-phone">500-600-700</td>
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
                    RoweryExpert <?= dane_instancji()['adres'] ?>
                    <?= dane_instancji()['miasto'] ?> tel. <?= dane_instancji()['telefon'] ?>
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
                    <td class="receipt-value" id="print-customer-phone-service">500-600-700</td>
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

