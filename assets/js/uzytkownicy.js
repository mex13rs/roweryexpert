        // --- ZAKŁADKA UŻYTKOWNICY (3.1+, wyłącznie admin) ---
        // Lista to tylko podgląd; akcje (rola, reset hasła, włącz/wyłącz,
        // usunięcie) w osobnej karcie konta (#user-modal) - jak karta
        // zgłoszenia (3.3).
        const usersListEl = document.getElementById('users-list');
        const usersHintEl = document.getElementById('users-hint');
        const adduserBtn = document.getElementById('add-user-btn');
        const newuserLogin = document.getElementById('new-user-login');
        const newuserPass = document.getElementById('new-user-pass');
        const newuserRola = document.getElementById('new-user-rola');
        const myUserId = Number(usersListEl?.dataset.me || 0);

        // Elementy karty konta
        const userModal = document.getElementById('user-modal');
        const userModalLogin = document.getElementById('user-modal-login');
        const userModalBadge = document.getElementById('user-modal-badge');
        const userModalStatus = document.getElementById('user-modal-status');
        const userModalLast = document.getElementById('user-modal-last');
        const userModalCreated = document.getElementById('user-modal-created');
        const userModalPassflag = document.getElementById('user-modal-passflag');
        const userModalZgloszenia = document.getElementById('user-modal-zgloszenia');
        const userModalWydane = document.getElementById('user-modal-wydane');
        const userModalRola = document.getElementById('user-modal-rola');
        const userModalPassInput = document.getElementById('user-modal-pass-input');
        const userModalPassSave = document.getElementById('user-modal-pass-save');
        const userModalHint = document.getElementById('user-modal-hint');
        const userModalToggle = document.getElementById('user-modal-toggle');
        const userModalDelete = document.getElementById('user-modal-delete');
        const userModalCancel = document.getElementById('user-modal-cancel');
        const userModalClose = document.getElementById('user-modal-close');

        let usersCache = [];
        let openUserId = null;   // konto otwarte w karcie

        function usersHint(text, isError) {
            if (!usersHintEl) return;
            usersHintEl.textContent = text || '';
            usersHintEl.style.color = isError ? 'var(--danger)' : 'var(--text-secondary)';
        }

        function cardHint(text, isError) {
            if (!userModalHint) return;
            userModalHint.textContent = text || '';
            userModalHint.style.color = isError ? 'var(--danger)' : 'var(--text-secondary)';
        }

        async function usersPost(fields) {
            const fd = new FormData();
            for (const key of Object.keys(fields)) fd.append(key, String(fields[key]));
            const res = await apiFetch(API_UZYTKOWNICY, { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Operacja nieudana.');
            return data;
        }

        function formatLastLogin(v) {
            if (!v) return 'nigdy';
            // '2026-09-28 10:00:00' -> '28.09.2026 10:00'
            const m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/.exec(String(v));
            return m ? `${m[3]}.${m[2]}.${m[1]} ${m[4]}` : String(v);
        }

        function roleBadge(u) {
            return u.rola === 'admin'
                ? '<span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--accent); border: 1px solid var(--accent); border-radius: 999px; padding: 0.1rem 0.5rem;">admin</span>'
                : '<span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); border: 1px solid var(--border); border-radius: 999px; padding: 0.1rem 0.5rem;">pracownik</span>';
        }

        function userById(id) {
            return usersCache.find(x => Number(x.id) === Number(id)) || null;
        }

        function renderUsers() {
            if (!usersListEl) return;
            if (!usersCache.length) {
                usersListEl.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-secondary);">Brak kont użytkowników.</p>';
                return;
            }

            usersListEl.innerHTML = usersCache.map(u => {
                const id = Number(u.id);
                const self = id === myUserId;
                const off = !Number(u.aktywny);
                const flags = [
                    off ? '<span style="font-size: 0.75rem; color: var(--danger); font-weight: 700;">konto wyłączone</span>' : '',
                    Number(u.must_change_password)
                        ? '<span style="font-size: 0.75rem; color: var(--text-secondary);">wymusza zmianę hasła</span>' : '',
                    self ? '<span style="font-size: 0.75rem; color: var(--text-secondary);">(to Twoje konto)</span>' : '',
                ].filter(Boolean).join(' ');

                return `<div class="user-row" data-user-id="${id}" role="button" tabindex="0"
                        aria-label="Otwórz konto ${escapeHtml(String(u.login))}"
                        style="border: 1px solid var(--border); border-radius: 10px; padding: 0.7rem 0.85rem; background: var(--card-lighter);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <strong style="font-size: 0.95rem; color: var(--primary-text);">${escapeHtml(String(u.login))}</strong>
                        ${roleBadge(u)}
                        ${flags}
                        <span style="margin-left: auto; font-size: 0.75rem; color: var(--text-secondary);">
                            ostatnie logowanie: ${formatLastLogin(u.last_login_at)}
                        </span>
                        <span style="color: var(--text-secondary); font-size: 1.1rem; line-height: 1;">›</span>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.35rem;">
                        założył: ${Number(u.zgloszenia || 0)} zgłoszeń · wydał: ${Number(u.wydane || 0)} rowerów
                    </div>
                </div>`;
            }).join('');
        }

        async function loadUsers() {
            if (!usersListEl) return;
            usersHint('Wczytywanie…', false);
            try {
                const res = await apiFetch(API_UZYTKOWNICY);
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się wczytać kont.');
                usersCache = data.data;
                usersHint('', false);
                renderUsers();
                // karta otwarta = odśwież jej zawartość po zmianach
                if (openUserId !== null) fillUserCard(openUserId);
            } catch (err) {
                usersListEl.innerHTML = '';
                usersHint(err.message, true);
            }
        }

        // ---------- KARTA KONTA (modal) ----------

        function fillUserCard(id) {
            const u = userById(id);
            if (!u) { closeUserCard(); return; }
            const self = Number(u.id) === myUserId;
            const off = !Number(u.aktywny);
            const refs = Number(u.zgloszenia || 0) + Number(u.wydane || 0);

            userModalLogin.textContent = String(u.login);
            userModalBadge.innerHTML = roleBadge(u);
            userModalStatus.textContent = off ? 'wyłączone' : 'aktywne';
            userModalStatus.style.color = off ? 'var(--danger)' : 'var(--primary-text)';
            userModalLast.textContent = formatLastLogin(u.last_login_at);
            userModalCreated.textContent = formatLastLogin(u.created_at);
            userModalPassflag.textContent = Number(u.must_change_password)
                ? 'wymusza zmianę po zalogowaniu' : 'ustalone';
            userModalZgloszenia.textContent = String(Number(u.zgloszenia || 0));
            userModalWydane.textContent = String(Number(u.wydane || 0));
            userModalRola.value = u.rola === 'admin' ? 'admin' : 'pracownik';
            userModalToggle.textContent = off ? 'Włącz konto' : 'Wyłącz konto';
            if (document.activeElement !== userModalPassInput) userModalPassInput.value = '';

            // Self-guardy i blokada kasowania kont z historią obsługi
            userModalRola.disabled = self;
            userModalToggle.disabled = self;
            userModalDelete.disabled = self || refs > 0;
            if (self) {
                cardHint('To Twoje konto — nie możesz zmienić roli ani go wyłączyć/usunąć.', false);
            } else if (refs > 0) {
                cardHint(`Konto ma przypisane zgłoszenia (${refs}) — możesz je tylko wyłączyć, `
                    + 'żeby na kartach została informacja, kto je obsługiwał.', false);
            } else {
                cardHint('', false);
            }
        }

        function openUserCard(id) {
            const u = userById(id);
            if (!u) return;
            openUserId = Number(u.id);
            fillUserCard(openUserId);
            userModal?.classList.add('active');
        }

        function closeUserCard() {
            userModal?.classList.remove('active');
            openUserId = null;
        }

        // Lista: klik w wiersz otwiera kartę konta
        usersListEl?.addEventListener('click', (e) => {
            const row = e.target.closest('[data-user-id]');
            if (row) openUserCard(row.dataset.userId);
        });
        usersListEl?.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const row = e.target.closest('[data-user-id]');
            if (row) {
                e.preventDefault();
                openUserCard(row.dataset.userId);
            }
        });

        userModalClose?.addEventListener('click', closeUserCard);
        userModalCancel?.addEventListener('click', closeUserCard);
        userModal?.addEventListener('click', (e) => {
            if (e.target === userModal) closeUserCard();
        });

        // Rola
        userModalRola?.addEventListener('change', async () => {
            const u = userById(openUserId);
            if (!u) return;
            const rola = userModalRola.value === 'admin' ? 'admin' : 'pracownik';
            try {
                await usersPost({ action: 'role', id: u.id, rola });
                showToast(`Rola konta "${u.login}": ${rola === 'admin' ? 'administrator' : 'pracownik'}.`, 'info');
                await loadUsers();
            } catch (err) {
                cardHint(err.message, true);
                fillUserCard(openUserId);   // cofnij wartość w select
            }
        });

        // Włączenie/wyłączenie konta
        userModalToggle?.addEventListener('click', async () => {
            const u = userById(openUserId);
            if (!u) return;
            const wylacz = Number(u.aktywny) === 1;
            try {
                await usersPost({ action: 'toggle', id: u.id, aktywny: wylacz ? '0' : '1' });
                showToast(wylacz ? `Konto "${u.login}" wyłączone.` : `Konto "${u.login}" włączone.`, 'info');
                await loadUsers();
            } catch (err) {
                cardHint(err.message, true);
            }
        });

        // Reset hasła
        userModalPassSave?.addEventListener('click', async () => {
            const u = userById(openUserId);
            if (!u) return;
            const nowe = userModalPassInput.value || '';
            if (nowe.length < 6) {
                cardHint('Nowe hasło musi mieć min. 6 znaków.', true);
                userModalPassInput.focus();
                return;
            }
            try {
                await usersPost({ action: 'password', id: u.id, haslo: nowe });
                showToast(`Hasło konta "${u.login}" zmienione; konto wylogowane.`, 'info');
                userModalPassInput.value = '';
                await loadUsers();
            } catch (err) {
                cardHint(err.message, true);
            }
        });

        // Usuwanie konta (bez zgłoszeń) — z potwierdzeniem jak przy zgłoszeniach
        userModalDelete?.addEventListener('click', async () => {
            const u = userById(openUserId);
            if (!u || userModalDelete.disabled) return;
            const ok = await showConfirmModal(
                'Usunąć konto?',
                `Konto "${u.login}" zostanie trwale usunięte razem z jego sesjami. `
                + 'Dostępne tylko dla kont bez zgłoszeń — konto z historią obsługi wyłącz zamiast kasować.',
                'Usuń konto'
            );
            if (!ok) return;
            try {
                await usersPost({ action: 'delete', id: u.id });
                closeUserCard();
                showToast(`Konto "${u.login}" usunięte.`, 'info');
                await loadUsers();
            } catch (err) {
                cardHint(err.message, true);
            }
        });

        // --- dodawanie konta ---
        adduserBtn?.addEventListener('click', async () => {
            const login = (newuserLogin?.value || '').trim();
            const haslo = newuserPass?.value || '';
            const rola = newuserRola?.value === 'admin' ? 'admin' : 'pracownik';

            if (!login) { usersHint('Podaj login (np. imię pracownika).', true); newuserLogin?.focus(); return; }
            if (haslo.length < 6) { usersHint('Hasło startowe musi mieć min. 6 znaków.', true); newuserPass?.focus(); return; }

            adduserBtn.disabled = true;
            try {
                await usersPost({ action: 'create', login, haslo, rola });
                newuserLogin.value = '';
                newuserPass.value = '';
                if (newuserRola) newuserRola.value = 'pracownik';
                usersHint('', false);
                showToast(`Konto "${login}" utworzone.`, 'info');
                await loadUsers();
            } catch (err) {
                usersHint(err.message, true);
            } finally {
                adduserBtn.disabled = false;
            }
        });
