        // --- OBSŁUGA FILTRÓW I WYSZUKIWARKI ---
        // 3.8.8: pole wyszukiwania przyjmuje TYLKO CYFRY (numer zlecenia /
        // telefon). Wcześniej po F5 przeglądarka przywracała tu wartość z
        // ekranu logowania (np. "admin") i cała lista "znikała" - pole
        // czyścimy przy starcie, a litery obcinamy też przy każdym input
        // (autofill przeglądarki potrafi dopisać wartość później).
        searchInput.value = '';
        searchQuery = '';
        searchInput.addEventListener('input', (e) => {
            const czyste = e.target.value.replace(/\D/g, '');
            if (e.target.value !== czyste) e.target.value = czyste;
            searchQuery = czyste;
            renderServicesList();
            scheduleMobileCard();
        });

        // --- 3.9: FILTR TYPU SPRZETU (Rower / Hulajnoga / Wszystkie typy) ---
        // Widoczny tylko przy wlaczonym module hulajnogi (body.off-hulajnogi
        // ukrywa go w CSS); przy wylaczonym module filterTyp trzyma 'all'.
        document.getElementById('typ-filters')?.addEventListener('click', (e) => {
            const btn = e.target.closest('.typ-btn');
            if (!btn) return;
            filterTyp = btn.dataset.typFilter || 'all';
            renderServicesList();
            scheduleMobileCard();
        });

        // --- MOBILE: lista ukryta, więc wynik wyszukiwania pokazuje karta zgłoszenia ---
        let mobileCardTimer = null;

        function openSearchCard() {
            if (!IS_MOBILE) return;
            const query = searchQuery.trim();
            // Na telefonie zobowiazujemy min. 4 znaki numeru serwisowego
            // i pokazujemy tylko to jedno zgloszenie (zero dopasowan = nic).
            if (query.length < 4) return;
            const q = query.toLowerCase();
            const byNo = db.filter(item => !item.deleted
                && (item.serviceNo || '').toLowerCase().includes(q));

            if (byNo.length === 0) {
                showToast(`Brak zgłoszenia o numerze ${query}.`, 'error');
                return;
            }
            const exact = byNo.find(item => (item.serviceNo || '').toLowerCase() === q);
            if (exact) {
                openDetailModal(exact.id);
            } else if (byNo.length === 1) {
                openDetailModal(byNo[0].id);
            } else {
                // Kilka dopasowan: nie otwieramy karty - wiecej cyfr numeru
                showToast(`Znaleziono ${byNo.length} zgłoszeń — wpisz więcej cyfr numeru.`, 'info');
            }
        }

        function scheduleMobileCard() {
            if (!IS_MOBILE) return;
            clearTimeout(mobileCardTimer);
            mobileCardTimer = setTimeout(openSearchCard, 700);
        }

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(mobileCardTimer);
                openSearchCard();
            }
        });

        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                renderServicesList();
            });
        });

        // --- IKONKI UŻYTKOWNIKÓW W WIERZU FILTRÓW (3.8.8) ---
        // Kółko z inicjałem każdego, kto założył zgłoszenie; klik = filtr
        // po autorze (drugi klik zdejmuje filtr). Budowane przy każdym
        // renderze listy, bo lista użytkowników zależy od bieżących danych.
        function renderUserFilters() {
            const box = document.getElementById('user-filters');
            const sep = document.getElementById('user-filters-sep');
            if (!box) return;
            const users = new Map();
            db.forEach(item => {
                if (item.createdById && !users.has(item.createdById)) {
                    users.set(item.createdById, String(item.createdBy || ('#' + item.createdById)));
                }
            });
            // Aktywny filtr, którego użytkownik nie ma już w danych (usunięte
            // wszystkie jego zgłoszenia) - zdejmujemy, żeby nie została
            // pusta lista bez widocznego powodu
            if (filterUserId !== null && !users.has(filterUserId)) filterUserId = null;
            if (sep) sep.hidden = users.size === 0;
            box.hidden = users.size === 0;
            box.innerHTML = Array.from(users, ([id, login]) => {
                const akt = filterUserId === id;
                return `<button type="button" class="user-chip user-chip-filter${akt ? ' active' : ''}"` +
                    ` data-user-id="${id}" style="background: ${userChipColor(login)}"` +
                    ` title="Pokaż tylko zgłoszenia: ${escapeHtml(login)}">${escapeHtml(Array.from(login)[0].toUpperCase())}</button>`;
            }).join('');
        }

        document.getElementById('user-filters')?.addEventListener('click', (e) => {
            const btn = e.target.closest('.user-chip-filter');
            if (!btn) return;
            const id = Number(btn.dataset.userId);
            filterUserId = (filterUserId === id) ? null : id;
            renderServicesList();
        });

        // Kafle dashboardu = skrót do filtrów (klik przewija do listy)
        document.getElementById('dash-grid').addEventListener('click', (e) => {
            const tile = e.target.closest('.dash-tile');
            if (!tile) return;
            currentFilter = tile.dataset.filter;
            filterButtons.forEach(btn =>
                btn.classList.toggle('active', btn.dataset.filter === currentFilter));
            renderServicesList();
            servicesListContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Sortowanie listy (preferencja zapisywana lokalnie)
        const sortSelect = document.getElementById('sort-select');
        try {
            const saved = localStorage.getItem('re_sort');
            if (saved && sortSelect.querySelector(`option[value="${saved}"]`)) currentSort = saved;
        } catch (e) { /* localStorage niedostępne */ }
        if (sortSelect) {
            sortSelect.value = currentSort;
            sortSelect.addEventListener('change', () => {
                currentSort = sortSelect.value;
                try { localStorage.setItem('re_sort', currentSort); } catch (e) { /* ignore */ }
                renderServicesList();
            });
        }

