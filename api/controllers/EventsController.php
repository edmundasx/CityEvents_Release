<?php

require_once __DIR__ . '/../helpers.php';

function handleEvents(string $method, array &$data, array $input, string $dataFile): void
{
    $pdo = get_pdo();

    switch ($method) {
        case 'GET':
            if ($pdo) {
                $query = 'SELECT * FROM events WHERE 1=1';
                $params = [];

                if (!empty($_GET['id'])) {
                    $query .= ' AND id = :id';
                    $params[':id'] = $_GET['id'];
                }

                if (!empty($_GET['organizer_id'])) {
                    $query .= ' AND organizer_id = :organizer_id';
                    $params[':organizer_id'] = $_GET['organizer_id'];
                }

                if (!empty($_GET['category'])) {
                    $query .= ' AND category = :category';
                    $params[':category'] = $_GET['category'];
                }

                if (!empty($_GET['search'])) {
                    $query .= ' AND (LOWER(title) LIKE :search OR LOWER(description) LIKE :search)';
                    $params[':search'] = '%' . mb_strtolower($_GET['search']) . '%';
                }

                if (!empty($_GET['location'])) {
                    $query .= ' AND LOWER(location) LIKE :location';
                    $params[':location'] = '%' . mb_strtolower($_GET['location']) . '%';
                }

                if (empty($_GET['include_all'])) {
                    $query .= " AND status = 'approved'";
                }

                $query .= ' ORDER BY event_date ASC';

                $statement = $pdo->prepare($query);
                $statement->execute($params);
                $events = $statement->fetchAll();
                respond(['events' => $events]);
                return;
            }

            $events = filter_events($data['events'], $_GET);
            respond(['events' => $events]);
            return;

        case 'POST':
            if ($pdo) {
                $missing = ensure_required_fields($input, ['organizer_id', 'title', 'description', 'category', 'location', 'event_date']);
                if ($missing) {
                    respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
                    return;
                }

                if (isset($input['price']) && !is_numeric($input['price'])) {
                    respond(['error' => 'Neteisinga kainos reikšmė'], 400);
                    return;
                }

                $organizerName = 'Organizatorius';
                $userLookup = $pdo->prepare('SELECT name FROM users WHERE id = :id LIMIT 1');
                $userLookup->execute([':id' => $input['organizer_id']]);
                $userRow = $userLookup->fetch();
                if ($userRow && !empty($userRow['name'])) {
                    $organizerName = $userRow['name'];
                }

                $statement = $pdo->prepare(
                    'INSERT INTO events (organizer_id, organizer_name, title, description, category, location, lat, lng, event_date, price, status, cover_image) ' .
                    'VALUES (:organizer_id, :organizer_name, :title, :description, :category, :location, :lat, :lng, :event_date, :price, :status, :cover_image)'
                );

                $payload = [
                    ':organizer_id' => $input['organizer_id'],
                    ':organizer_name' => $organizerName,
                    ':title' => trim($input['title']),
                    ':description' => trim($input['description']),
                    ':category' => $input['category'],
                    ':location' => trim($input['location']),
                    ':lat' => $input['lat'] ?? null,
                    ':lng' => $input['lng'] ?? null,
                    ':event_date' => $input['event_date'],
                    ':price' => isset($input['price']) ? (float)$input['price'] : 0.00,
                    ':status' => 'pending',
                    ':cover_image' => $input['cover_image'] ?? null,
                ];

                $statement->execute($payload);

                $eventId = (int)$pdo->lastInsertId();
                $createdEvent = array_merge(['id' => $eventId], array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, [
                    'organizer_id' => $input['organizer_id'],
                    'organizer_name' => $organizerName,
                    'title' => $input['title'],
                    'description' => $input['description'],
                    'category' => $input['category'],
                    'location' => $input['location'],
                    'lat' => $input['lat'] ?? null,
                    'lng' => $input['lng'] ?? null,
                    'event_date' => $input['event_date'],
                    'price' => isset($input['price']) ? (float)$input['price'] : 0.00,
                    'status' => 'pending',
                    'cover_image' => $input['cover_image'] ?? null,
                ]));

                respond(['event' => $createdEvent], 201);
                return;
            }

            $missing = ensure_required_fields($input, ['organizer_id', 'title', 'description', 'category', 'location', 'event_date']);
            if ($missing) {
                respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
                return;
            }

            if (isset($input['price']) && !is_numeric($input['price'])) {
                respond(['error' => 'Neteisinga kainos reikšmė'], 400);
                return;
            }

            $organizerId = $input['organizer_id'];
            $organizer = null;
            foreach ($data['users'] as $user) {
                if ((string)$user['id'] === (string)$organizerId) {
                    $organizer = $user;
                    break;
                }
            }

            $new = [
                'id' => round(microtime(true) * 1000),
                'organizer_id' => $organizerId,
                'organizer_name' => $organizer['name'] ?? 'Organizatorius',
                'title' => trim($input['title']),
                'description' => trim($input['description']),
                'category' => $input['category'],
                'location' => trim($input['location']),
                'lat' => $input['lat'] ?? null,
                'lng' => $input['lng'] ?? null,
                'event_date' => $input['event_date'],
                'price' => isset($input['price']) ? (float)$input['price'] : 0,
                'status' => 'pending',
                'cover_image' => $input['cover_image'] ?? '',
            ];

            $data['events'][] = $new;
            save_data($dataFile, $data);
            respond(['event' => $new], 201);
            return;

        case 'PUT':
            if ($pdo) {
                $eventId = $input['id'] ?? null;
                if (!$eventId) {
                    respond(['error' => 'Trūksta renginio ID'], 400);
                    return;
                }

                if (isset($input['price']) && $input['price'] !== '' && !is_numeric($input['price'])) {
                    respond(['error' => 'Neteisinga kainos reikšmė'], 400);
                    return;
                }

                $allowedKeys = ['title', 'description', 'category', 'location', 'lat', 'lng', 'event_date', 'price', 'status', 'cover_image'];
                $setParts = [];
                $params = [':id' => $eventId];

                foreach ($allowedKeys as $key) {
                    if (array_key_exists($key, $input) && $input[$key] !== null && $input[$key] !== '') {
                        $setParts[] = "$key = :$key";
                        $params[":$key"] = $key === 'price' ? (float)$input[$key] : $input[$key];
                    }
                }

                if (empty($setParts)) {
                    respond(['error' => 'Nėra laukų atnaujinimui'], 400);
                    return;
                }

                $statement = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
                $statement->execute([':id' => $eventId]);
                $existing = $statement->fetch();
                if (!$existing) {
                    respond(['error' => 'Renginys nerastas'], 404);
                    return;
                }

                $updateQuery = 'UPDATE events SET ' . implode(', ', $setParts) . ' WHERE id = :id';
                $update = $pdo->prepare($updateQuery);
                $update->execute($params);

                $fetchUpdated = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
                $fetchUpdated->execute([':id' => $eventId]);
                $updated = $fetchUpdated->fetch();

                respond(['event' => $updated]);
                return;
            }

            $eventId = $input['id'] ?? null;
            if (!$eventId) {
                respond(['error' => 'Trūksta renginio ID'], 400);
                return;
            }

            if (isset($input['price']) && $input['price'] !== '' && !is_numeric($input['price'])) {
                respond(['error' => 'Neteisinga kainos reikšmė'], 400);
                return;
            }

            $allowedKeys = ['title', 'description', 'category', 'location', 'lat', 'lng', 'event_date', 'price', 'status', 'cover_image'];
            foreach ($data['events'] as &$event) {
                if ((string)$event['id'] === (string)$eventId) {
                    $event = array_merge($event, array_filter($input, function ($value, $key) use ($allowedKeys) {
                        return in_array($key, $allowedKeys, true) && $value !== null && $value !== '';
                    }, ARRAY_FILTER_USE_BOTH));
                    save_data($dataFile, $data);
                    respond(['event' => $event]);
                    return;
                }
            }

            respond(['error' => 'Renginys nerastas'], 404);
            return;

        case 'DELETE':
            if ($pdo) {
                $eventId = $_GET['id'] ?? null;
                if (!$eventId) {
                    respond(['error' => 'Trūksta renginio ID'], 400);
                    return;
                }

                $statement = $pdo->prepare('DELETE FROM events WHERE id = :id');
                $statement->execute([':id' => $eventId]);

                if ($statement->rowCount() === 0) {
                    respond(['error' => 'Renginys nerastas'], 404);
                    return;
                }

                respond(['message' => 'Renginys pašalintas']);
                return;
            }

            $eventId = $_GET['id'] ?? null;
            if (!$eventId) {
                respond(['error' => 'Trūksta renginio ID'], 400);
                return;
            }

            foreach ($data['events'] as $index => $event) {
                if ((string)$event['id'] === (string)$eventId) {
                    array_splice($data['events'], $index, 1);
                    save_data($dataFile, $data);
                    respond(['message' => 'Renginys pašalintas']);
                    return;
                }
            }

            respond(['error' => 'Renginys nerastas'], 404);
            return;

        default:
            respond(['error' => 'Nepalaikomas metodas'], 405);
            return;
    }
}
