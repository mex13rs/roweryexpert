<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API kont użytkowników (3.1) - wyłącznie administrator
 |  GET                      -> lista kont (bez hashy haseł)
 |  POST action=create       -> nowe konto: login, haslo, rola
 |  POST action=password     -> reset hasła konta (id, haslo)
 |                               + wymuszona zmiana + wylogowanie sesji
 |  POST action=role         -> zmiana roli (id, rola)
 |  POST action=toggle       -> włączenie/wyłączenie konta (id, aktywny)
 --------------------------------------------------------------- */

require __DIR__ . '/../config.php';

auth_require_admin();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/** Login: 2-64 znaki, bez spacji (login = samo imię pracownika). */
function uzytkownicy_login_ok(string $login): bool
{
    return preg_match('/^[A-Za-z0-9ĄĆĘŁŃÓŚŹŻąćęłńóśźż._-]{2,64}$/u', $login) === 1;
}

if ($method === 'GET') {
    $rows = db()->query(
        'SELECT id, login, rola, aktywny, must_change_password, created_at, last_login_at
         FROM users ORDER BY (rola = "admin") DESC, login'
    )->fetchAll();
    json_out(['success' => true, 'data' => $rows]);
}

if ($method === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $me = (int) (auth_user()['id'] ?? 0);

    if ($action === 'create') {
        $login = trim((string) ($_POST['login'] ?? ''));
        $haslo = (string) ($_POST['haslo'] ?? '');
        $rola  = ($_POST['rola'] ?? '') === 'admin' ? 'admin' : 'pracownik';

        if (!uzytkownicy_login_ok($login)) {
            json_fail('Login: 2-64 znaki - litery, cyfry, kropka, myślnik, podkreślnik (bez spacji).', 400);
        }
        if (strlen($haslo) < 6) {
            json_fail('Hasło musi mieć min. 6 znaków.', 400);
        }
        $stmt = db()->prepare('SELECT id FROM users WHERE login = ?');
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            json_fail('Taki login już istnieje.', 400);
        }

        db()->prepare('INSERT INTO users (login, password_hash, rola) VALUES (?, ?, ?)')
            ->execute([$login, password_hash($haslo, PASSWORD_DEFAULT), $rola]);
        json_out(['success' => true, 'id' => (int) db()->lastInsertId()], 201);
    }

    if ($action === 'password') {
        $id   = (int) ($_POST['id'] ?? 0);
        $haslo = (string) ($_POST['haslo'] ?? '');
        if ($id <= 0) {
            json_fail('Brak konta.', 400);
        }
        if (strlen($haslo) < 6) {
            json_fail('Hasło musi mieć min. 6 znaków.', 400);
        }
        $ok = db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?')
            ->execute([password_hash($haslo, PASSWORD_DEFAULT), $id]);
        if (!$ok) {
            json_fail('Konto nie istnieje.', 404);
        }
        // Wylogowanie wszystkich sesji tego konta (hasło się zmieniło)
        db()->prepare('DELETE FROM sesje WHERE user_id = ?')->execute([$id]);
        json_out(['success' => true]);
    }

    if ($action === 'role') {
        $id = (int) ($_POST['id'] ?? 0);
        $rola = ($_POST['rola'] ?? '') === 'admin' ? 'admin' : 'pracownik';
        if ($id <= 0) {
            json_fail('Brak konta.', 400);
        }
        if ($id === $me) {
            json_fail('Nie możesz zmienić roli własnego konta.', 400);
        }
        $ok = db()->prepare('UPDATE users SET rola = ? WHERE id = ?')->execute([$rola, $id]);
        if (!$ok) {
            json_fail('Konto nie istnieje.', 404);
        }
        json_out(['success' => true]);
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $aktywny = (int) (($_POST['aktywny'] ?? '0') === '1');
        if ($id <= 0) {
            json_fail('Brak konta.', 400);
        }
        if ($id === $me) {
            json_fail('Nie możesz wyłączyć własnego konta.', 400);
        }
        $ok = db()->prepare('UPDATE users SET aktywny = ? WHERE id = ?')->execute([$aktywny, $id]);
        if (!$ok) {
            json_fail('Konto nie istnieje.', 404);
        }
        if (!$aktywny) {
            db()->prepare('DELETE FROM sesje WHERE user_id = ?')->execute([$id]);
        }
        json_out(['success' => true]);
    }

    json_fail('Nieznana akcja.', 400);
}

json_fail('Nieznane żądanie.', 400);
