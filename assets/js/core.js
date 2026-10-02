        // --- INICJALIZACJA STANU ---
        let db = []; // Zgłoszenia ładowane z API/PHP i bazy MySQL
        let currentFilter = 'all';
        let currentSort = 'planned_asc';   // domyślnie: najbliższy termin odbioru na górze
        let searchQuery = '';
        let filterUserId = null;   // 3.8.8: ikonka użytkownika = filtr po autorze (null = wszyscy)
        let filterTyp = 'all';     // 3.9: filtr typu sprzetu na liscie: all | rower | hulajnogi

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
        // więc PC i telefon widzą to samo. Domyślnie wszystko włączone
        // (hulajnogi startują wyłączone - patrz modul_domyslnie w config.php).
        let MODULY = { kalendarz: true, zdjecia: true, skaner: true, uslugi: true, druk: true, kosz: true, kolorystyka: true, powitanie: true, karta_wydania: true, wykonane: true, hulajnogi: false };

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
            // 3.9: wylaczony modul hulajnogi = wylaczony filtr typu
            if (!modulOn('hulajnogi') && filterTyp !== 'all') filterTyp = 'all';

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
            // (tylko .filters - .svc-typ-btn w Ustawieniach tez ma filter-btn,
            //  ale ma wlasna klase active zarzadzana przez syncSvcTypSeg)
            document.querySelectorAll('.filters .filter-btn:not(.typ-btn)').forEach(btn =>
                btn.classList.toggle('active', btn.dataset.filter === currentFilter));
            document.querySelectorAll('.dash-tile').forEach(tile =>
                tile.classList.toggle('active', tile.dataset.filter === currentFilter));
            document.querySelectorAll('.typ-btn').forEach(btn =>
                btn.classList.toggle('active', btn.dataset.typFilter === filterTyp));

            // 3.9: wybor typu sprzetu + blokada formularza (formularz.js)
            if (typeof zastosujTypSprzetu === 'function') zastosujTypSprzetu();
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
        // .typ-btn (filtr rower/hulajnoga) ma wlasna obsluge - patrz filtry.js
        const filterButtons = document.querySelectorAll('.filters .filter-btn:not(.typ-btn)');
        
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

        // --- AUTO-ODŚWIEŻANIE POLLINGIEM (znacznik zmian co 10 s) ---
        // Komputer z otwartym panelem sam pokaże zmiany zrobione gdzie indziej
        // (np. przyjęcie na telefonie) - bez F5. Odpytujemy lekki znacznik
        // (?stamp=1: liczba rekordów + najnowszy updated_at + liczba zdjęć);
        // lista przemalowuje się tylko przy realnej zmianie w bazie i nigdy
        // wtedy, gdy ktoś pracuje w otwartym oknie albo trwa zapis.
        const POLL_MS = 10000;
        let pollTimer = null;
        let pollLastStamp = null;
        let pollBusy = false;

        async function pollGetStamp() {
            const res = await apiFetch(API_ZGLOSZENIA + '?stamp=1');
            const data = await res.json();
            if (!data.success || !data.data || !data.data.stamp) {
                throw new Error('Brak znacznika zmian.');
            }
            return data.data.stamp;
        }

        // Otwarte okno (karta, edycja, kalendarz, monit po przyjęciu) albo
        // trwający zapis = nie przemalowujemy listy pod pracownikiem
        function panelZapelniony() {
            const overlay = document.getElementById('upload-overlay');
            return !!document.querySelector('.modal-overlay.active, .modal-overlay.leaving')
                || !!(overlay && overlay.classList.contains('active'));
        }

        // Pobranie świeżej listy i przemalowanie widoków (bez reloadu strony -
        // wypełniany formularz i dane logowania zostają nietknięte)
        async function pollOdswiezListe() {
            const res = await apiFetch(API_ZGLOSZENIA);
            const data = await res.json();
            if (!data.success) return;
            db = data.data;
            renderServicesList();
            if (modulOn('kalendarz')) renderCalendar();
        }

        async function pollTick() {
            if (pollBusy || document.hidden) return;
            pollBusy = true;
            try {
                const stamp = await pollGetStamp();
                if (pollLastStamp === null) {
                    pollLastStamp = stamp; // pierwszy odczyt = punkt odniesienia
                } else if (stamp !== pollLastStamp) {
                    // Przy otwartym oknie NIE aktualizujemy pollLastStamp:
                    // zaległość odrobimy przy najbliższym ticku po zamknięciu
                    if (panelZapelniony()) return;
                    pollLastStamp = stamp;
                    await pollOdswiezListe();
                }
            } catch (err) {
                // Chwilowy brak sieci albo wygasła sesja (401 obsłuży apiFetch
                // z komunikatem) - spróbujemy przy najbliższym ticku
            } finally {
                pollBusy = false;
            }
        }

        function startPolling() {
            if (pollTimer || !IS_AUTHENTICATED) return;
            pollTimer = setInterval(pollTick, POLL_MS);
            pollTick();
        }

        // Karta w tle = bez zapytań do hostingu; powrót na plan = natychmiast
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(pollTimer);
                pollTimer = null;
            } else {
                startPolling();
            }
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

            // Aktualizacje (v2): sprawdź dostępność przy starcie (check raz/24h po stronie PHP)
            checkForUpdate();

            // Auto-odświeżanie: pierwszy odczyt znacznika + start co 10 s,
            // żeby panel sam pokazywał zmiany zrobione na innym urządzeniu
            startPolling();
        });

        // --- AKTUALIZACJE (v2): baner + modal + wykonywanie ---
        const updateBanner = document.getElementById('update-banner');
        const updateBannerText = document.getElementById('update-banner-text');
        const updateNowBtn = document.getElementById('update-now-btn');
        const updateModal = document.getElementById('update-modal');
        const updateModalVersions = document.getElementById('update-modal-versions');
        const updateModalProgress = document.getElementById('update-modal-progress');
        const updateProgressText = document.getElementById('update-progress-text');
        const updateProgressBar = document.getElementById('update-progress-bar');
        const updateModalOk = document.getElementById('update-modal-ok');
        const updateModalCancel = document.getElementById('update-modal-cancel');
        const updateModalClose = document.getElementById('update-modal-close');
        let updateData = null;

        // 3.8.8: przycisk "Aktualizuj" w Ustawieniach aktywny tylko wtedy,
        // gdy którykolwiek check (startowy lub ręczny) znalazł nową wersję
        function syncUpdateApplyBtn() {
            const b = document.getElementById('update-apply-btn');
            if (b) b.disabled = !(updateData && updateData.dostepna);
        }

        // Startowy check (cache 24h) - jako JEDYNY źródło żółtego banera
        async function checkForUpdate() {
            if (!updateBanner) return;
            try {
                const res = await apiFetch(API_USTAWIENIA);
                const data = await res.json();
                if (!data.success || !data.data.update) return;
                updateData = data.data.update;
                syncUpdateApplyBtn();
                if (!updateData.dostepna) return;

                const isAdmin = (document.getElementById('current-user')?.dataset.rola === 'admin');
                if (isAdmin) {
                    updateBannerText.textContent = 'Dostępna nowsza wersja: ' + updateData.nowa_wersja
                        + ' (masz ' + updateData.obecna_wersja + ')';
                    updateNowBtn.hidden = false;
                } else {
                    updateBannerText.textContent = 'Dostępna nowsza wersja panelu — powiadom administratora.';
                }
                updateBanner.hidden = false;
            } catch (e) {
                /* brak połączenia z API — baner pozostaje ukryty */
            }
        }

        // Wymuszone sprawdzenie (przycisk "Sprawdź aktualizacje") — omija cache 24h.
        // 3.8.8: NIE rusza banera na górze (to wyłącznie automatyczne wykrycie
        // przy starcie) — resultat = toast + włączenie "Aktualizuj" obok
        async function forceCheckUpdate() {
            const btn = document.getElementById('update-check-btn');
            if (!btn) return;
            btn.disabled = true;
            btn.textContent = 'Sprawdzanie…';
            try {
                const res = await apiFetch(API_USTAWIENIA, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=check_update',
                });
                const data = await res.json();
                if (data.success && data.data.update) {
                    updateData = data.data.update;
                    syncUpdateApplyBtn();
                    if (updateData.dostepna) {
                        showToast('Dostępna wersja ' + updateData.nowa_wersja + ' — kliknij „Aktualizuj".');
                    } else {
                        showToast('Brak nowych aktualizacji.');
                    }
                } else {
                    showToast(data.error || 'Nie udało się sprawdzić aktualizacji.', 'error');
                }
            } catch (e) {
                showToast('Błąd połączenia podczas sprawdzania.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Sprawdź aktualizacje';
            }
        }

        function openUpdateModal() {
            if (!updateModal || !updateData) return;
            updateModalVersions.textContent = 'Z wersji ' + updateData.obecna_wersja
                + ' do wersji ' + updateData.nowa_wersja;
            updateModalProgress.hidden = true;
            updateModalOk.disabled = false;
            updateModal.classList.add('active');
        }

        function closeUpdateModal() {
            if (updateModal) updateModal.classList.remove('active');
        }

        updateNowBtn?.addEventListener('click', openUpdateModal);
        document.getElementById('update-check-btn')?.addEventListener('click', forceCheckUpdate);
        document.getElementById('update-apply-btn')?.addEventListener('click', openUpdateModal);
        updateModalCancel?.addEventListener('click', closeUpdateModal);
        updateModalClose?.addEventListener('click', closeUpdateModal);
        updateModal?.addEventListener('click', (e) => {
            if (e.target === updateModal) closeUpdateModal();
        });

        updateModalOk?.addEventListener('click', async () => {
            if (!updateData) return;
            updateModalOk.disabled = true;
            updateModalProgress.hidden = false;
            updateProgressText.textContent = 'Pobieranie aktualizacji…';
            updateProgressBar.style.width = '30%';

            try {
                const res = await apiFetch(API_USTAWIENIA, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=do_update',
                });
                const data = await res.json();
                updateProgressBar.style.width = '100%';
                if (data.success) {
                    updateProgressText.textContent = 'Gotowe! Odświeżam panel…';
                    showToast('Zaktualizowano do wersji ' + data.data.nowa_wersja);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    updateProgressText.textContent = '';
                    updateModalProgress.hidden = true;
                    updateModalOk.disabled = false;
                    showToast(data.error || 'Nie udało się zaktualizować.', 'error');
                }
            } catch (err) {
                updateProgressText.textContent = '';
                updateModalProgress.hidden = true;
                updateModalOk.disabled = false;
                showToast('Błąd połączenia podczas aktualizacji.', 'error');
            }
        });

