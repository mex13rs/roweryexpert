        // --- ZAPISYWANIE DANYCH W BAZIE MYSQL (przez API) ---
        async function saveItemToDB(item, files) {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('bike_name', item.bikeName);
            // 3.9: typ sprzetu i numer seryjny (pusty = NULL w bazie)
            formData.append('typ', item.typ || 'rower');
            formData.append('numer_seryjny', item.numerSeryjny || '');
            formData.append('date_in', item.dateIn);
            formData.append('date_planned', item.datePlanned);
            formData.append('customer_phone', item.customerPhone);
            formData.append('fault_description', item.faultDescription);
            formData.append('status', item.status);
            formData.append('source', IS_MOBILE ? 'mobile' : 'desktop');
            // services_done pomijamy: przyjęcie to zakres prac, nie stan wykonania

            if (files && files.length) {
                for (const file of files) {
                    formData.append('photos[]', file);
                }
            }

            const res = await apiFetch(API_ZGLOSZENIA, { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) {
                throw new Error(data.error || 'Nie udało się zapisać zgłoszenia.');
            }
            db.unshift(data.data);
            renderServicesList();
            return data.data;
        }

        // Tworzenie obiektu danych roweru z formularza
        function getFormData() {
            const manual = faultDescriptionInput.value.trim();
            const checked = Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => cb.value);
            let description = manual;
            if (checked.length) {
                const servicesBlock = checked.map(s => '- ' + s).join('\n');
                description = manual ? manual + '\n\n' + servicesBlock : servicesBlock;
            }

            return {
                bikeName: bikeNameInput.value.trim(),
                // 3.9: typ = wybrany przy przyjeciu; numer seryjny wylacznie
                // dla hulajnogi (przy rowerze zawsze pusty)
                typ: aktywnyTyp(),
                numerSeryjny: aktywnyTyp() === 'hulajnoga'
                    ? (document.getElementById('numer-seryjny')?.value || '').trim()
                    : '',
                dateIn: dateInInput.value,
                datePlanned: datePlannedInput.value,
                customerPhone: customerPhoneInput.value.trim(),
                faultDescription: description,
                status: 'in_progress', // Domyślny status: 'in_progress', 'completed', 'picked_up'
                // Na przyjęciu nic jeszcze nie wykonano — zaznaczone usługi
                // jadą do opisu (linie „- ”), checkboxy z karty je wypełnią
                servicesDone: []
            };
        }

        // Czyszczenie formularza
        function resetForm() {
            bikeNameInput.value = '';
            faultDescriptionInput.value = '';
            customerPhoneInput.value = '';
            photosInput.value = '';
            if (cameraInput) cameraInput.value = '';
            document.querySelectorAll('.service-checkbox:checked').forEach(cb => cb.checked = false);

            const now = new Date();
            const planned = new Date();
            planned.setDate(now.getDate() + 2);
            dateInInput.value = formatDateForInput(now);
            datePlannedInput.value = modulOn('kalendarz') ? formatDateForInput(planned) : '';

            // 3.9: po zapisie wracamy do "wybierz typ" (modul hulajnogi wlaczony)
            wybranyTyp = null;
            const serialEl = document.getElementById('numer-seryjny');
            if (serialEl) serialEl.value = '';
            if (typeof zastosujTypSprzetu === 'function') zastosujTypSprzetu();
        }

        // --- INTEGRACJA GOOGLE CALENDAR ---
        // Generowanie linku szybkiego dodawania wydarzenia w Kalendarzu Google
        function generateGoogleCalendarLink(item) {
            const baseUrl = 'https://calendar.google.com/calendar/render';
            const action = 'TEMPLATE';
            
            const title = `🔧 Serwis: ${item.bikeName}`;
            const details = `KLIENT: ${item.customerPhone}\n\nDATA PRZYJĘCIA: ${formatDateForUser(item.dateIn)}\n\nOPIS USTERKI: ${item.faultDescription}`;
            
            const dateStartUTC = convertToUTCFormat(item.dateIn);
            // Zgodnie ze specyfikacją całodniową koniec to dzień następny
            const nextDay = new Date(item.datePlanned);
            nextDay.setDate(nextDay.getDate() + 1);
            const dateEndUTC = convertToUTCFormat(formatDateForInput(nextDay));
            const dates = `${dateStartUTC}/${dateEndUTC}`;
            
            // "remind" ustawiony na pusto nadpisuje domyślne reguły powiadomień konta Google i wyłącza przypomnienia
            const params = new URLSearchParams({
                action: action,
                text: title,
                details: details,
                dates: dates,
                sf: 'true',
                output: 'xml',
                remind: ''
            });

            return `${baseUrl}?${params.toString()}`;
        }

        // Konwersja daty (np. "2026-07-14") na format tekstowy dla Google Calendar całodniowego ("YYYYMMDD")
        function convertToUTCFormat(localDateStr) {
            if (!localDateStr) return '';
            return localDateStr.replace(/-/g, '');
        }

        function openGoogleCalendar(item) {
            const link = generateGoogleCalendarLink(item);
            window.open(link, '_blank');
            showToast('Otwarto okno dodawania do Kalendarza Google!', 'info');
        }

