        // --- MOTYW (DARK / LIGHT) ---
        themeToggleBtn.addEventListener('click', () => {
            if (document.body.classList.contains('light-theme')) {
                setTheme('dark-theme');
            } else {
                setTheme('light-theme');
            }
        });

        function setTheme(theme) {
            if (theme === 'light-theme') {
                document.body.classList.add('light-theme');
                themeIconSun.style.display = 'none';
                themeIconMoon.style.display = 'block';
            } else {
                document.body.classList.remove('light-theme');
                themeIconSun.style.display = 'block';
                themeIconMoon.style.display = 'none';
            }
            localStorage.setItem('theme', theme);
        }

        // --- KOLOR AKCENTU (paleta w nagłówku) ---
        // Domyślnie żółty Media Expert; wybór zapisywany w localStorage
        // (jak motyw - ustawienie per urządzenie).
        const ACCENT_KLASY = ['accent-zielony', 'accent-czerwony', 'accent-niebieski', 'accent-pomaranczowy'];
        // Warianty logo i faviconki przekolorowane na kolor akcentu
        // (żółty #FFDD00 z oryginałów → kolor akcentu, generowane z logo.png/favicon.png)
        const ACCENT_PLIKI = {
            zolty:        { logo: 'logo.png',              fav: 'favicon.png' },
            zielony:      { logo: 'logo-zielony.png',      fav: 'favicon-zielony.png' },
            czerwony:     { logo: 'logo-czerwony.png',     fav: 'favicon-czerwony.png' },
            niebieski:    { logo: 'logo-niebieski.png',    fav: 'favicon-niebieski.png' },
            pomaranczowy: { logo: 'logo-pomaranczowy.png', fav: 'favicon-pomaranczowy.png' }
        };
        const paletteBtn = document.getElementById('palette-btn');
        const accentPicker = document.getElementById('accent-picker');

        function setAccent(accent, zapisz) {
            const a = accent || 'zolty';
            ACCENT_KLASY.forEach(k => document.body.classList.remove(k));
            if (a !== 'zolty') document.body.classList.add('accent-' + a);
            document.querySelectorAll('.accent-swatch').forEach(sw =>
                sw.classList.toggle('active', sw.dataset.accent === a));
            // logo w nagłówku i na loginie + faviconka zmieniają kolor z akcentem
            const plik = ACCENT_PLIKI[a] || ACCENT_PLIKI.zolty;
            document.querySelectorAll('.logo-img').forEach(img => {
                if (!img.src.endsWith(plik.logo)) img.src = plik.logo;
            });
            const fav = document.querySelector('link[rel="icon"]');
            if (fav && !fav.href.endsWith(plik.fav)) fav.href = plik.fav;
            // Zapis tylko przy świadomej zmianie — wymuszenie koloru
            // przy wyłączonym module kolorystyki nie kasuje wyboru
            if (zapisz !== false) localStorage.setItem('accent', a);
        }

        // Wybór zapisany przy starcie (przed pierwszym odrysowaniem)
        setAccent(localStorage.getItem('accent') || 'zolty');

        paletteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            accentPicker.classList.toggle('open');
        });

        document.querySelectorAll('.accent-swatch').forEach(sw =>
            sw.addEventListener('click', () => setAccent(sw.dataset.accent)));

        // Zamknięcie palety po kliknięciu poza nią
        document.addEventListener('click', (e) => {
            if (!accentPicker.contains(e.target) && !paletteBtn.contains(e.target)) {
                accentPicker.classList.remove('open');
            }
        });

        // Helper: formatowanie daty do elementu input type="date"
        function formatDateForInput(date) {
            const pad = (n) => n.toString().padStart(2, '0');
            const yyyy = date.getFullYear();
            const mm = pad(date.getMonth() + 1);
            const dd = pad(date.getDate());
            return `${yyyy}-${mm}-${dd}`;
        }

        // Helper: czytelne formatowanie daty dla użytkownika (PL) - bez godzin
        function formatDateForUser(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const pad = (n) => n.toString().padStart(2, '0');
            return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
        }

        // --- PHONE FORMAT & VALIDATION ---
        function formatPhone(value) {
            // Remove everything except digits
            const digits = value.replace(/\D/g, '');
            // Max 9 digits
            const limited = digits.slice(0, 9);
            // Insert dash every 3 digits: 532 561 152 → 532-561-152
            const parts = [];
            for (let i = 0; i < limited.length; i += 3) {
                parts.push(limited.slice(i, i + 3));
            }
            return parts.join('-');
        }

        function validatePhone(phone) {
            const digits = phone.replace(/\D/g, '');
            return digits.length >= 9;
        }

        // --- TOAST NOTIFICATIONS ---
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            let icon = '';
            if (type === 'success') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--success);"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>`;
            } else if (type === 'info') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--calendar-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>`;
            } else if (type === 'error') {
                icon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px; color: var(--danger);"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Zm3 0v.008h.008V6.75H6.75Zm7.5 0v.008h.008V6.75h-.984Zm-3.75 0v.008h.008V6.75h-.984Z" /></svg>`;
            }
            
            toast.innerHTML = `${icon}<span>${message}</span>`;
            // Jeden toast na raz - skumulowane toasty zaslaniały ekran telefonu
            toastContainer.querySelectorAll('.toast').forEach(t => t.remove());
            toastContainer.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // --- BEZPIECZNE WPROWADZANIE TEKSTU DO HTML ---
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

