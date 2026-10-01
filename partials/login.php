<?php defined('SERWIS_PANEL') or exit; ?>
    <?php if (!$authenticated): ?>
    <!-- LOGIN SCREEN (hasło podawane raz dziennie) -->
    <div class="login-screen">
        <div class="card login-card">
            <div class="logo-section">
                <img class="logo-img" src="logo.png" alt="RoweryExpert">
                <div class="logo-text">
                    <h1>RoweryExpert</h1>
                    <p>Panel Serwisowy</p>
                </div>
            </div>
            <h2><?= !empty($pokazReset) ? 'Nowe hasło' : 'Zaloguj się' ?></h2>
            <?php if (!empty($loginError)): ?>
                <p class="login-error"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>
            <?php if (!empty($resetMsg)): ?>
                <p class="login-error" style="color: #4ade80;"><?= htmlspecialchars($resetMsg) ?></p>
            <?php endif; ?>
            <?php if (!empty($resetErr)): ?>
                <p class="login-error"><?= htmlspecialchars($resetErr) ?></p>
            <?php endif; ?>
            <?php if (!empty($pokazReset)): ?>
            <!-- RESET HASŁA: wysyłka nowego hasła + link potwierdzający -->
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: -0.5rem 0 1rem;">
                Podaj login administratora. Nowe, wygenerowane losowo hasło przyjdzie mailem
                i zmieni się dopiero, gdy klikniesz link w tej wiadomości.
            </p>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/serwis.php') ?>">
                <input type="hidden" name="action" value="request_reset">
                <div class="form-group">
                    <label for="reset-user">Login</label>
                    <input type="text" id="reset-user" name="login" placeholder="np. admin" required autocomplete="username">
                </div>
                <button type="submit" class="btn btn-primary">Wyślij nowe hasło na maila</button>
            </form>
            <p style="text-align: center; margin-top: 0.9rem; font-size: 0.9rem;">
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/serwis.php') ?>" style="color: var(--primary-text);">Wróć do logowania</a>
            </p>
            <?php else: ?>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/serwis.php') ?>">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-user">Login</label>
                    <input type="text" id="login-user" name="login" placeholder="np. marek" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="login-password">Hasło</label>
                    <input type="password" id="login-password" name="password" placeholder="******" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary">Zaloguj</button>
            </form>
            <p style="text-align: center; margin-top: 0.9rem; font-size: 0.9rem;">
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/serwis.php') ?>?zapomnia=1" style="color: var(--primary-text);">Nie pamiętam hasła</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN APP CONTAINER -->
