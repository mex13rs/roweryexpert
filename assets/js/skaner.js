        // --- SKANER QR (aparat przy pasku wyszukiwania) ---
        const scanModal = document.getElementById('scan-modal');
        const scanVideo = document.getElementById('scan-video');
        const scanHint = document.getElementById('scan-hint');
        let scanStream = null;
        let scanRafId = null;
        let scanBusy = false;
        let scanLastTick = 0;
        let barcodeDetector = null;
        let jsQRPromise = null;

        function stopScanner() {
            scanModal.classList.remove('active');
            scanBusy = false;
            if (scanRafId !== null) {
                cancelAnimationFrame(scanRafId);
                scanRafId = null;
            }
            if (scanStream) {
                scanStream.getTracks().forEach(track => track.stop());
                scanStream = null;
            }
            scanVideo.srcObject = null;
        }

        // Fallback jsQR (np. iOS Safari nie ma natywnego BarcodeDetector) — ładowany na żądanie
        function loadJsQR() {
            if (!jsQRPromise) {
                jsQRPromise = new Promise((resolve, reject) => {
                    if (typeof window.jsQR !== 'undefined') return resolve(window.jsQR);
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                    s.onload = () => resolve(window.jsQR);
                    s.onerror = () => reject(new Error('Nie udało się załadować biblioteki skanera.'));
                    document.head.appendChild(s);
                });
            }
            return jsQRPromise;
        }

        async function detectCodeFromVideo() {
            // 1) Natywny BarcodeDetector (Chrome/Android)
            if ('BarcodeDetector' in window) {
                if (!barcodeDetector) {
                    try {
                        barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
                    } catch (e) {
                        barcodeDetector = null;
                    }
                }
                if (barcodeDetector) {
                    const codes = await barcodeDetector.detect(scanVideo);
                    if (codes.length) return codes[0].rawValue;
                    return null;
                }
            }

            // 2) Fallback: jsQR na klatce pobranej z <video>
            const jsQR = await loadJsQR();
            const w = scanVideo.videoWidth;
            const h = scanVideo.videoHeight;
            if (!w || !h) return null;

            const canvas = detectCodeFromVideo.canvas || (detectCodeFromVideo.canvas = document.createElement('canvas'));
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(scanVideo, 0, 0, w, h);
            const frame = ctx.getImageData(0, 0, w, h);
            const res = jsQR(frame.data, w, h);
            return res ? res.data : null;
        }

        async function scanTick(ts) {
            if (!scanModal.classList.contains('active')) return;

            if (!scanBusy && scanStream && scanVideo.readyState >= 2 && ts - scanLastTick > 300) {
                scanBusy = true;
                scanLastTick = ts;
                try {
                    const code = await detectCodeFromVideo();
                    if (code) {
                        handleScanResult(code);
                        return;
                    }
                } catch (e) {
                    if (!scanTick.warned) {
                        scanTick.warned = true;
                        scanHint.textContent = 'Nie udało się uruchomić odczytu kodu — wpisz numer ręcznie w wyszukiwarce.';
                    }
                }
                scanBusy = false;
            }
            scanRafId = requestAnimationFrame(scanTick);
        }

        // Wynik skanu: przefiltruj listę i otwórz kartę podglądu
        function handleScanResult(raw) {
            const code = String(raw || '').trim();
            stopScanner();

            // Skan ma być widoczny niezależnie od aktywnego filtra
            currentFilter = 'all';
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === 'all');
            });

            searchQuery = code;
            searchInput.value = code;
            renderServicesList();

            const matches = db.filter(item =>
                !item.deleted && (item.serviceNo || '').toLowerCase() === code.toLowerCase()
            );

            if (matches.length === 1) {
                openDetailModal(matches[0].id);
                showToast(`Znaleziono zgłoszenie: ${matches[0].bikeName}`);
            } else if (matches.length > 1) {
                if (IS_MOBILE) {
                    // Lista na telefonie jest ukryta — od razu pokazujemy kartę
                    openDetailModal(matches[0].id);
                    showToast(`Znaleziono ${matches.length} zgłoszeń — pokazano pierwsze.`, 'info');
                } else {
                    showToast(`Znaleziono ${matches.length} zgłoszeń o tym numerze — sprawdź listę.`, 'info');
                }
            } else {
                showToast(`Brak zgłoszenia o numerze ${code}.`, 'error');
            }
        }

        window.openScanModal = async function() {
            if (!modulOn('skaner')) return;   // moduł skanera wyłączony
            // Wyczyść poprzednie wyszukiwanie, żeby wynik skanu był czysty
            searchQuery = '';
            searchInput.value = '';
            scanTick.warned = false;
            scanHint.textContent = 'Skieruj aparat na kod QR z numerem serwisowym (naklejka na sprzęcie).';
            scanModal.classList.add('active');

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Ta przeglądarka nie udostępnia aparatu.');
                }
                scanStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false
                });
                scanVideo.srcObject = scanStream;
                await scanVideo.play();
                scanLastTick = 0;
                scanRafId = requestAnimationFrame(scanTick);
            } catch (err) {
                stopScanner();
                showToast('Nie udało się uruchomić aparatu: ' + (err.message || err.name || 'błąd'), 'error');
            }
        };

        document.getElementById('scan-qr-btn').addEventListener('click', window.openScanModal);
        document.getElementById('scan-modal-close').addEventListener('click', stopScanner);
        document.getElementById('scan-cancel-btn').addEventListener('click', stopScanner);
        scanModal.addEventListener('click', (e) => {
            if (e.target === scanModal) stopScanner();
        });

        // Skaner tylko na urządzeniach mobilnych (aparat)
        if (!IS_MOBILE) {
            document.getElementById('scan-qr-btn').hidden = true;
        }

