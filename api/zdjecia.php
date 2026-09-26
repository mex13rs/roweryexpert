<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API zdjęć zgłoszeń
 |  GET    /api/zdjecia.php?zgloszenie_id=N  -> lista zdjęć
 |  POST   (multipart, zgloszenie_id=N)      -> dodaj zdjęcia
 |  DELETE /api/zdjecia.php?id=N             -> usuń zdjęcie
 --------------------------------------------------------------- */

require __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    auth_require();
    if ($method === 'GET') {
        $zgloszenieId = (int) ($_GET['zgloszenie_id'] ?? 0);
        if ($zgloszenieId <= 0) {
            json_fail('Brak identyfikatora zgłoszenia.');
        }
        json_out(['success' => true, 'data' => photos_for($zgloszenieId)]);
    }

    if ($method === 'POST') {
        $zgloszenieId = (int) ($_POST['zgloszenie_id'] ?? 0);
        if ($zgloszenieId <= 0) {
            json_fail('Brak identyfikatora zgłoszenia.');
        }

        $stmt = db()->prepare('SELECT 1 FROM zgloszenia WHERE id = ?');
        $stmt->execute([$zgloszenieId]);
        if (!$stmt->fetchColumn()) {
            json_fail('Zgłoszenie nie istnieje.', 404);
        }

        if (empty($_FILES['photos'])) {
            json_fail('Nie wybrano żadnych plików.');
        }

        $saved = store_photos($zgloszenieId, $_FILES['photos']);
        json_out(['success' => true, 'data' => $saved], 201);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_fail('Brak identyfikatora zdjęcia.');
        }
        if (!delete_photo($id)) {
            json_fail('Zdjęcie nie istnieje.', 404);
        }
        json_out(['success' => true, 'id' => $id]);
    }

    json_fail('Metoda nieobsługiwana.', 405);
} catch (Throwable $e) {
    error_log('[zdjecia.php] ' . $e->getMessage());
    json_fail('Błąd serwera: ' . $e->getMessage(), 500);
}
