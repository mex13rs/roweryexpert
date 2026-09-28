        // --- MODAL EDYCJI ZGŁOSZENIA ---
        const editModal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-form');
        const editBikeNameInput = document.getElementById('edit-bike-name');
        const editDateInInput = document.getElementById('edit-date-in');
        const editDatePlannedInput = document.getElementById('edit-date-planned');
        const editCustomerPhoneInput = document.getElementById('edit-customer-phone');
        const editFaultInput = document.getElementById('edit-fault');
        const editServiceNotesInput = document.getElementById('edit-service-notes');
        let editModalId = null;

        function closeEditModal() {
            editModal.classList.remove('active');
            editModalId = null;
        }

        window.openEditModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item || item.deleted) return;

            editModalId = id;
            editBikeNameInput.value = item.bikeName;
            editDateInInput.value = item.dateIn;
            editDatePlannedInput.value = item.datePlanned;
            editCustomerPhoneInput.value = item.customerPhone;
            editFaultInput.value = item.faultDescription;
            editServiceNotesInput.value = item.serviceNotes || '';
            editModal.classList.add('active');
            editBikeNameInput.focus();
        };

        editCustomerPhoneInput.addEventListener('input', (e) => {
            e.target.value = formatPhone(e.target.value);
        });

        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (editModalId === null) return;

            const item = db.find(item => item.id === editModalId);
            if (!item) { closeEditModal(); return; }

            if (!validatePhone(editCustomerPhoneInput.value)) {
                showToast('Numer telefonu musi mieć co najmniej 9 cyfr.', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('id', editModalId);
                formData.append('bike_name', editBikeNameInput.value.trim());
                formData.append('date_in', editDateInInput.value);
                formData.append('date_planned', editDatePlannedInput.value);
                formData.append('customer_phone', editCustomerPhoneInput.value.trim());
                formData.append('fault_description', editFaultInput.value.trim());
                formData.append('status', item.status);
                formData.append('service_notes', editServiceNotesInput.value.trim());

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zapisać zmian.');

                const idx = db.findIndex(row => row.id === editModalId);
                if (idx !== -1) db[idx] = data.data;

                closeEditModal();
                renderServicesList();
                showToast('Zaktualizowano zgłoszenie.');
            } catch (err) {
                showToast(err.message, 'error');
            }
        });

        document.getElementById('edit-modal-cancel').addEventListener('click', closeEditModal);
        document.getElementById('edit-modal-close').addEventListener('click', closeEditModal);
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });

        // --- WYKONANE CZYNNOŚCI (checkboxy z katalogu usług) ---
        // Faktycznie wykonane: stan zapisany w bazie.
        // null = jeszcze nie zapisywano (nic nie zaznaczono) -> pusta lista.
        function getDoneServices(item) {
            return Array.isArray(item.servicesDone) ? item.servicesDone : [];
        }

        // Zakres uzgodniony przy przyjęciu: linie „- Usługa” dopisane do opisu
        // przy zakładaniu zgłoszenia. Karta pokazuje tylko te pozycje; dokładamy
        // też to, co jest już zaznaczone jako wykonane (żeby nie zgubić stanu).
        function getPlannedServices(item) {
            const lines = (item.faultDescription || '').split('\n')
                .map(l => l.trim())
                .filter(l => l.startsWith('- '))
                .map(l => l.slice(2).trim())
                .filter(l => l.length > 0);
            const names = services.map(s => s.nazwa);
            const planned = names.length ? lines.filter(l => names.includes(l)) : lines;
            return Array.from(new Set([...planned, ...getDoneServices(item)]));
        }

        // Zapis zaznaczeń z karty zgłoszenia (debounce: jeden request na serię kliknięć)
        let doneSaveTimer = null;
        const donePending = new Map();   // id zgłoszenia -> { item, values, prev }
        function onDoneToggle(item) {
            const before = donePending.get(item.id);
            const values = Array.from(
                document.querySelectorAll('#detail-done-list input:checked')
            ).map(cb => cb.value);
            // prev = stan sprzed całej serii kliknięć (do wycofania przy błędzie)
            donePending.set(item.id, {
                item,
                values,
                prev: before ? before.prev : item.servicesDone
            });
            item.servicesDone = values;

            clearTimeout(doneSaveTimer);
            doneSaveTimer = setTimeout(flushDoneSaves, 450);
        }

        async function flushDoneSaves() {
            const entries = Array.from(donePending.values());
            donePending.clear();
            for (const e of entries) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'services');
                    fd.append('id', e.item.id);
                    fd.append('services_done', JSON.stringify(e.values));
                    const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Nie udało się zapisać wykonanych czynności.');
                    e.item.servicesDone = data.data.servicesDone;
                } catch (err) {
                    e.item.servicesDone = e.prev;   // wycofanie optymistycznej zmiany
                    if (detailModalId === e.item.id) fillDetailModal(e.item);
                    showToast(err.message, 'error');
                }
            }
        }

        // Autozapis notatek z karty zgłoszenia (debounce jak przy checkboxach)
        let notesSaveTimer = null;
        const notesPending = new Map();   // id zgłoszenia -> { item, value, prev }
        document.getElementById('detail-notes').addEventListener('input', () => {
            const item = db.find(x => x.id === detailModalId);
            if (!item) return;
            const value = document.getElementById('detail-notes').value;
            const before = notesPending.get(item.id);
            notesPending.set(item.id, {
                item,
                value,
                prev: before ? before.prev : item.serviceNotes
            });
            item.serviceNotes = value;   // stan lokalny od razu (podgląd/wydruk)

            clearTimeout(notesSaveTimer);
            notesSaveTimer = setTimeout(flushNotesSaves, 450);
        });

        async function flushNotesSaves() {
            const entries = Array.from(notesPending.values());
            notesPending.clear();
            for (const e of entries) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'notes');
                    fd.append('id', e.item.id);
                    fd.append('service_notes', e.value);
                    const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Nie udało się zapisać notatek.');
                    e.item.serviceNotes = data.data.serviceNotes;
                } catch (err) {
                    e.item.serviceNotes = e.prev || '';
                    if (detailModalId === e.item.id) {
                        document.getElementById('detail-notes').value = e.prev || '';
                    }
                    showToast(err.message, 'error');
                }
            }
        }

        // --- MODAL PODGLĄDU ZGŁOSZENIA (bez edycji, z wydaniem roweru) ---
        const detailModal = document.getElementById('detail-modal');
        const detailIssueBtn = document.getElementById('detail-issue-btn');
        let detailModalId = null;

        function statusLabelFor(item) {
            if (item.deleted) return 'W koszu';
            if (item.status === 'in_progress') return 'W serwisie';
            if (item.status === 'completed') return 'Gotowy';
            if (item.status === 'picked_up') return 'Odebrany';
            return '';
        }

        function fillDetailModal(item) {
            const noEl = document.getElementById('detail-service-no');
            noEl.textContent = item.serviceNo || '';
            noEl.hidden = !item.serviceNo;

            document.getElementById('detail-bike-name').textContent = item.bikeName;

            const badge = document.getElementById('detail-status');
            badge.textContent = statusLabelFor(item);

            document.getElementById('detail-date-in').textContent = formatDateForUser(item.dateIn);

            const overdue = isOverdue(item);
            document.getElementById('detail-date-planned').textContent =
                formatDateForUser(item.datePlanned) + (overdue ? ' — PO TERMINIE' : '');

            document.getElementById('detail-phone').innerHTML =
                `<a href="tel:${escapeHtml(item.customerPhone)}">tel. ${escapeHtml(item.customerPhone)}</a>`;

            document.getElementById('detail-fault').textContent = item.faultDescription || '—';

            // 3.2: kto zalozyl zgloszenie / kto wydal rower (stare = "—")
            document.getElementById('detail-created-by').textContent = item.createdBy || '—';
            document.getElementById('detail-confirmed-by').textContent = item.confirmedBy || '—';

            // Notatki: aktywne pole z autozapisem (w koszu tylko do odczytu)
            const notesEl = document.getElementById('detail-notes');
            notesEl.value = item.serviceNotes || '';
            notesEl.disabled = !!item.deleted;

            // Wydanie roweru dostępne tylko dla zgłoszeń spoza kosza i nieodebranych
            const canIssue = !item.deleted && item.status !== 'picked_up';
            detailIssueBtn.disabled = !canIssue;
            detailIssueBtn.textContent = canIssue ? 'Wydaj rower' : 'Rower już wydany';

            // Wykonane czynności: wyłącznie usługi z pierwotnego zgłoszenia,
            // jako puste checkboxy — zaznaczasz je w chwili wykonania pracy;
            // zaznaczenie zapisuje się samo. Po wydaniu/z kosza tylko podgląd.
            // Brak usług z przyjęcia lub wyłączony moduł — wiersz znika.
            const doneListEl = document.getElementById('detail-done-list');
            const planned = getPlannedServices(item);
            const showDone = modulOn('wykonane') && planned.length > 0;
            document.getElementById('detail-done-label').hidden = !showDone;
            doneListEl.hidden = !showDone;
            doneListEl.textContent = '';
            if (showDone) {
                const checked = getDoneServices(item);
                const wrap = document.createElement('span');
                wrap.className = 'service-checkbox-list';
                wrap.style.marginTop = '0';
                planned.forEach(name => {
                    const label = document.createElement('label');
                    label.className = 'service-check';
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.className = 'service-checkbox';
                    cb.value = name;
                    cb.checked = checked.includes(name);
                    cb.disabled = !canIssue;
                    cb.addEventListener('change', () => onDoneToggle(item));
                    label.appendChild(cb);
                    label.appendChild(document.createTextNode(' ' + name));
                    wrap.appendChild(label);
                });
                doneListEl.appendChild(wrap);
            }

            // Info o zgłoszeniu z telefonu: na mobile lista z maską jest ukryta,
            // więc stan „wymaga potwierdzenia" pokazujemy też w karcie.
            const pendingEl = document.getElementById('detail-pending');
            pendingEl.hidden = item.confirmed || !!item.deleted;
            document.getElementById('detail-pending-text').textContent = IS_MOBILE
                ? 'Zgłoszenie zablokowane — wymaga potwierdzenia na komputerze'
                : 'Zgłoszenie zablokowane — wymaga potwierdzenia';
        }

        window.openDetailModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item) return;
            detailModalId = id;
            fillDetailModal(item);
            detailModal.classList.add('active');
        };

        function closeDetailModal() {
            detailModal.classList.remove('active');
            detailModalId = null;
        }

        // Zatwierdzenie wydania roweru (status -> picked_up)
        detailIssueBtn.addEventListener('click', async () => {
            if (detailModalId === null) return;
            const item = db.find(item => item.id === detailModalId);
            if (!item || item.deleted || item.status === 'picked_up') return;

            try {
                const formData = new FormData();
                formData.append('action', 'status');
                formData.append('id', item.id);
                formData.append('status', 'picked_up');

                const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się zmienić statusu.');

                item.status = 'picked_up';
                fillDetailModal(item);
                renderServicesList();
                showToast(`Rower wydany klientowi: ${item.bikeName}`);

                // Karta wydania roweru: druk od razu (komputer + moduły
                // druku i Karta wydania)
                if (modulOn('druk') && modulOn('karta_wydania') && !IS_MOBILE) triggerPrint(item, 'wydanie');
            } catch (err) {
                showToast(err.message, 'error');
            }
        });

        document.getElementById('detail-modal-close').addEventListener('click', closeDetailModal);
        document.getElementById('detail-cancel-btn').addEventListener('click', closeDetailModal);
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) closeDetailModal();
        });

