<?php defined('SERWIS_PANEL') or exit; ?>
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

