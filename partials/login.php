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
            <h2>Zaloguj się</h2>
            <?php if (!empty($loginError)): ?>
                <p class="login-error"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>
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
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN APP CONTAINER -->
