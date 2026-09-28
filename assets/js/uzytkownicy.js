        // --- ZAKŁADKA UŻYTKOWNICY (3.1, wyłącznie admin) ---
        const usersListEl = document.getElementById('users-list');
        const usersHintEl = document.getElementById('users-hint');
        const adduserBtn = document.getElementById('add-user-btn');
        const newuserLogin = document.getElementById('new-user-login');
        const newuserPass = document.getElementById('new-user-pass');
        const newuserRola = document.getElementById('new-user-rola');
        const myUserId = Number(usersListEl?.dataset.me || 0);

        let usersCache = [];
        let resetUserId = null;   // dla którego konta otwarty jest formularz resetu

        function usersHint(text, isError) {
            if (!usersHintEl) return;
            usersHintEl.textContent = text || '';
            usersHintEl.style.color = isError ? 'var(--danger)' : 'var(--text-secondary)';
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
                const badge = u.rola === 'admin'
                    ? '<span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--accent); border: 1px solid var(--accent); border-radius: 999px; padding: 0.1rem 0.5rem;">admin</span>'
                    : '<span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); border: 1px solid var(--border); border-radius: 999px; padding: 0.1rem 0.5rem;">pracownik</span>';

                const flags = [
                    off ? '<span style="font-size: 0.75rem; color: var(--danger); font-weight: 700;">konto wyłączone</span>' : '',
                    Number(u.must_change_password)
                        ? '<span style="font-size: 0.75rem; color: var(--text-secondary);">wymusza zmianę hasła</span>' : '',
                    self ? '<span style="font-size: 0.75rem; color: var(--text-secondary);">(to Twoje konto)</span>' : '',
                ].filter(Boolean).join(' ');

                const resetForm = resetUserId === id
                    ? `<input type="password" id="reset-pass-input" placeholder="nowe hasło (min. 6)"
                              autocomplete="new-password" data-reset-input
                              style="flex: 1 1 140px; min-width: 0; padding: 0.45rem 0.6rem; border-radius: 8px;
                                     border: 1px solid var(--border); background: var(--card-lighter); color: var(--primary-text);">
                       <button class="btn btn-primary" data-act="reset-save" data-id="${id}" style="font-size: 0.82rem;">Zapisz hasło</button>
                       <button class="btn btn-secondary" data-act="reset-cancel" style="font-size: 0.82rem;">Anuluj</button>`
                    : `<select data-act="role" data-id="${id}" ${self ? 'disabled' : ''}
                              style="font-size: 0.82rem; padding: 0.4rem 0.5rem; border-radius: 8px;
                                     border: 1px solid var(--border); background: var(--card-lighter); color: var(--primary-text);">
                           <option value="pracownik" ${u.rola !== 'admin' ? 'selected' : ''}>pracownik</option>
                           <option value="admin" ${u.rola === 'admin' ? 'selected' : ''}>administrator</option>
                       </select>
                       <button class="btn btn-secondary" data-act="reset" data-id="${id}" style="font-size: 0.82rem;">Reset hasła</button>
                       <button class="btn ${off ? 'btn-primary' : 'btn-secondary'}" data-act="toggle" data-id="${id}" ${self ? 'disabled' : ''}
                               style="font-size: 0.82rem;">${off ? 'Włącz konto' : 'Wyłącz konto'}</button>`;

                return `<div style="border: 1px solid var(--border); border-radius: 10px; padding: 0.7rem 0.85rem; background: var(--card-lighter);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <strong style="font-size: 0.95rem; color: var(--primary-text);">${escapeHtml(String(u.login))}</strong>
                        ${badge}
                        ${flags}
                        <span style="margin-left: auto; font-size: 0.75rem; color: var(--text-secondary);">
                            ostatnie logowanie: ${formatLastLogin(u.last_login_at)}
                        </span>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.6rem; flex-wrap: wrap; align-items: center;">
                        ${resetForm}
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
            } catch (err) {
                usersListEl.innerHTML = '';
                usersHint(err.message, true);
            }
        }

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

        // --- akcje na liście kont (delegacja, bo lista się przebudowuje) ---
        usersListEl?.addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-act]');
            if (!btn) return;
            const act = btn.dataset.act;
            const id = Number(btn.dataset.id);
            const u = usersCache.find(x => Number(x.id) === id);

            try {
                if (act === 'reset') {
                    resetUserId = id;
                    renderUsers();
                    document.getElementById('reset-pass-input')?.focus();
                    return;
                }
                if (act === 'reset-cancel') {
                    resetUserId = null;
                    renderUsers();
                    return;
                }
                if (act === 'reset-save') {
                    const nowe = document.getElementById('reset-pass-input')?.value || '';
                    if (nowe.length < 6) { usersHint('Nowe hasło musi mieć min. 6 znaków.', true); return; }
                    await usersPost({ action: 'password', id, haslo: nowe });
                    resetUserId = null;
                    showToast(`Hasło konta "${u?.login ?? id}" zmienione; konto wylogowane.`, 'info');
                    await loadUsers();
                    return;
                }
                if (act === 'toggle') {
                    const wylacz = Number(u?.aktywny ?? 1) === 1;
                    await usersPost({ action: 'toggle', id, aktywny: wylacz ? '0' : '1' });
                    showToast(wylacz ? `Konto "${u?.login}" wyłączone.` : `Konto "${u?.login}" włączone.`, 'info');
                    await loadUsers();
                    return;
                }
            } catch (err) {
                usersHint(err.message, true);
            }
        });

        usersListEl?.addEventListener('change', async (e) => {
            const sel = e.target.closest('select[data-act="role"]');
            if (!sel) return;
            const id = Number(sel.dataset.id);
            const u = usersCache.find(x => Number(x.id) === id);
            const rola = sel.value === 'admin' ? 'admin' : 'pracownik';
            try {
                await usersPost({ action: 'role', id, rola });
                showToast(`Rola konta "${u?.login}": ${rola === 'admin' ? 'administrator' : 'pracownik'}.`, 'info');
                await loadUsers();
            } catch (err) {
                usersHint(err.message, true);
                renderUsers();   // cofnij wartość w select
            }
        });
