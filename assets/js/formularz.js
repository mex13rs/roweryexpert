        // --- OBSŁUGA ZDARZEŃ FORMULARZA ---
        serviceForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateForm()) return;

            const item = getFormData();
            const photoCount = selectedFiles.length;
            showUploading(true);
            try {
                const saved = await saveItemToDB(item, selectedFiles);
                showUploading(false);
                resetForm();
                selectedFiles = [];
                renderPhotoPreviews();

                if (IS_MOBILE) {
                    // Telefon: pełnoekranowy monit zamiast toastu; kalendarz
                    // i wydruk uruchomi potwierdzenie na komputerze.
                    showPhoneSuccess(photoCount);
                    if (photoCount) notifyFotoWarn(); // toast o progu80% limitu
                } else {
                    showToast('Zapisano zgłoszenie rowerowe w bazie!');
                    if (photoCount) notifyFotoWarn(); // po „Zapisano”, nie przed

                    // Zapisz, Drukuj i Dodaj do Kalendarza - jedna wspólna akcja
                    printAndAddToCalendar(saved);
                }
            } catch (err) {
                showUploading(false);
                showToast(err.message, 'error');
            }
        });

        document.getElementById('save-only-btn').addEventListener('click', async () => {
            if (validateForm()) {
                const item = getFormData();
                showUploading(true);
                try {
                    const photoCount = selectedFiles.length;
                    await saveItemToDB(item, selectedFiles);
                    showUploading(false);
                    resetForm();
                    selectedFiles = [];
                    renderPhotoPreviews();
                    if (IS_MOBILE) {
                        showPhoneSuccess(photoCount);
                        if (photoCount) notifyFotoWarn(); // toast o progu80% limitu
                    } else {
                        showToast('Zapisano zgłoszenie w bazie serwisu!');
                        if (photoCount) notifyFotoWarn(); // po „Zapisano”, nie przed
                    }
                } catch (err) {
                    showUploading(false);
                    showToast(err.message, 'error');
                }
            }
        });

        function validateForm() {
            if (!bikeNameInput.value.trim()) {
                showToast('Wprowadź nazwę roweru!', 'info');
                bikeNameInput.focus();
                return false;
            }
            if (!validatePhone(customerPhoneInput.value)) {
                            showToast('Wprowadź poprawny numer telefonu (min. 9 cyfr)!', 'info');
                customerPhoneInput.focus();
                return false;
            }
            if (!getFormData().faultDescription) {
                showToast('Opisz usterkę lub zaznacz wykonywane usługi!', 'info');
                faultDescriptionInput.focus();
                return false;
            }
            return true;
        }

        // --- TERMINY: zgłoszenia spóźnione i zaplanowane na jutro ---
        function isOverdue(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;   // brak terminu (moduł kalendarza)
            return item.datePlanned < formatDateForInput(new Date());
        }

        function isPlannedToday(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;
            return item.datePlanned === formatDateForInput(new Date());
        }

        function isPlannedTomorrow(item) {
            if (item.deleted || item.status === 'picked_up') return false;
            if (!item.datePlanned) return false;
            const t = new Date();
            t.setDate(t.getDate() + 1);
            return item.datePlanned === formatDateForInput(t);
        }

        // Liczniki na przyciskach filtrów: Jutro / Po terminie / Kosz
        function setFilterCount(id, count, variant) {
            const el = document.getElementById(id);
            if (!el) return;
            el.hidden = count === 0;
            el.textContent = String(count);
            el.className = 'filter-count' + (variant ? ' ' + variant : '');
        }

        // Licznik na Koszu + odświeżenie kafli dashboardu (reszta filtrów
        // obsługuje się wyłącznie kliknięciem w kafl)
        function updateFilterCounts() {
            setFilterCount('count-trash', db.filter(item => !!item.deleted).length, 'count-neutral');
            updateDashboard();
        }

        // Kafle podsumowań nad listą + podświetlenie kafla aktywnego filtra
        function updateDashboard() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val);
            };
            const live = db.filter(item => !item.deleted);
            set('dash-in-progress', live.filter(item => item.status === 'in_progress').length);
            set('dash-completed', live.filter(item => item.status === 'completed').length);
            set('dash-overdue', db.filter(isOverdue).length);
            set('dash-today', db.filter(isPlannedToday).length);
            set('dash-tomorrow', db.filter(isPlannedTomorrow).length);
            document.querySelectorAll('.dash-tile').forEach(tile =>
                tile.classList.toggle('active', tile.dataset.filter === currentFilter));
        }

        // --- POWITANIE PO ZALOGOWANIU ---
        // Okno „Podsumowanie dnia": ile odbiorów zaplanowanych na dziś i jutro
        // (te same helpery co kafle dashboardu) + ostrzeżenie „Po terminie",
        // gdy jakikolwiek termin minął. Pokazywane tylko po zalogowaniu
        // (flaga ?powitanie=1 z redirectu po udanym logowaniu).
        function pokazPowitanie() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val);
            };
            set('welcome-today', db.filter(isPlannedToday).length);
            set('welcome-tomorrow', db.filter(isPlannedTomorrow).length);
            const poTerminie = db.filter(isOverdue).length;
            const overEl = document.getElementById('welcome-overdue');
            if (overEl) {
                overEl.hidden = poTerminie === 0;
                overEl.textContent = 'Po terminie: ' + poTerminie;
            }
            document.getElementById('welcome-modal').classList.add('active');
        }

        function zamknijPowitanie() {
            document.getElementById('welcome-modal').classList.remove('active');
        }

        document.getElementById('welcome-modal-ok').addEventListener('click', zamknijPowitanie);
        document.getElementById('welcome-modal-close').addEventListener('click', zamknijPowitanie);

