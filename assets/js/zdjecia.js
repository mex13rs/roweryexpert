        // --- MODAL ZDJĘĆ: podgląd, dodawanie, usuwanie ---
        let photosModalId = null;

        window.openPhotosModal = function(id) {
            const item = db.find(item => item.id === id);
            if (!item) return;
            photosModalId = id;
            photosModalTitle.textContent = item.bikeName;
            photosModalInput.value = '';
            renderPhotosGrid(item.photos || []);
            photosModal.classList.add('active');
        };

        function renderPhotosGrid(photos) {
            photosGrid.innerHTML = '';
            if (!photos.length) {
                photosGrid.innerHTML = '<div class="photos-empty">Brak zdjęć. Wgraj pierwsze zdjęcie powyżej.</div>';
                return;
            }
            photos.forEach(photo => {
                const tile = document.createElement('div');
                tile.className = 'photo-tile';
                // 3.10.1: miniatura (320 px) zamiast pełnego zdjęcia; klik i lightbox
                // nadal pokazują pełne zdjęcie. Bez miniatury = pełne zdjęcie.
                tile.innerHTML = `
                    <img src="${escapeHtml(photo.thumb_url || photo.url)}" alt="${escapeHtml(photo.name || 'Zdjęcie')}" onclick="openLightbox('${escapeHtml(photo.url)}')" loading="lazy" decoding="async">
                    <button class="delete-photo" title="Usuń zdjęcie" onclick="deletePhoto(${photo.id})">&times;</button>
                `;
                photosGrid.appendChild(tile);
            });
        }

        async function uploadModalPhotos(files) {
            if (!photosModalId || !files.length) return;

            const formData = new FormData();
            formData.append('zgloszenie_id', photosModalId);
            for (const file of files) {
                formData.append('photos[]', file);
            }

            showUploading(true);
            photosModalInput.disabled = true;
            cameraModalInput.disabled = true;
            try {
                const res = await apiFetch(API_ZDJECIA, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się wgrać zdjęć.');

                const item = db.find(item => item.id === photosModalId);
                if (item) {
                    item.photos = (item.photos || []).concat(data.data);
                    renderPhotosGrid(item.photos);
                    renderServicesList();
                }
                // Po wgrywaniu: komunikat; gdy próg80% przekroczony — jeden
                // złożony toast z procentem i zapasem (nowy toast kasuje stary)
                const st = await photoStatsNow();
                const warn = st ? fotoWarnText(st.bytes) : null;
                loadPhotoStats(); // odśwież info w statystykach
                if (warn) {
                    showToast(`Dodano zdjęć: ${data.data.length}. ⚠ ${warn}`, 'error');
                } else {
                    showToast(`Dodano zdjęć: ${data.data.length}`);
                }
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                showUploading(false);
                photosModalInput.disabled = false;
                cameraModalInput.disabled = false;
            }
        }

        photosModalInput.addEventListener('change', () => {
            const files = Array.from(photosModalInput.files);
            photosModalInput.value = '';
            uploadModalPhotos(files);
        });

        cameraModalInput.addEventListener('change', () => {
            const files = Array.from(cameraModalInput.files);
            cameraModalInput.value = '';
            uploadModalPhotos(files);
        });

        window.deletePhoto = async function(photoId) {
            const confirmed = await showConfirmModal(
                'Usuń zdjęcie',
                'Czy na pewno chcesz usunąć to zdjęcie? Zostanie trwale usunięte z serwera, a operacji nie można cofnąć.',
                'Usuń'
            );
            if (!confirmed) return;
            try {
                const res = await apiFetch(`${API_ZDJECIA}?id=${encodeURIComponent(photoId)}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nie udało się usunąć zdjęcia.');

                const item = db.find(item => item.id === photosModalId);
                if (item) {
                    item.photos = (item.photos || []).filter(p => p.id !== photoId);
                    renderPhotosGrid(item.photos);
                }
                renderServicesList();
                loadPhotoStats(); // zużycie spadło — odśwież ostrzeżenie80%
                showToast('Usunięto zdjęcie.', 'info');
            } catch (err) {
                showToast(err.message, 'error');
            }
        };

        closePhotosBtn.addEventListener('click', () => {
            photosModal.classList.remove('active');
            photosModalId = null;
        });

        photosModal.addEventListener('click', (e) => {
            if (e.target === photosModal) {
                photosModal.classList.remove('active');
                photosModalId = null;
            }
        });

        // --- LIGHTBOX ---
        window.openLightbox = function(url) {
            lightboxImg.src = url;
            lightbox.classList.add('active');
        };

        lightbox.addEventListener('click', () => {
            lightbox.classList.remove('active');
            lightboxImg.src = '';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                lightbox.classList.remove('active');
                lightboxImg.src = '';
                photosModal.classList.remove('active');
                photosModalId = null;
                closeConfirmModal(false);
                if (typeof closeEditModal === 'function') closeEditModal();
                if (typeof closeDetailModal === 'function') closeDetailModal();
                if (typeof stopScanner === 'function') stopScanner();
                if (typeof calendarModal !== 'undefined') calendarModal.classList.remove('active');
                if (typeof calHidePopover === 'function') calHidePopover();
            }
        });

