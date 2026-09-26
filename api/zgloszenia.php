<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API zgloszen serwisowych
 |  GET    /api/zgloszenia.php               -> lista zgloszen (razem z koszem)
 |  POST   action=create (multipart)         -> nowe zgloszenie + zdjecia
 |  POST   action=status                     -> zmiana statusu
 |  POST   action=confirm                    -> potwierdzenie zgloszenia z mobile (PC)
 |  POST   action=update                     -> edycja danych zgloszenia
 |  POST   action=restore                    -> przywrocenie z kosza
 |  DELETE /api/zgloszenia.php?id=N          -> przeniesienie do kosza (soft delete)
 |  DELETE /api/zgloszenia.php?id=N&purge=1  -> trwale usuniecie ze zdjeciami
  --------------------------------------------------------------- */

require __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    auth_require();
    if ($method === 'GET') {
        $rows = db()->query(
            'SELECT * FROM zgloszenia ORDER BY id DESC'
        )->fetchAll();

        $items = array_map(fn(array $r): array => map_zgloszenie($r), $rows);
        json_out(['success' => true, 'data' => $items]);
    }

    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            [$bikeName, $dateIn, $datePlanned, $phone, $fault, $status] =
                validate_zgloszenie($_POST);

            // Zgłoszenie z mobile wymaga potwierdzenia na komputerze
            $source   = (string) ($_POST['source'] ?? 'desktop');
            $confirmed = $source === 'mobile' ? 0 : 1;

            // Moduł "zdjęcia" wyłączony: przyjęcie ze zdjęciami jest odrzucane
            if (!modul('zdjecia') && !empty($_FILES['photos'])) {
                json_fail('Moduł zdjęć jest wyłączony w ustawieniach panelu.');
            }

            $stmt = db()->prepare(
                'INSERT INTO zgloszenia
                    (bike_name, date_in, date_planned, customer_phone,
                     fault_description, status, confirmed)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$bikeName, $dateIn, $datePlanned, $phone, $fault, $status, $confirmed]);
            $id = (int) db()->lastInsertId();

            // Numer serwisowy: RO-ROK-NUMER_ID (etykieta QR na rowerze)
            $serviceNo = sprintf('RO-%s-%04d', date('Y'), $id);
            db()->prepare('UPDATE zgloszenia SET service_no = ? WHERE id = ?')
                ->execute([$serviceNo, $id]);

            store_photos($id, $_FILES['photos'] ?? []);

            $row = db()->prepare('SELECT * FROM zgloszenia WHERE id = ?');
            $row->execute([$id]);

            json_out(['success' => true, 'data' => map_zgloszenie($row->fetch())], 201);
        }

        if ($action === 'status') {
            $id     = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');

            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }
            if (!in_array($status, STATUSES, true)) {
                json_fail('Nieprawidłowy status.');
            }

            $stmt = db()->prepare('UPDATE zgloszenia SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            if ($stmt->rowCount() === 0 && !record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            json_out(['success' => true, 'id' => $id, 'status' => $status]);
        }

        if ($action === 'confirm') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }

            $stmt = db()->prepare('UPDATE zgloszenia SET confirmed = 1 WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0 && !record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            json_out(['success' => true, 'id' => $id, 'confirmed' => true]);
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }
            if (!record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            [$bikeName, $dateIn, $datePlanned, $phone, $fault, $status] =
                validate_zgloszenie($_POST);

            $notes = trim((string) ($_POST['service_notes'] ?? ''));
            if (mb_strlen($notes) > 6000) {
                json_fail('Notatka „wykonane czynności" jest za długa (max 6000 znaków).');
            }

            // date_planned: NULL = pusty termin (modul kalendarza wylaczony);
            // COALESCE zachowuje stary termin, gdy nowy nie przychodzi
            $stmt = db()->prepare(
                'UPDATE zgloszenia
                    SET bike_name = ?, date_in = ?, date_planned = COALESCE(?, date_planned),
                        customer_phone = ?, fault_description = ?,
                        status = ?, service_notes = ?
                  WHERE id = ?'
            );
            $stmt->execute([$bikeName, $dateIn, $datePlanned, $phone, $fault, $status, $notes ?: null, $id]);

            $row = db()->prepare('SELECT * FROM zgloszenia WHERE id = ?');
            $row->execute([$id]);

            json_out(['success' => true, 'data' => map_zgloszenie($row->fetch())]);
        }

        if ($action === 'restore') {
            if (!modul('kosz')) {
                json_fail('Moduł kosza jest wyłączony w ustawieniach panelu.', 403);
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }

            $stmt = db()->prepare('UPDATE zgloszenia SET deleted_at = NULL WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0 && !record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            json_out(['success' => true, 'id' => $id]);
        }

        json_fail('Nieznana akcja.', 404);
    }

    if ($method === 'DELETE') {
        // Kasowanie (kosz i trwałe) wymaga wlaczonego modulu kosza
        if (!modul('kosz')) {
            json_fail('Moduł kosza jest wyłączony w ustawieniach panelu.', 403);
        }
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_fail('Brak identyfikatora zgłoszenia.');
        }
        if (!record_exists($id)) {
            json_fail('Zgłoszenie nie istnieje.', 404);
        }

        $purge = (string) ($_GET['purge'] ?? '') === '1';

        if ($purge) {
            // Trwałe usunięcie: zdjęcia z dysku + wiersz z bazy
            delete_photos_of($id);
            db()->prepare('DELETE FROM zgloszenia WHERE id = ?')->execute([$id]);
            json_out(['success' => true, 'id' => $id, 'purged' => true]);
        }

        // Domyślnie: kosz (soft delete) — można przywrócić
        db()->prepare('UPDATE zgloszenia SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL')
            ->execute([$id]);

        json_out(['success' => true, 'id' => $id, 'deleted' => true]);
    }

    json_fail('Metoda nieobsługiwana.', 405);
} catch (Throwable $e) {
    error_log('[zgloszenia.php] ' . $e->getMessage());
    json_fail('Błąd serwera: ' . $e->getMessage(), 500);
}

function record_exists(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM zgloszenia WHERE id = ?');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}
