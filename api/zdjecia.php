<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API zdjęć zgłoszeń
 |  GET    /api/zdjecia.php?zgloszenie_id=N  -> lista zdjęć
 |  POST   (multipart, zgloszenie_id=N)      -> dodaj zdjęcia
 |  DELETE /api/zdjecia.php?id=N             -> usuń zdjęcie
 --------------------------------------------------------------- */

// Brak config.php = instalacja nieukonczona -> JSON zamiast bledu PHP.
if (!is_file(__DIR__ . '/../config.php')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Instalacja nie zostala zakonczona - uruchom install.php']);
    exit;
}

require __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    auth_require();
    // Moduł "zdjęcia" wyłączony przez użytkownika = całe API zdjęć nieczynne
    if (!modul('zdjecia')) {
        json_fail('Moduł zdjęć jest wyłączony w ustawieniach panelu.', 403);
    }
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
        // 3.10.0: guard własności (jak w zgloszenia.php) - pracownik usuwa
        // zdjęcia tylko własnych zgłoszeń i nie z kosza.
        if (!delete_photo($id, true)) {
            json_fail('Zdjęcie nie istnieje.', 404);
        }
        json_out(['success' => true, 'id' => $id]);
    }

    json_fail('Metoda nieobsługiwana.', 405);
} catch (Throwable $e) {
    json_fail_internal('zdjecia.php', $e);
}
