<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 | API zgloszen serwisowych
 |  GET    /api/zgloszenia.php               -> lista zgloszen (razem z koszem)
 |  POST   action=create (multipart)         -> nowe zgloszenie + zdjecia
 |  POST   action=status                     -> zmiana statusu
 |  POST   action=confirm                    -> potwierdzenie zgloszenia z mobile (PC)
 |  POST   action=update                     -> edycja danych zgloszenia
 |  POST   action=services                    -> zapis wykonanych czynnosci (checkboxy)
 |  POST   action=notes                       -> zapis notatek z karty zgloszenia
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

            // Przyjęcie nie zapisuje wykonanych czynności: services_done zostaje
            // null (puste), checkboxy z karty zgłoszenia wypełniają je w trakcie
            // prac. Pole w żądaniu obsługujemy dla zgodności ze starym frontem.
            $servicesDone = validate_services_done((string) ($_POST['services_done'] ?? ''));
            $servicesJson = $servicesDone
                ? json_encode($servicesDone, JSON_UNESCAPED_UNICODE)
                : null;

            $me = (int) (auth_user()['id'] ?? 0);
            $stmt = db()->prepare(
                'INSERT INTO zgloszenia
                    (bike_name, date_in, date_planned, customer_phone,
                     fault_description, status, confirmed, services_done, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$bikeName, $dateIn, $datePlanned, $phone, $fault, $status, $confirmed, $servicesJson, $me ?: null]);
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

            $st = db()->prepare('SELECT status FROM zgloszenia WHERE id = ?');
            $st->execute([$id]);
            $current = $st->fetchColumn();
            if ($current === false) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            if ($status === 'picked_up' && $current !== 'picked_up') {
                // 3.5: wydanie roweru - zapisz, kto wydal (kólko na liscie)
                $me = (int) (auth_user()['id'] ?? 0);
                db()->prepare('UPDATE zgloszenia SET status = ?, confirmed_by = ? WHERE id = ?')
                    ->execute([$status, $me ?: null, $id]);
            } elseif ($current === 'picked_up' && $status !== 'picked_up') {
                // 3.5: cofniecie wydania - "kto wydal" ma zniknac z listy i karty
                db()->prepare('UPDATE zgloszenia SET status = ?, confirmed_by = NULL WHERE id = ?')
                    ->execute([$status, $id]);
            } else {
                db()->prepare('UPDATE zgloszenia SET status = ? WHERE id = ?')
                    ->execute([$status, $id]);
            }

            // 3.5: pelny rekord - po wydaniu/cofnieciu zmienia sie tez "kto wydal",
            // frontend podmienia go w cache, zeby kólko na liscie zniknelo od razu
            $row = db()->prepare('SELECT * FROM zgloszenia WHERE id = ?');
            $row->execute([$id]);
            json_out([
                'success' => true,
                'id'      => $id,
                'status'  => $status,
                'data'    => map_zgloszenie($row->fetch()),
            ]);
        }

        if ($action === 'confirm') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }

            // 3.2: kto wydal rower (klikniecie "Wydaj rower")
            $me = (int) (auth_user()['id'] ?? 0);
            $stmt = db()->prepare('UPDATE zgloszenia SET confirmed = 1, confirmed_by = ? WHERE id = ?');
            $stmt->execute([$me ?: null, $id]);
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

        if ($action === 'services') {
            // Zapis zaznaczonych checkboxów "Wykonane czynności" z karty zgłoszenia
            if (!modul('wykonane')) {
                json_fail('Moduł „Wykonane czynności” jest wyłączony w ustawieniach panelu.', 403);
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }
            if (!record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            $servicesDone = validate_services_done((string) ($_POST['services_done'] ?? ''));
            // '[]' = zapisano i nic nie zaznaczono; null = jeszcze nie zapisywano
            $servicesJson = $servicesDone
                ? json_encode($servicesDone, JSON_UNESCAPED_UNICODE)
                : '[]';

            db()->prepare('UPDATE zgloszenia SET services_done = ? WHERE id = ?')
                ->execute([$servicesJson, $id]);

            $row = db()->prepare('SELECT * FROM zgloszenia WHERE id = ?');
            $row->execute([$id]);

            json_out(['success' => true, 'data' => map_zgloszenie($row->fetch())]);
        }

        if ($action === 'notes') {
            // Zapis notatek z karty zgłoszenia (autozapis jak przy checkboxach)
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_fail('Brak identyfikatora zgłoszenia.');
            }
            if (!record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }

            $notes = trim((string) ($_POST['service_notes'] ?? ''));
            if (mb_strlen($notes) > 6000) {
                json_fail('Notatka jest za długa (max 6000 znaków).');
            }

            db()->prepare('UPDATE zgloszenia SET service_notes = ? WHERE id = ?')
                ->execute([$notes ?: null, $id]);

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
            if (!record_exists($id)) {
                json_fail('Zgłoszenie nie istnieje.', 404);
            }
            // 3.2: pracownik przywraca tylko własne zgłoszenia (admin - każde)
            owner_guard($id);

            $stmt = db()->prepare('UPDATE zgloszenia SET deleted_at = NULL WHERE id = ?');
            $stmt->execute([$id]);

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
            // Trwałe usunięcie: zdjęcia z dysku + wiersz z bazy.
            // 3.1: tylko admin (niszczymy zdjęcia nieodwracalnie)
            auth_require_admin();
            delete_photos_of($id);
            db()->prepare('DELETE FROM zgloszenia WHERE id = ?')->execute([$id]);
            json_out(['success' => true, 'id' => $id, 'purged' => true]);
        }

        // 3.2: pracownik kasuje (kosz) tylko własne zgłoszenia; admin - każde
        owner_guard($id);

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

/**
 * 3.2 - wlasnosc zgloszenia: pracownik kasuje/przywraca tylko wlasne
 * (created_by = jego id); admin przechodzi bez ograniczen. Rekordy sprzed
 * wdrozenia (created_by = NULL) kasuje wiec tylko admin.
 */
function owner_guard(int $id): void
{
    if (auth_is_admin()) {
        return;
    }
    $stmt = db()->prepare('SELECT created_by FROM zgloszenia WHERE id = ?');
    $stmt->execute([$id]);
    $owner = $stmt->fetchColumn();
    if ($owner === false || $owner === null || (int) $owner !== (int) (auth_user()['id'] ?? 0)) {
        json_fail('Możesz kasować i przywracać tylko własne zgłoszenia.', 403);
    }
}
