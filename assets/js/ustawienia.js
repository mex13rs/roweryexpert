        // --- OBSŁUGA SETTINGS MODAL ---
        openSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.add('active');
            loadPhotoStats();
            syncModuleToggles();   // zakładka Moduły: odbij aktualne flagi
        });

        closeSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.remove('active');
            passwordHintEl.textContent = '';
        });

        // Zamknięcie modala po kliknięciu na tło
        settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                settingsModal.classList.remove('active');
                passwordHintEl.textContent = '';
            }
        });

        // PRZEŁĄCZANIE ZAKŁADEK USTAWIEŃ
        function switchSettingsTab(tabName) {
            // Zakładka usług nie istnieje, gdy moduł wyłączony
            if (tabName === 'uslugi' && !modulOn('uslugi')) tabName = 'general';
            tabGeneralBtn.classList.toggle('active', tabName === 'general');
            tabUslugiBtn.classList.toggle('active', tabName === 'uslugi');
            tabModulyBtn.classList.toggle('active', tabName === 'moduly');
            tabGeneralContent.hidden = tabName !== 'general';
            tabUslugiContent.hidden = tabName !== 'uslugi';
            tabModulyContent.hidden = tabName !== 'moduly';
            if (tabName === 'uslugi') renderServiceList();
            if (tabName === 'moduly') syncModuleToggles();
        }

        tabGeneralBtn.addEventListener('click', () => switchSettingsTab('general'));
        tabUslugiBtn.addEventListener('click', () => switchSettingsTab('uslugi'));
        tabModulyBtn.addEventListener('click', () => switchSettingsTab('moduly'));

        // --- MODUŁY: przełączniki w zakładce Ustawienia -> Moduły ---
        const MODUL_NAZWA = {
            kalendarz: 'Kalendarz', zdjecia: 'Zdjęcia', skaner: 'Skaner QR',
            uslugi: 'Katalog usług', druk: 'Drukowanie', kosz: 'Kosz',
            kolorystyka: 'Kolorystyka', powitanie: 'Powitanie', karta_wydania: 'Karta wydania',
            wykonane: 'Wykonane czynności'
        };
        const modToggles = document.querySelectorAll('.mod-toggle');
        const modulyHint = document.getElementById('moduly-hint');

        function syncModuleToggles() {
            modToggles.forEach(cb => { cb.checked = modulOn(cb.dataset.mod); });
            modulyHint.textContent = '';
        }

        modToggles.forEach(cb => cb.addEventListener('change', async () => {
            const nazwa = cb.dataset.mod;
            const poprzedni = MODULY[nazwa];
            MODULY[nazwa] = cb.checked;
            modulyHint.textContent = 'Zapisywanie…';

            try {
                await saveModules();
                applyModules();
                renderServicesList();
                updateDashboard();

                // Włączenie kalendarza: przywróć domyślny termin, gdy pusty
                if (nazwa === 'kalendarz' && cb.checked && !datePlannedInput.value) {
                    const d = new Date();
                    d.setDate(d.getDate() + 3);
                    datePlannedInput.value = formatDateForInput(d);
                }

                modulyHint.textContent = `Moduł „${MODUL_NAZWA[nazwa] || nazwa}” `
                    + (cb.checked ? 'włączony.' : 'wyłączony.');
                showToast('Zapisano ustawienia modułów.');
            } catch (err) {
                MODULY[nazwa] = poprzedni;
                cb.checked = poprzedni;
                modulyHint.textContent = '';
                showToast(err.message, 'error');
            }
        }));

        // --- USŁUGI (katalog usług w checkboxach) ---
        let services = [];

        async function loadServices() {
            try {
                const res = await apiFetch(API_USLUGI);
                const data = await res.json();
                if (data.success) {
                    services = data.data;
                    renderFormServiceCheckboxes();
                    renderServiceList();
                }
            } catch (err) {
                showToast('Nie udało się pobrać listy usług.', 'error');
            }
        }

        // Checkboxy pod polem "Opis usterki"
        function renderFormServiceCheckboxes() {
            serviceCheckboxList.innerHTML = '';
            if (!services.length) {
                serviceCheckboxList.innerHTML = '<p class="service-checkbox-empty">Brak skonfigurowanych usług — dodaj je w Ustawieniach.</p>';
                return;
            }
            services.forEach(item => {
                const label = document.createElement('label');
                label.className = 'service-check';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'service-checkbox';
                cb.value = item.nazwa;
                label.appendChild(cb);
                label.appendChild(document.createTextNode(' ' + item.nazwa));
                serviceCheckboxList.appendChild(label);
            });
        }

        // Lista usług w zakładce "Dodaj usługi"
        function renderServiceList() {
            serviceListEl.innerHTML = '';
            if (!services.length) {
                serviceListEl.innerHTML = '<li class="service-list-empty">Brak dodanych usług.</li>';
                return;
            }
            services.forEach(item => {
                const li = document.createElement('li');
                const span = document.createElement('span');
                span.textContent = item.nazwa;
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'remove-service';
                rm.title = 'Usuń usługę';
                rm.textContent = '×';
                rm.addEventListener('click', () => removeService(item.id));
                li.appendChild(span);
                li.appendChild(rm);
                serviceListEl.appendChild(li);
            });
        }

        async function removeService(id) {
            const service = services.find(s => s.id === id);
            const confirmed = await showConfirmModal(
                'Usuń usługę',
                `Czy na pewno chcesz usunąć usługę „${service ? service.nazwa : ''}” z listy? Zniknie również z checkboxów w formularzu zgłoszenia.`,
                'Usuń'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(API_USLUGI, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=remove&id=' + encodeURIComponent(id)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć usługi.');
                services = data.data;
                renderFormServiceCheckboxes();
                renderServiceList();
                showToast('Usunięto usługę.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        }

        addServiceBtn.addEventListener('click', addService);
        newServiceInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addService();
            }
        });

        async function addService() {
            const nazwa = newServiceInput.value.trim();
            if (!nazwa) {
                showToast('Wpisz nazwę usługi.', 'info');
                newServiceInput.focus();
                return;
            }
            addServiceBtn.disabled = true;
            try {
                const res = await apiFetch(API_USLUGI, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=add&nazwa=' + encodeURIComponent(nazwa)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się dodać usługi.');
                services = data.data;
                newServiceInput.value = '';
                renderFormServiceCheckboxes();
                renderServiceList();
                showToast('Dodano usługę!');
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                addServiceBtn.disabled = false;
            }
        }

        // STATYSTYKI ZDJĘĆ
        // Łączny limit pojemności na zdjęcia (MAX_PHOTOS_TOTAL_BYTES z config.php)
        // — widoczny przy statystykach w Ustawieniach -> Ogólne
        // Próg ostrzegawczy: od >=80% limitu komunikat po wgrywaniu
        // i czerwone info w statystykach
        const FOTO_WARN_PCT = 80;

        // Tekst ostrzeżenia o zbliżaniu się do limitu (null = poniżej progu)
        function fotoWarnText(bytes) {
            if (!FOTO_LIMIT_MB) return null;
            const limitBytes = FOTO_LIMIT_MB * 1024 * 1024;
            const pct = Math.round(bytes / limitBytes * 100);
            if (pct < FOTO_WARN_PCT) return null;
            return 'Zdjęcia: zużyto ' + pct + '% limitu ' + FOTO_LIMIT_MB
                + ' MB — zostało ' + formatBytes(Math.max(0, limitBytes - bytes))
                + '. Usuń część starych zdjęć.';
        }

        // Bieżące zużycie prosto z API (do ostrzeżeń po wgrywaniu)
        async function photoStatsNow() {
            try {
                const res = await apiFetch(API_KONTO);
                const data = await res.json();
                if (!data.success) return null;
                return { photos: Number(data.data.photos) || 0, bytes: Number(data.data.bytes) || 0 };
            } catch (e) {
                return null;
            }
        }

        // Toast o przekroczeniu progu80% — wywoływany po zapisaniu zgłoszenia
        // ze zdjęciami (musi być PO toaście „Zapisano”, bo nowszy toast
        // zastępuje poprzedni); poniżej progu nic nie pokazuje
        async function notifyFotoWarn() {
            const st = await photoStatsNow();
            const warn = st ? fotoWarnText(st.bytes) : null;
            if (warn) showToast('⚠ ' + warn, 'error');
            return warn;
        }

        async function loadPhotoStats() {
            statsPhotosEl.textContent = '…';
            statsSizeEl.textContent = '…';
            try {
                const res = await apiFetch(API_KONTO);
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Błąd statystyk');
                const photos = Number(data.data.photos) || 0;
                const bytes = Number(data.data.bytes) || 0;
                statsPhotosEl.textContent = String(photos);
                statsSizeEl.textContent = formatBytes(bytes) + ' / ' + FOTO_LIMIT_MB + ' MB';
                // Info o zużyciu >= progu ostrzegawczego (80% limitu)
                const warn = fotoWarnText(bytes);
                statsWarnEl.hidden = !warn;
                statsWarnEl.textContent = warn ? '⚠ ' + warn : '';
                statsSizeEl.style.color = warn ? 'var(--danger)' : 'var(--primary-text)';
            } catch (err) {
                statsPhotosEl.textContent = '—';
                statsSizeEl.textContent = '—';
                statsWarnEl.hidden = true;
                showToast('Nie udało się pobrać statystyk.', 'error');
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 MB';
            const mb = bytes / (1024 * 1024);
            if (mb >= 1) return mb >= 100 ? Math.round(mb) + ' MB' : mb.toFixed(1).replace('.', ',') + ' MB';
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        refreshStatsBtn.addEventListener('click', loadPhotoStats);

        // ZMIANA HASŁA
        savePasswordBtn.addEventListener('click', async () => {
            const current = currentPasswordInput.value;
            const next = newPasswordInput.value;
            const confirmPass = confirmPasswordInput.value;

            if (!current || !next || !confirmPass) {
                passwordHintEl.textContent = 'Uzupełnij wszystkie pola.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }
            if (next.length < 6) {
                passwordHintEl.textContent = 'Nowe hasło musi mieć min. 6 znaków.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }
            if (next !== confirmPass) {
                passwordHintEl.textContent = 'Nowe hasła nie są identyczne.';
                passwordHintEl.style.color = 'var(--danger)';
                return;
            }

            passwordHintEl.textContent = 'Zapisywanie…';
            passwordHintEl.style.color = 'var(--text-secondary)';
            savePasswordBtn.disabled = true;

            try {
                const res = await fetch(API_KONTO, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=password&current=' + encodeURIComponent(current) + '&next=' + encodeURIComponent(next),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    passwordHintEl.textContent = 'Hasło zostało zmienione.';
                    passwordHintEl.style.color = 'var(--success)';
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmPasswordInput.value = '';
                    showToast('Hasło zmienione!');
                } else {
                    passwordHintEl.textContent = data.error || 'Nie udało się zmienić hasła.';
                    passwordHintEl.style.color = 'var(--danger)';
                }
            } catch (err) {
                passwordHintEl.textContent = 'Błąd połączenia.';
                passwordHintEl.style.color = 'var(--danger)';
            } finally {
                savePasswordBtn.disabled = false;
            }
        });

        // Wylogowanie (koniec sesji dziennej)
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                window.location.href = window.location.pathname + '?logout=1';
            });
        }
