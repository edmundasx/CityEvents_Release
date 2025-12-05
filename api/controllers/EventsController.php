<?php

require_once __DIR__ . '/../helpers.php';

function handleEvents(string $method, array &$data, array $input, string $dataFile): void
{
    switch ($method) {
        case 'GET':
            $events = filter_events($data['events'], $_GET);
            respond(['events' => $events]);
            return;

        case 'POST':
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
