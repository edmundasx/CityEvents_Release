<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Event.php';

function handleEvents(string $method, array $input): void
{
    $eventModel = new Event();

    switch ($method) {
        case 'GET':
            $events = $eventModel->findAll($_GET);
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

            $event = $eventModel->create($input);
            if ($event) {
                respond(['event' => $event], 201);
                return;
            }

            respond(['error' => 'Nepavyko sukurti renginio'], 500);
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

            $event = $eventModel->update((int)$eventId, $input);
            if ($event) {
                respond(['event' => $event]);
                return;
            }

            respond(['error' => 'Nepavyko atnaujinti renginio'], 500);
            return;

        case 'DELETE':
            $eventId = $_GET['id'] ?? null;
            if (!$eventId) {
                respond(['error' => 'Trūksta renginio ID'], 400);
                return;
            }

            if ($eventModel->delete((int)$eventId)) {
                respond(['message' => 'Renginys pašalintas']);
                return;
            }

            respond(['error' => 'Renginys nerastas'], 404);
            return;

        default:
            respond(['error' => 'Nepalaikomas metodas'], 405);
            return;
    }
}
