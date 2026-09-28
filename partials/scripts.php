<?php defined('SERWIS_PANEL') or exit; ?>
    <!-- ==========================================================================
       LOGIKA JAVASCRIPT
       ========================================================================== -->
    <script>
    const IS_AUTHENTICATED = <?= $authenticated ? 'true' : 'false' ?>;
    const FOTO_LIMIT_MB = <?= (int) round(MAX_PHOTOS_TOTAL_BYTES / 1048576) ?>;
    // 3.2: id zalogowanego konta (filtr "Moje" na liscie zgloszen)
    const USER_ID = <?= (int) ($currentUser['id'] ?? 0) ?>;
    </script>
    <script src="assets/js/core.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/motyw.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/api.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/druk.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/formularz.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/lista.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/karta.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/skaner.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/kalendarz.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/zdjecia.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/filtry.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/ustawienia.js?v=<?= APP_VERSION ?>"></script>
    <script src="assets/js/uzytkownicy.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
