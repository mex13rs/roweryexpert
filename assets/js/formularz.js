        // --- 3.9: TYP SPRZETU (Rower / Hulajnoga) ---
        // Modul "hulajnogi" wylaczony = panel zachowuje sie jak wczesniej: bez
        // wyboru, bez blokady, typ zawsze "rower". Wlaczony = przed pierwszym
        // wyborem formularz jest zablokowany ("Najpierw wybierz typ sprzetu"),
        // a naglowek mowi "Przyjmij nowy". Przelaczenie w trakcie wypelniania
        // jest dozwolone (pola tekstowe zostaja, przerysowuja sie checkboxy),
        // po zapisie wracamy do wyboru typu.
        let wybranyTyp = null;   // null | 'rower' | 'hulajnoga'

        function aktywnyTyp() {
            if (!modulOn('hulajnogi')) return 'rower';
            return wybranyTyp || 'rower';
        }

        // "Przyjmij ..." - mianownik po czasowniku
        const TYP_MIANOWA = { rower: 'rower', hulajnoga: 'hulajnogę' };
        const TYP_PLACEHOLDER = {
            rower: 'np. Kross Hexagon 5.0, Giant Talon 1',
            hulajnoga: 'np. Xiaomi Pro 2, Ninebot F40 Lite'
        };

        function zastosujTypSprzetu() {
            const modul = modulOn('hulajnogi');
            const typ = aktywnyTyp();
            const wybrany = modul && wybranyTyp !== null;

            // Naglowek: wylaczony modul = stary tekst "Przyjmij nowy rower"
            const titleEl = document.getElementById('intake-title-text');
            if (titleEl) {
                titleEl.textContent = !modul ? 'Przyjmij nowy rower'
                    : !wybrany ? 'Przyjmij nowy'
                    : 'Przyjmij ' + TYP_MIANOWA[typ];
            }

            document.querySelectorAll('#intake-type .intake-type-btn').forEach(btn =>
                btn.classList.toggle('active', wybrany && btn.dataset.typ === wybranyTyp));

            const hintEl = document.getElementById('intake-hint');
            if (hintEl) hintEl.hidden = !modul || wybrany;

            // Checkboxy katalogu tylko dla aktywnego typu (przed wyborem tlo
            // pokazuje katalog rowerow - i tak zablokowany). Render PRZED
            // blokada, zeby petla disabled objela tez swiezo utworzone pola.
            if (typeof renderFormServiceCheckboxes === 'function') renderFormServiceCheckboxes();

            // Blokada formularza przed wyborem typu: atrybuty disabled (tez
            // klawiatura) + przyciemnienie i brak klikow (.typ-locked w CSS)
            const zablokowany = modul && !wybrany;
            serviceForm.classList.toggle('typ-locked', zablokowany);
            serviceForm.querySelectorAll('input, textarea, button').forEach(el => {
                el.disabled = zablokowany;
            });
            if (!zablokowany) {
                // przywraca required wg wlaczonego modulu kalendarza
                dateInInput.required = true;
                datePlannedInput.required = modulOn('kalendarz');
            }

            // Dynamiczne etykiety i placeholder pola nazwy
            const nameLabel = document.getElementById('bike-name-label');
            if (nameLabel) nameLabel.textContent = 'Nazwa ' + (typ === 'hulajnoga' ? 'Hulajnogi' : 'Roweru');
            bikeNameInput.placeholder = TYP_PLACEHOLDER[typ];

            // Numer seryjny: wylacznie wybrana hulajnoga (modul wylaczony = nigdy)
            const serialGroup = document.getElementById('serial-group');
            if (serialGroup) serialGroup.hidden = !(modul && wybranyTyp === 'hulajnoga');
        }

        document.getElementById('intake-type')?.addEventListener('click', (e) => {
            const btn = e.target.closest('.intake-type-btn');
            if (!btn || !modulOn('hulajnogi')) return;
            wybranyTyp = btn.dataset.typ === 'hulajnoga' ? 'hulajnoga' : 'rower';
            zastosujTypSprzetu();
        });

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
                    // item.typ odczytany PRZED resetForm() (reset zeruje wybranyTyp)
                    showToast(item.typ === 'hulajnoga'
                        ? 'Zapisano zgłoszenie hulajnogowe w bazie!'
                        : 'Zapisano zgłoszenie rowerowe w bazie!');
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
                        // 3.9: tekst wg typu (dla roweru - dokladnie jak wczesniej)
                        showToast(item.typ === 'hulajnoga'
                            ? 'Zapisano zgłoszenie hulajnogowe w bazie serwisu!'
                            : 'Zapisano zgłoszenie w bazie serwisu!');
                        if (photoCount) notifyFotoWarn(); // po „Zapisano”, nie przed
                    }
                } catch (err) {
                    showUploading(false);
                    showToast(err.message, 'error');
                }
            }
        });

        function validateForm() {
            // 3.9: modul hulajnogi wlaczony = bez wybranego typu nic nie zapiszemy
            // (formularz i tak jest zablokowany, wiec to zabezpieczenie dodatkowe)
            if (modulOn('hulajnogi') && wybranyTyp === null) {
                showToast('Najpierw wybierz typ sprzętu: Rower lub Hulajnoga.', 'info');
                return false;
            }
            if (!bikeNameInput.value.trim()) {
                showToast(wybranyTyp === 'hulajnoga'
                    ? 'Wprowadź nazwę hulajnogi!'
                    : 'Wprowadź nazwę roweru!', 'info');
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
            // 3.8.8: licznik Kosza = tyle, ile faktycznie zobaczysz po
            // wejściu w Kosz (z bieżącym wyszukiwaniem i filtrem po
            // użytkowniku) - inaczej mógł pokazywać np. "2" przy pustej
            // liście, gdy pole wyszukiwania było czymś zapełnione
            const wKoszu = db.filter(item => !!item.deleted
                && itemMatchesSearch(item)
                && (filterUserId === null || item.createdById === filterUserId)).length;
            setFilterCount('count-trash', wKoszu, 'count-neutral');
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
        // 3.9: przy wlaczonym module hulajnogi pod kazdym licznikiem rozbicie
        // na typy ("2 rowery · 1 hulajnoga"); modul wylaczony = widok jak wczesniej.
        function plRower(n) {
            if (n === 1) return 'rower';
            const d = n % 10, s = n % 100;
            return (d >= 2 && d <= 4 && !(s >= 12 && s <= 14)) ? 'rowery' : 'rowerów';
        }

        function plHulajnoga(n) {
            if (n === 1) return 'hulajnoga';
            const d = n % 10, s = n % 100;
            return (d >= 2 && d <= 4 && !(s >= 12 && s <= 14)) ? 'hulajnogi' : 'hulajnog';
        }

        function pokazPowitanie() {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = String(val);
            };
            set('welcome-today', db.filter(isPlannedToday).length);
            set('welcome-tomorrow', db.filter(isPlannedTomorrow).length);

            // Rozbicie na typy sprzetu pod licznikami
            const split = (id, lista) => {
                const el = document.getElementById(id);
                if (!el) return;
                const czesci = [];
                const r = lista.filter(x => (x.typ || 'rower') === 'rower').length;
                const h = lista.filter(x => x.typ === 'hulajnoga').length;
                if (r) czesci.push(r + ' ' + plRower(r));
                if (h) czesci.push(h + ' ' + plHulajnoga(h));
                el.textContent = czesci.join(' · ');
                el.hidden = !modulOn('hulajnogi') || czesci.length === 0;
            };
            split('welcome-today-split', db.filter(isPlannedToday));
            split('welcome-tomorrow-split', db.filter(isPlannedTomorrow));

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

