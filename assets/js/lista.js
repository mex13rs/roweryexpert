        // --- RENDERING LISTY HISTORYCZNEJ ---
        // Wyszukiwanie po tekście (nazwa, telefon, opis, NUMER SERWISOWY, notatki)
        function itemMatchesSearch(item) {
            if (!searchQuery) return true;
            const query = searchQuery.toLowerCase();
            return item.bikeName.toLowerCase().includes(query)
                || item.customerPhone.toLowerCase().includes(query)
                || item.faultDescription.toLowerCase().includes(query)
                || (item.serviceNo || '').toLowerCase().includes(query)
                || (item.serviceNotes || '').toLowerCase().includes(query)
                || (item.servicesDone || []).join(' ').toLowerCase().includes(query);
        }

        // --- Plakietki osób obsługujących na liście (3.4) ---
        // Stały kolor dla danego loginu: prosty hash -> HSL, niezależny od motywu
        function userChipColor(login) {
            let h = 0;
            for (let i = 0; i < login.length; i++) h = (h * 31 + login.charCodeAt(i)) % 360;
            return 'hsl(' + h + ', 58%, 42%)';
        }

        // Kółko z inicjałem; brak loginu = szare "—" (rekord sprzed wdrożenia)
        function userChip(login, emptyTitle) {
            if (!login) {
                return `<span class="user-chip user-chip-empty" title="${escapeHtml(emptyTitle || 'Brak danych')}">—</span>`;
            }
            const s = String(login);
            return `<span class="user-chip" style="background: ${userChipColor(s)}" title="${escapeHtml(s)}">${escapeHtml(Array.from(s)[0].toUpperCase())}</span>`;
        }

        function renderServicesList() {
            servicesListContainer.innerHTML = '';

            renderUserFilters();   // 3.8.8: ikonki użytkowników (mogą zdjąć wygasły filtr)
            updateFilterCounts();

            // Filtruj i szukaj
            let filteredDb = db.filter(item => {
                const deleted = !!item.deleted;

                // 3.8.8: filtr po ikonce użytkownika (kto założył zgłoszenie)
                if (filterUserId !== null && item.createdById !== filterUserId) return false;

                if (currentFilter === 'trash') {
                    if (!deleted) return false;
                } else {
                    if (deleted) return false;
                    if (currentFilter === 'tomorrow') {
                        if (!isPlannedTomorrow(item)) return false;
                    } else if (currentFilter === 'today') {
                        if (!isPlannedToday(item)) return false;
                    } else if (currentFilter === 'overdue') {
                        if (!isOverdue(item)) return false;
                    } else if (currentFilter === 'mine') {
                        // 3.2: filtr "Moje" - zgloszenia zalozyc przez mnie
                        if (item.createdById !== USER_ID) return false;
                    } else if (currentFilter !== 'all' && item.status !== currentFilter) {
                        return false;
                    }
                }
                
                // Wyszukiwanie po tekście (nazwa, telefon, opis, NUMER SERWISOWY)
                return itemMatchesSearch(item);
            });

            // Sortowanie listy (wybór w prawym górnym rogu nad listą)
            const collator = new Intl.Collator('pl');
            const sorters = {
                planned_asc: (a, b) => (a.datePlanned || a.dateIn || '').localeCompare(b.datePlanned || b.dateIn || ''),
                planned_desc: (a, b) => (b.datePlanned || b.dateIn || '').localeCompare(a.datePlanned || a.dateIn || ''),
                dateIn_desc: (a, b) => (b.dateIn || '').localeCompare(a.dateIn || ''),
                dateIn_asc: (a, b) => (a.dateIn || '').localeCompare(b.dateIn || ''),
                name: (a, b) => collator.compare(a.bikeName || '', b.bikeName || ''),
                status: (a, b) => ((STATUS_ORDER[a.status] ?? 9) - (STATUS_ORDER[b.status] ?? 9))
                    || (a.datePlanned || '').localeCompare(b.datePlanned || ''),
                // 3.2: wg uzytkownika (kto zalozyl / kto wydal) - rekordy sprzed
                // wdrozenia bez autora (null) ida na koniec listy
                user: (a, b) => collator.compare(a.createdBy || String.fromCharCode(65533), b.createdBy || String.fromCharCode(65533)),
                issuer: (a, b) => collator.compare(a.confirmedBy || String.fromCharCode(65533), b.confirmedBy || String.fromCharCode(65533)),
            };

            filteredDb.sort((a, b) =>
                (sorters[currentSort] || sorters.planned_asc)(a, b) || (b.id - a.id));

            if (filteredDb.length === 0) {
                const noCriteria = !searchQuery && currentFilter === 'all';
                servicesListContainer.innerHTML = noCriteria
                    ? `
                    <div class="empty-state">
                        <div class="empty-icon">🚲</div>
                        <p>Tu pojawią się przyjęte rowery. Zacznij od formularza „Przyjmij nowy rower”.</p>
                    </div>
                `
                    : currentFilter === 'trash'
                    ? `
                    <div class="empty-state">
                        <div class="empty-icon">🗑️</div>
                        <p>Kosz jest pusty. Usunięte zgłoszenia trafią tutaj i będzie można je przywrócić.</p>
                    </div>
                `
                    : `
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <p>Żadne zgłoszenie nie pasuje do wyszukiwania ani filtru. Zmień kryteria.</p>
                    </div>
                `;
                return;
            }

            filteredDb.forEach(item => {
                const card = document.createElement('div');
                const isPending = !item.confirmed && !item.deleted;
                const overdue = isOverdue(item);
                card.className = 'service-item-card card-' + item.status
                    + (isPending ? ' pending-card' : '')
                    + (item.deleted ? ' is-deleted' : '');
                card.dataset.id = item.id;
                
                let statusLabel = '';
                if (item.deleted) statusLabel = 'W koszu';
                else if (item.status === 'in_progress') statusLabel = 'W serwisie';
                else if (item.status === 'completed') statusLabel = 'Gotowy';
                else if (item.status === 'picked_up') statusLabel = 'Odebrany';

                const photos = Array.isArray(item.photos) ? item.photos : [];

                // Odznaka statusu: w koszu brak przełączania
                const statusHtml = item.deleted
                    ? `<span class="status-badge status-picked_up">${statusLabel}</span>`
                    : `<span class="status-badge status-${escapeHtml(item.status)}" onclick="cycleStatus(${item.id})" title="Kliknij, aby zmienić status">${statusLabel}</span>`;

                // Miniatury zdjęć na karcie (moduł zdjęć może być wyłączony)
                const thumbs = modulOn('zdjecia') ? photos.map(p => `
                    <button class="photo-thumb" onclick="openLightbox('${escapeHtml(p.url)}')" title="${escapeHtml(p.name)}">
                        <img src="${escapeHtml(p.url)}" alt="Zdjęcie zgłoszenia">
                    </button>
                `).join('') : '';

                // Kalendarz/Drukuj dostępne tylko na komputerze (na mobile nie ma przycisków druku/API)
                const calendarBtn = (IS_MOBILE || !modulOn('kalendarz')) ? '' : `
                        <button class="btn-action action-calendar" onclick="openGoogleCalendarFromId(${item.id})" title="Udostępnij do Kalendarza Google">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-3-9v.008H12V9.75h3.75Z" />
                            </svg>
                            Kalendarz
                        </button>`;
                const printBtn = (IS_MOBILE || !modulOn('druk')) ? '' : `
                        <button class="btn-action" onclick="printFromId(${item.id})" title="Drukuj potwierdzenie">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829a42.409 42.409 0 0 0 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 1.252a1.125 1.125 0 0 1-1.107 1.328H7.218a1.125 1.125 0 0 1-1.107-1.328L6.34 18m11.32 0H6.34m0 0h11.32M18 10.5h.008v.008H18V10.5Zm-1.8-6.177a1.95 1.95 0 0 1 2.593 0c.38.347.607.82.607 1.32V9.75H4.5V5.643c0-.5.227-.973.607-1.32a1.95 1.95 0 0 1 2.593 0" />
                            </svg>
                            Drukuj
                        </button>`;

                const photosBtn = modulOn('zdjecia') ? `
                        <button class="btn-action" onclick="openPhotosModal(${item.id})" title="Zobacz i dodaj zdjęcia">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M12 18.75V21m0 0h12M21 12V8.25m0 0h-3.75m3.75 0V4.5m-3.75 3.75h3.75M14.25 7.5h.008v.008h-.008V7.5Z" />
                            </svg>
                            Zdjęcia (${photos.length})
                        </button>` : '';

                const editBtn = `
                        <button class="btn-action" onclick="openEditModal(${item.id})" title="Edytuj zgłoszenie">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            Edytuj
                        </button>`;

                const trashBtn = `
                        <button class="btn-action btn-danger-outline" onclick="deleteItem(${item.id})" title="Przenieś do kosza">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>`;

                const restoreBtn = `
                        <button class="btn-action" onclick="restoreItem(${item.id})" title="Przywróć zgłoszenie z kosza">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            Przywróć
                        </button>`;

                const purgeBtn = `
                        <button class="btn-action btn-danger-outline" onclick="purgeItem(${item.id})" title="Usuń trwale wraz ze zdjęciami">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>`;

                // W koszu tylko podgląd i przywrócenie; poza koszem pełne akcje
                // (kosz może być wyłączony modułem - wtedy nie ma też koszenia)
                const actionsHtml = `<div class="item-actions">${
                    item.deleted
                        ? `${photosBtn}${restoreBtn}${purgeBtn}`
                        : `${photosBtn}${editBtn}${calendarBtn}${printBtn}${modulOn('kosz') ? trashBtn : ''}`
                }</div>`;

                // Kto przyjął: plakietka + login w wierszu „Przyjęto”; kto wydał:
                // kółko przy odznace statusu (3.4)
                const intakeHtml = item.createdBy
                    ? `<span class="chip-inline">${userChip(item.createdBy)}<span class="chip-name">${escapeHtml(item.createdBy)}</span></span>`
                    : userChip(null, 'Konto sprzed wdrożenia użytkowników');
                const issuerHtml = item.confirmedBy
                    ? `<span class="chip-inline" title="Rower wydał: ${escapeHtml(item.confirmedBy)}">${userChip(item.confirmedBy)}</span>`
                    : '';

                card.innerHTML = `
                    <div class="item-header">
                        <div class="item-info">
                            <h3>${escapeHtml(item.bikeName)}${item.serviceNo ? `<span class="service-no" title="Numer serwisowy">${escapeHtml(item.serviceNo)}</span>` : ''}</h3>
                            <a href="tel:${escapeHtml(item.customerPhone)}" class="phone-link">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.802-5.194-4.174-6.996-7.002l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                tel. ${escapeHtml(item.customerPhone)}
                            </a>
                        </div>
                        <span class="status-wrap">${statusHtml}${issuerHtml}</span>
                    </div>
                    
                    <div class="item-details">
                        <div class="detail-row">
                            <span class="detail-label">Przyjęto:</span>
                            <span class="detail-val">${formatDateForUser(item.dateIn)}${intakeHtml}</span>
                        </div>
                        ${modulOn('kalendarz') ? `
                        <div class="detail-row">
                            <span class="detail-label">Termin:</span>
                            <span class="detail-val">${formatDateForUser(item.datePlanned)}${overdue ? ' <span class="overdue-badge">Po terminie</span>' : ''}</span>
                        </div>` : ''}
                        <div class="fault-desc">${escapeHtml(item.faultDescription)}</div>
                        ${item.serviceNotes ? `<div class="detail-row" style="grid-column: span 2;"><span class="detail-label">Notatki:</span><span class="detail-val" style="white-space: pre-wrap;">${escapeHtml(item.serviceNotes)}</span></div>` : ''}
                        ${thumbs ? `<div class="item-photos">${thumbs}</div>` : ''}
                    </div>
                    
                    ${actionsHtml}
                `;

                // Zamazanie zgłoszenia z mobile do czasu potwierdzenia na komputerze
                if (isPending) {
                    card.innerHTML += `
                        <div class="pending-mask">
                            <span class="pending-note">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                Zgłoszenie zablokowane — ${IS_MOBILE ? 'wymaga potwierdzenia na komputerze' : 'wymaga potwierdzenia'}
                            </span>
                            ${IS_MOBILE ? '' : `
                            <button class="btn-confirm" onclick="confirmItem(${item.id})" title="Potwierdź zgłoszenie">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Potwierdź
                            </button>`}
                        </div>
                    `;
                }

                servicesListContainer.appendChild(card);
            });
        }

        // Kliknięcie w treść karty na liście otwiera kartę zgłoszenia.
        // Przyciski, linki, odznaka statusu i maska potwierdzenia zachowują
        // swoją dotychczasową rolę (są pomijane przez closest()).
        servicesListContainer.addEventListener('click', (e) => {
            if (e.target.closest('a, button, .status-badge, .pending-mask, input, label')) return;
            const card = e.target.closest('.service-item-card');
            if (!card) return;
            const id = parseInt(card.dataset.id, 10);
            if (Number.isNaN(id)) return;
            openDetailModal(id);
        });

        // Potwierdzenie zgłoszenia z mobile: odblokowanie + kalendarz + druk
        window.confirmItem = async function(id) {
            const item = db.find(item => item.id === id);
            if (!item || item.confirmed) return;

            try {
                const formData = new FormData();
                formData.append('action', 'confirm');
                formData.append('id', item.id);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się potwierdzić zgłoszenia.');

                item.confirmed = true;
                renderServicesList();
                showToast('Zgłoszenie potwierdzone i odblokowane!');

                // Jednocześnie: dodanie do kalendarza + wydruk potwierdzenia
                // (części wyłączone modułami pomijane)
                printAndAddToCalendar(item);
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Zmiana statusu w cyklu: W serwisie -> Gotowy -> Odebrany -> W serwisie...
        window.cycleStatus = async function(id) {
            const index = db.findIndex(item => item.id === id);
            if (index === -1) return;

            let item = db[index];
            let next;
            if (item.status === 'in_progress') next = 'completed';
            else if (item.status === 'completed') next = 'picked_up';
            else next = 'in_progress';

            try {
                const formData = new FormData();
                formData.append('action', 'status');
                formData.append('id', item.id);
                formData.append('status', next);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zmienić statusu.');

                // 3.5: serwer zwraca pelny rekord - zmienia sie tez "kto wydal",
                // wiec podmieniamy cache, a nie tylko status
                if (data.data) {
                    db[index] = data.data;
                    item = data.data;
                } else {
                    item.status = next;
                }
                renderServicesList();
                showToast(`Zmieniono status roweru: ${item.bikeName}`);

                // Wydanie roweru z listy — druk karty wydania, gdy włączone
                // moduły druku i Karty wydania
                if (next === 'picked_up' && modulOn('druk') && modulOn('karta_wydania') && !IS_MOBILE) {
                    triggerPrint(item, 'wydanie');
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Narzędziowe akcje na wierszach historii
        window.openGoogleCalendarFromId = function(id) {
            const item = db.find(item => item.id === id);
            if (item) openGoogleCalendar(item);
        };

        window.printFromId = function(id) {
            const item = db.find(item => item.id === id);
            // Po wydaniu przycisk drukuje kartę wydania, wcześniej — potwierdzenie przyjęcia
            if (item) triggerPrint(item, item.status === 'picked_up' ? 'wydanie' : 'przyjecie');
        };

        window.deleteItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            const item = db.find(item => item.id === id);
            const bikeLabel = item ? `„${item.bikeName}”` : 'to zlecenie';
            const confirmed = await showConfirmModal(
                'Przenieś do kosza',
                `Zgłoszenie ${bikeLabel} trafi do kosza — będzie można je przywrócić. Trwałe usunięcie (razem ze zdjęciami) znajdziesz w koszu.`,
                'Do kosza'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZGLOSZENIA}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się przenieść zgłoszenia do kosza.');

                if (item) item.deleted = true;
                renderServicesList();
                showToast('Zgłoszenie przeniesione do kosza.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Przywrócenie zgłoszenia z kosza
        window.restoreItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            try {
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('id', id);

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się przywrócić zgłoszenia.');

                const item = db.find(item => item.id === id);
                if (item) item.deleted = false;
                renderServicesList();
                showToast('Przywrócono zgłoszenie z kosza.');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        // Trwałe usunięcie z kosza (wraz ze zdjęciami)
        window.purgeItem = async function(id) {
            if (!modulOn('kosz')) return;   // moduł kosza wyłączony
            const item = db.find(item => item.id === id);
            const bikeLabel = item ? `„${item.bikeName}”` : 'to zlecenie';
            const confirmed = await showConfirmModal(
                'Usuń trwale',
                `Zgłoszenie ${bikeLabel} wraz ze zdjęciami zostanie nieodwracalnie usunięte z bazy. Tej operacji nie można cofnąć.`,
                'Usuń trwale'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZGLOSZENIA}?id=${encodeURIComponent(id)}&purge=1`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć zgłoszenia.');

                db = db.filter(item => item.id !== id);
                renderServicesList();
                showToast('Usunięto zgłoszenie trwale.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

