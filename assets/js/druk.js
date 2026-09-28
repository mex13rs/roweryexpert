        // --- WSPÓLNA AKCJA: DRUKUJ + DODAJ DO KALENDARZA ---
        // Kolejność działań zależy od modułów: gdy któreś wyłączone,
        // pomijamy tylko tę część.
        function printAndAddToCalendar(item) {
            if (modulOn('kalendarz')) openGoogleCalendar(item);
            if (modulOn('druk')) triggerPrint(item);
        }

        // --- MECHANIZM DRUKOWANIA POTWIERDZENIA ---
        // tryb: 'przyjecie' (domyślnie) — potwierdzenie przyjęcia roweru,
        //       'wydanie'  — karta wydania roweru (drukowana przy wydaniu)
        function triggerPrint(item, tryb) {
            const isIssue = tryb === 'wydanie';

            // Tytuł zależy od okazji
            document.getElementById('print-title-client').textContent =
                isIssue ? 'Karta Wydania Roweru' : 'Potwierdzenie Przyjęcia Roweru';
            document.getElementById('print-title-service').textContent =
                isIssue ? 'Karta Wydania Roweru - Egzemplarz Serwisu'
                        : 'Zlecenie Serwisowe - Egzemplarz Serwisu';

            // Wypełnij template wydruku A4 (dane roweru, telefonu, opis usterki)
            document.getElementById('print-bike-name').textContent = item.bikeName;
            document.getElementById('print-date-in').textContent = formatDateForUser(item.dateIn);
            document.getElementById('print-customer-phone').textContent = item.customerPhone;
            document.getElementById('print-fault-description').textContent = item.faultDescription;
            document.getElementById('print-service-no').textContent = item.serviceNo || '—';

            // Wypełnij template strony 2 (egzemplarz dla serwisu)
            document.getElementById('print-bike-name-service').textContent = item.bikeName;
            document.getElementById('print-date-in-service').textContent = formatDateForUser(item.dateIn);
            document.getElementById('print-customer-phone-service').textContent = item.customerPhone;
            document.getElementById('print-fault-description-service').textContent = item.faultDescription;
            document.getElementById('print-service-no-service').textContent = item.serviceNo || '—';

            // Wykonane czynności: na karcie wydania lista zaznaczonych checkboxów (☑),
            // gdy nic nie zaznaczono lub moduł wyłączony — sekcja się nie pojawia
            const doneList = (modulOn('wykonane') && isIssue) ? getDoneServices(item) : [];
            const doneHtml = doneList.map(n => '☑ ' + escapeHtml(n)).join('<br>');
            const showDone = doneList.length > 0;
            document.getElementById('print-done-row-client').hidden = !showDone;
            document.getElementById('print-done-cell-client').hidden = !showDone;
            document.getElementById('print-done-client').innerHTML = showDone ? doneHtml : '';
            document.getElementById('print-done-title-service').hidden = !showDone;
            document.getElementById('print-done-service').hidden = !showDone;
            document.getElementById('print-done-service').innerHTML = showDone ? doneHtml : '';

            // Notatki: na wydruku tylko po uzupełnieniu (puste pole znika)
            const notes = (item.serviceNotes || '').trim();
            document.getElementById('print-service-notes').textContent = notes || '—';
            // display zamiast hidden: block ma inline flex, przez który [hidden] nie zadziała
            document.getElementById('print-notes-block').style.display = notes ? 'flex' : 'none';
            document.getElementById('print-notes-spacer').hidden = !!notes;

            // Elementy kodu QR
            const qrCanvas = document.getElementById('qr-code-canvas');
            const qrImg = document.getElementById('qr-code-img');
            const qrServiceCanvas = document.getElementById('qr-service-canvas');

            // Wywołanie systemowego okna druku
            const executePrint = () => {
                setTimeout(() => {
                    window.print();
                }, 150);
            };

            try {
                // Bezpieczne sprawdzanie czy biblioteka QRious została załadowana z CDN
                if (typeof QRious !== 'undefined') {
                    qrCanvas.style.display = 'inline-block';
                    qrImg.style.display = 'none';
                    
                    const ctx = qrCanvas.getContext('2d');
                    ctx.clearRect(0, 0, qrCanvas.width, qrCanvas.height);
                    
                    new QRious({
                        element: qrCanvas,
                        value: 'https://maps.app.goo.gl/samSLejTdYzsQAEg8',
                        size: 150,
                        level: 'H',
                        foreground: '#000000',
                        background: '#ffffff'
                    });

                    // Etykieta QR z numerem serwisowym do naklejenia na rower
                    if (item.serviceNo) {
                        qrServiceCanvas.style.display = 'inline-block';
                        new QRious({
                            element: qrServiceCanvas,
                            value: item.serviceNo,
                            size: 150,
                            level: 'H',
                            foreground: '#000000',
                            background: '#ffffff'
                        });
                    } else {
                        qrServiceCanvas.style.display = 'none';
                    }
                    
                    executePrint(); // Rysowanie na canvasie jest natychmiastowe - drukujemy od razu
                } else {
                    throw new Error('QRious library is missing.');
                }
            } catch (err) {
                // W przypadku problemów sieciowych, pobieramy kod QR przez API online
                console.warn('Błąd generowania QR lokalnie, korzystam z API online:', err);
                qrCanvas.style.display = 'none';
                qrImg.style.display = 'inline-block';
                qrServiceCanvas.style.display = 'none';
                
                // Czekamy na pobranie kodu QR przed wywołaniem druku
                qrImg.onload = () => {
                    executePrint();
                };
                qrImg.onerror = () => {
                    console.error('Nie można pobrać kodu QR z zewnętrznego API.');
                    executePrint(); // Drukuj mimo braku obrazka QR
                };
                
                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fmaps.app.goo.gl%2FsamSLejTdYzsQAEg8';
            }
        }

        // Phone auto-format
        customerPhoneInput.addEventListener('input', (e) => {
            const cursorPos = e.target.selectionStart;
            const oldVal = e.target.value;
            const formatted = formatPhone(oldVal);
            e.target.value = formatted;
            // Restore cursor position roughly
            const diff = formatted.length - oldVal.length;
            e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
        });

        // Selected files for the new-report form (with preview)
        let selectedFiles = [];

        photosInput.addEventListener('change', () => {
            selectedFiles = selectedFiles.concat(Array.from(photosInput.files));
            photosInput.value = '';
            renderPhotoPreviews();
        });

        cameraInput.addEventListener('change', () => {
            selectedFiles = selectedFiles.concat(Array.from(cameraInput.files));
            cameraInput.value = '';
            renderPhotoPreviews();
        });

        function renderPhotoPreviews() {
            const container = document.getElementById('photo-previews');
            container.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'photo-preview';
                    div.innerHTML = `<img src="${e.target.result}" alt="Podgląd">
                        <button type="button" class="remove-photo" data-index="${index}" title="Usuń z wyboru">&times;</button>`;
                    const btn = div.querySelector('.remove-photo');
                    btn.addEventListener('click', () => {
                        selectedFiles.splice(index, 1);
                        photosInput.value = '';
                        const dt = new DataTransfer();
                        selectedFiles.forEach(f => dt.items.add(f));
                        photosInput.files = dt.files;
                        renderPhotoPreviews();
                    });
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        // --- MONIT PO ZAPISIE Z TELEFONU (pelny ekran) ---
        // Potwierdzenie zgloszenia (kalendarz + wydruk) robi sie na PC,
        // wiec telefon po zapisie pokazuje tylko informacje + OK.
        const phoneSuccessModal = document.getElementById('phone-success-modal');
        const phoneSuccessMsg = document.getElementById('phone-success-msg');

        function showPhoneSuccess(photoCount) {
            const zdjeciaTxt = photoCount > 0
                ? `Dodano ${photoCount} ${photoCount === 1 ? 'zdjęcie' : (photoCount < 5 ? 'zdjęcia' : 'zdjęć')}.<br><br>`
                : '';
            // Część po potwierdzeniu zależy od włączonych modułów
            const poPotwierdzeniu = [];
            if (modulOn('kalendarz')) poPotwierdzeniu.push('kalendarz');
            if (modulOn('druk')) poPotwierdzeniu.push('wydruk potwierdzenia dla klienta');
            const ogon = poPotwierdzeniu.length
                ? ` — tam uruchomi się też ${poPotwierdzeniu.join(' i ')}.`
                : '.';
            phoneSuccessMsg.innerHTML = zdjeciaTxt
                + 'Zgłoszenie jest zamazane na liście do czasu <strong>potwierdzenia na komputerze</strong>'
                + ogon;
            phoneSuccessModal.classList.add('active');
        }

        document.getElementById('phone-success-ok').addEventListener('click', () => {
            phoneSuccessModal.classList.remove('active');
        });

