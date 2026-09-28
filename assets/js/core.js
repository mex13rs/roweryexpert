        // --- INICJALIZACJA STANU ---
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
        const API_UZYTKOWNICY = 'api/uzytkownicy.php';

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
        const tabSerwisBtn = document.getElementById('tab-btn-serwis');
        const tabUslugiBtn = document.getElementById('tab-btn-uslugi');
        const tabModulyBtn = document.getElementById('tab-btn-moduly');
        const tabGeneralContent = document.getElementById('tab-content-general');
        const tabSerwisContent = document.getElementById('tab-content-serwis');
        const tabUslugiContent = document.getElementById('tab-content-uslugi');
        const tabModulyContent = document.getElementById('tab-content-moduly');
        const tabUsersBtn = document.getElementById('tab-btn-users');
        const tabUsersContent = document.getElementById('tab-content-users');
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

