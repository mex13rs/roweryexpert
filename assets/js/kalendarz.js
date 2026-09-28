        // --- KALENDARZ TERMINÓW (widok miesięczny) ---
        const calendarModal = document.getElementById('calendar-modal');
        const calGrid = document.getElementById('cal-grid');
        const calTitle = document.getElementById('cal-title');
        const MONTHS_PL = [
            'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
        ];
        let calYear = new Date().getFullYear();
        let calMonth = new Date().getMonth();

        // Kolejność chipów w dniu: do wykonania → gotowe → odebrane
        const STATUS_ORDER = { in_progress: 0, completed: 1, picked_up: 2 };

        // Wszystkie zgłoszenia w kalendarzu (bez kosza):
        // nieodebrane kolorowo, odebrane na szaro
        function calendarItems() {
            return db.filter(item => !item.deleted);
        }

        // Kolejność zgłoszeń w dniu: do wykonania → gotowe → odebrane
        function calItemOrder(a, b) {
            return ((STATUS_ORDER[a.status] ?? 9) - (STATUS_ORDER[b.status] ?? 9)) || (a.id - b.id);
        }

        // Listy dni — wypełnia renderCalendar, czyta dymek „więcej"
        let calDayData = {};

        // --- DYMIEK „więcej" — powiększony widok dnia ---
        const calPopover = document.createElement('div');
        calPopover.className = 'cal-popover';
        document.body.appendChild(calPopover);
        let calHideTimer = null;
        let calShownAt = 0;

        function calHidePopover() {
            calPopover.classList.remove('open');
        }

        function calHideSoon() {
            clearTimeout(calHideTimer);
            calHideTimer = setTimeout(() => {
                // Dymek nachodzi na komórkę — jeśli kursor jest nad nim, czekamy
                if (calPopover.matches(':hover')) { calHideSoon(); return; }
                calHidePopover();
            }, 300);
        }

        function calPlural(n) {
            if (n === 1) return 'zgłoszenie';
            const last = n % 10, last2 = n % 100;
            if (last >= 2 && last <= 4 && !(last2 >= 12 && last2 <= 14)) return 'zgłoszenia';
            return 'zgłoszeń';
        }

        function calShowPopover(anchor) {
            const key = anchor.getAttribute('data-cal-day');
            const items = (calDayData[key] || []).slice().sort(calItemOrder);
            if (!items.length) return;

            const d = new Date(key + 'T00:00:00');
            const title = d.toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', weekday: 'long' });
            const rows = items.map(item => {
                let cls = item.status === 'picked_up' ? 's-done'
                    : item.status === 'completed' ? 's-ready' : 's-progress';
                if (item.status !== 'picked_up' && isOverdue(item)) cls += ' is-overdue';
                const range = `${formatDateForUser(item.dateIn)} → ${formatDateForUser(item.datePlanned)}`;
                return `<button type="button" class="cal-pop-item ${cls}" data-cal-id="${item.id}">
                    <span class="cal-pop-name">${escapeHtml(item.bikeName)}</span>
                    <span class="cal-pop-meta">${statusLabelFor(item)} · ${range}</span>
                </button>`;
            }).join('');

            calPopover.innerHTML = `<div class="cal-popover-title">${title} — ${items.length} ${calPlural(items.length)}</div>${rows}`;
            calPopover.dataset.day = key;
            calPopover.classList.add('open');
            calShownAt = Date.now();

            // Dymek rozrasta się "z komórki dnia" — nachodzi na nią bez odstępu,
            // animacja (CSS) robi wrażenie powiększania się samej komórki
            const cell = anchor.closest('.cal-cell') || anchor;
            const cr = cell.getBoundingClientRect();
            const pw = calPopover.offsetWidth;
            const ph = calPopover.offsetHeight;

            let top = cr.top - 2;
            let originY = 'top';
            if (top + ph > window.innerHeight - 8) {      // brak miejsca na dole -> w górę
                top = Math.max(8, cr.bottom + 2 - ph);
                originY = 'bottom';
            }
            const left = Math.max(8, Math.min(cr.left - 2, window.innerWidth - pw - 8));

            calPopover.style.transformOrigin = `${originY} left`;
            calPopover.style.left = left + 'px';
            calPopover.style.top = top + 'px';
        }

        function renderCalendar() {
            calTitle.textContent = `${MONTHS_PL[calMonth]} ${calYear}`;

            // Tydzień zaczyna się od poniedziałku
            const firstDay = new Date(calYear, calMonth, 1);
            const startOffset = (firstDay.getDay() + 6) % 7;
            const start = new Date(calYear, calMonth, 1 - startOffset);

            // Grupowanie: każdy dzień od przyjęcia do planowanego odbioru
            // (wpis 25–27 pojawia się w kalendarzu na 25, 26 i 27)
            const byDay = {};
            calendarItems().forEach(item => {
                const from = (item.dateIn || item.datePlanned);
                let to = item.datePlanned || from;
                if (to < from) to = from;

                let d = new Date(from + 'T00:00:00');
                const end = new Date(to + 'T00:00:00');
                let guard = 0;
                while (d <= end && guard < 90) {
                    const key = formatDateForInput(d);
                    (byDay[key] = byDay[key] || []).push(item);
                    d.setDate(d.getDate() + 1);
                    guard++;
                }
            });
            calDayData = byDay;

            const todayStr = formatDateForInput(new Date());
            let html = '';

            for (let i = 0; i < 42; i++) {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                const key = formatDateForInput(d);
                const otherMonth = d.getMonth() !== calMonth;
                const isToday = key === todayStr;

                const dayItems = (byDay[key] || []).slice().sort(calItemOrder);

                const chips = dayItems.slice(0, 3).map(item => {
                    let cls;
                    if (item.status === 'picked_up') {
                        cls = 'chip-done';                       // odebrany — na szaro
                    } else {
                        cls = (item.status === 'completed' ? 'chip-ready' : 'chip-progress')
                            + (isOverdue(item) ? ' chip-overdue' : '');
                    }
                    const range = `${formatDateForUser(item.dateIn)} → ${formatDateForUser(item.datePlanned)}`;
                    return `<button type="button" class="cal-chip ${cls}" data-cal-id="${item.id}" title="${escapeHtml(item.bikeName)} — w serwisie ${range}">${escapeHtml(item.bikeName)}</button>`;
                }).join('');

                const more = dayItems.length > 3
                    ? `<span class="cal-more" data-cal-day="${key}" role="button" tabindex="0" aria-label="Pokaż wszystkie zgłoszenia tego dnia">+${dayItems.length - 3} więcej</span>`
                    : '';

                html += `
                    <div class="cal-cell${otherMonth ? ' other-month' : ''}${isToday ? ' today' : ''}">
                        <span class="cal-daynum">${d.getDate()}</span>
                        ${chips}${more}
                    </div>`;
            }

            calGrid.innerHTML = html;
        }

        // Kliknięcie wpisu w kalendarzu -> karta podglądu zgłoszenia
        function openCalendarItem(id) {
            calHidePopover();
            calendarModal.classList.remove('active');
            const item = db.find(row => row.id === id);
            if (item) {
                openDetailModal(id);
            } else {
                showToast('To zgłoszenie nie jest już dostępne.', 'error');
            }
        }

        calGrid.addEventListener('click', (e) => {
            const more = e.target.closest('.cal-more');
            if (more) {
                // Na dotyku nie ma najechania — kliknięcie przełącza dymek dnia
                const key = more.getAttribute('data-cal-day');
                if (calPopover.classList.contains('open') && calPopover.dataset.day === key
                        && Date.now() - calShownAt > 600) {
                    calHidePopover();
                } else {
                    clearTimeout(calHideTimer);
                    calShowPopover(more);
                }
                return;
            }
            const chip = e.target.closest('[data-cal-id]');
            if (!chip) return;
            openCalendarItem(parseInt(chip.getAttribute('data-cal-id'), 10));
        });

        // Najechanie/fokus na „więcej" -> dymek z pełną listą dnia
        calGrid.addEventListener('mouseover', (e) => {
            const more = e.target.closest('.cal-more');
            if (!more) return;
            clearTimeout(calHideTimer);
            calShowPopover(more);
        });
        calGrid.addEventListener('mouseout', (e) => {
            const more = e.target.closest('.cal-more');
            if (more && !(e.relatedTarget && more.contains(e.relatedTarget))) calHideSoon();
        });
        calGrid.addEventListener('focusin', (e) => {
            const more = e.target.closest('.cal-more');
            if (more) { clearTimeout(calHideTimer); calShowPopover(more); }
        });
        calGrid.addEventListener('focusout', (e) => {
            if (e.target.closest('.cal-more')) calHideSoon();
        });

        // Dymek trzyma się otwarty pod kursorem; kliknięcie otwiera zgłoszenie
        calPopover.addEventListener('mouseenter', () => clearTimeout(calHideTimer));
        calPopover.addEventListener('mouseleave', calHideSoon);
        calPopover.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-cal-id]');
            if (!btn) {
                // Kliknięcie w tło dymka (np. drugi tap na dotyku) — zamknij
                calHidePopover();
                return;
            }
            openCalendarItem(parseInt(btn.getAttribute('data-cal-id'), 10));
        });

        // Znika przy przewijaniu (dymek jest „przyklejony" do komórki)
        window.addEventListener('scroll', calHideSoon, true);

        function shiftCalendar(delta) {
            calHidePopover();
            calMonth += delta;
            if (calMonth < 0) { calMonth = 11; calYear--; }
            if (calMonth > 11) { calMonth = 0; calYear++; }
            renderCalendar();
        }

        document.getElementById('open-calendar-btn').addEventListener('click', () => {
            const now = new Date();
            calYear = now.getFullYear();
            calMonth = now.getMonth();
            renderCalendar();
            calendarModal.classList.add('active');
        });

        document.getElementById('cal-prev-btn').addEventListener('click', () => shiftCalendar(-1));
        document.getElementById('cal-next-btn').addEventListener('click', () => shiftCalendar(1));
        document.getElementById('calendar-modal-close').addEventListener('click', () => {
            calHidePopover();
            calendarModal.classList.remove('active');
        });
        calendarModal.addEventListener('click', (e) => {
            if (e.target === calendarModal) {
                calHidePopover();
                calendarModal.classList.remove('active');
            }
        });

