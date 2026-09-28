        // --- OBSŁUGA FILTRÓW I WYSZUKIWARKI ---
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
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

