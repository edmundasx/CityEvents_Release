<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$resource = $_GET['resource'] ?? 'events';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$dataFile = __DIR__ . '/data.json';

function default_data(): array
{
    $now = time();
    return [
        'users' => [
            ['id' => 1, 'name' => 'Asta Vartotoja', 'email' => 'asta@cityevents.lt', 'password' => 'slaptas', 'role' => 'user'],
            ['id' => 2, 'name' => 'Organizatorius Jonas', 'email' => 'jonas@cityevents.lt', 'password' => 'organizer', 'role' => 'organizer'],
            ['id' => 3, 'name' => 'Administratorė Ieva', 'email' => 'admin@cityevents.lt', 'password' => 'admin', 'role' => 'admin'],
        ],
        'blocked_users' => [],
        'favorites' => [],
        'notifications' => [
            ['id' => 1, 'type' => 'admin', 'message' => '2 renginiai laukia patvirtinimo', 'created_at' => 'prieš 5 min.'],
            ['id' => 2, 'type' => 'organizer', 'message' => '„Miesto mugė“ patvirtinta ir matoma lankytojams', 'created_at' => 'prieš 1 val.'],
            ['id' => 3, 'type' => 'user', 'message' => 'Naujų renginių pagal jūsų pomėgius: muzika', 'created_at' => 'vakar'],
        ],
        'events' => [
            [
                'id' => 1,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Miesto mugė',
                'description' => 'Sezoninė miesto mugė su vietiniais gamintojais ir scena',
                'category' => 'food',
                'location' => 'Rotušės aikštė',
                'lat' => 54.6872,
                'lng' => 25.2797,
                'event_date' => date(DATE_ATOM, $now + 7 * 86400),
                'price' => 0,
                'status' => 'approved',
                'cover_image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'id' => 2,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Technologijų vakaras',
                'description' => 'Diskusijos ir dirbtuvės apie inovacijas',
                'category' => 'business',
                'location' => 'Technopolis Vilnius',
                'lat' => 54.6690,
                'lng' => 25.2747,
                'event_date' => date(DATE_ATOM, $now + 14 * 86400),
                'price' => 15,
                'status' => 'pending',
                'cover_image' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id' => 3,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Muzikos piknikas',
                'description' => 'Gyva muzika parke ir maisto furgonai',
                'category' => 'music',
                'location' => 'Bernardinų sodas',
                'lat' => 54.6840,
                'lng' => 25.2900,
                'event_date' => date(DATE_ATOM, $now + 3 * 86400),
                'price' => 5,
                'status' => 'approved',
                'cover_image' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id' => 4,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Urban Run',
                'description' => '5 km miesto bėgimas palei upę su muzika finiše',
                'category' => 'sports',
                'location' => 'Neries pakrantė',
                'lat' => 54.6890,
                'lng' => 25.2660,
                'event_date' => date(DATE_ATOM, $now - 10 * 86400),
                'price' => 0,
                'status' => 'approved',
                'cover_image' => 'https://images.unsplash.com/photo-1508609349937-5ec4ae374ebf?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id' => 5,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Kino vakaras po atviru dangumi',
                'description' => 'Vasaros kino seansas su vietos režisieriumi',
                'category' => 'arts',
                'location' => 'Valdovų rūmų kiemas',
                'lat' => 54.6850,
                'lng' => 25.2890,
                'event_date' => date(DATE_ATOM, $now + 21 * 86400),
                'price' => 8,
                'status' => 'pending',
                'cover_image' => 'https://images.unsplash.com/photo-1517602302552-471fe67acf66?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id' => 6,
                'organizer_id' => 2,
                'organizer_name' => 'Organizatorius',
                'title' => 'Startuolių pusryčiai',
                'description' => 'Tinklaveikos susitikimas ankstyvą rytą su kava ir investuotojais',
                'category' => 'business',
                'location' => 'Vilnius Tech Park',
                'lat' => 54.6800,
                'lng' => 25.2870,
                'event_date' => date(DATE_ATOM, $now + 35 * 86400),
                'price' => 12,
                'status' => 'approved',
                'cover_image' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80',
            ],
        ],
    ];
}

function load_data(string $file): array
{
    if (is_file($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return default_data();
}

function save_data(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function sanitize_user(array $user): array
{
    unset($user['password']);
    return $user;
}

$data = load_data($dataFile);

function filter_events(array $events, array $params): array
{
    $filtered = array_values(array_filter($events, function ($event) use ($params) {
        if (!empty($params['id']) && (string)$event['id'] !== (string)$params['id']) {
            return false;
        }
        if (!empty($params['organizer_id']) && (string)$event['organizer_id'] !== (string)$params['organizer_id']) {
            return false;
        }
        if (!empty($params['category']) && $event['category'] !== $params['category']) {
            return false;
        }
        if (!empty($params['search'])) {
            $needle = mb_strtolower($params['search']);
            $haystack = mb_strtolower(($event['title'] ?? '') . ' ' . ($event['description'] ?? ''));
            if (strpos($haystack, $needle) === false) {
                return false;
            }
        }
        if (!empty($params['location'])) {
            $needle = mb_strtolower($params['location']);
            if (strpos(mb_strtolower($event['location'] ?? ''), $needle) === false) {
                return false;
            }
        }
        if (empty($params['include_all']) && ($event['status'] ?? '') !== 'approved') {
            return false;
        }
        return true;
    }));

    return $filtered;
}

try {
    switch ($resource) {
        case 'events':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $events = filter_events($data['events'], $_GET);
                respond(['events' => $events]);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $organizerId = $input['organizer_id'] ?? null;
                $organizer = null;
                if ($organizerId) {
                    foreach ($data['users'] as $user) {
                        if ((string)$user['id'] === (string)$organizerId) {
                            $organizer = $user;
                            break;
                        }
                    }
                }
                $new = [
                    'id' => round(microtime(true) * 1000),
                    'organizer_id' => $organizerId,
                    'organizer_name' => $organizer['name'] ?? 'Organizatorius',
                    'title' => $input['title'] ?? 'Naujas renginys',
                    'description' => $input['description'] ?? '',
                    'category' => $input['category'] ?? 'other',
                    'location' => $input['location'] ?? 'Nepateikta',
                    'lat' => $input['lat'] ?? null,
                    'lng' => $input['lng'] ?? null,
                    'event_date' => $input['event_date'] ?? date(DATE_ATOM, time() + 86400),
                    'price' => $input['price'] ?? 0,
                    'status' => 'pending',
                    'cover_image' => $input['cover_image'] ?? '',
                ];
                $data['events'][] = $new;
                save_data($dataFile, $data);
                respond(['event' => $new]);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $eventId = $input['id'] ?? null;
                if (!$eventId) {
                    respond(['error' => 'Trūksta renginio ID'], 400);
                }

                $allowedKeys = ['title', 'description', 'category', 'location', 'lat', 'lng', 'event_date', 'price', 'status', 'cover_image'];
                foreach ($data['events'] as &$event) {
                    if ((string)$event['id'] === (string)$eventId) {
                        $event = array_merge($event, array_filter($input, function ($value, $key) use ($allowedKeys) {
                            return in_array($key, $allowedKeys, true) && $value !== null && $value !== '';
                        }, ARRAY_FILTER_USE_BOTH));
                        save_data($dataFile, $data);
                        respond(['event' => $event]);
                    }
                }

                respond(['error' => 'Renginys nerastas'], 404);
            }
            respond(['error' => 'Nepalaikomas metodas'], 405);

        case 'notifications':
            respond(['notifications' => $data['notifications']]);

        case 'favorites':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $entry = [
                    'id' => round(microtime(true) * 1000),
                    'event_id' => $input['event_id'] ?? null,
                    'user_id' => $input['user_id'] ?? null,
                    'tag' => $input['tag'] ?? 'favorite',
                ];
                $data['favorites'][] = $entry;
                save_data($dataFile, $data);
                respond(['favorite' => $entry]);
            }
            respond(['error' => 'Nepalaikomas metodas'], 405);

        case 'auth':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['error' => 'Nepalaikomas metodas'], 405);
            }
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            foreach ($data['users'] as $user) {
                if (strcasecmp($user['email'], $email) === 0 && $user['password'] === $password) {
                    respond(['user' => sanitize_user($user)]);
                }
            }
            respond(['error' => 'Neteisingi prisijungimo duomenys'], 401);

        case 'users':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    respond(['error' => 'Trūksta naudotojo ID'], 400);
                }
                foreach ($data['users'] as $user) {
                    if ((string)$user['id'] === (string)$id) {
                        respond(['user' => sanitize_user($user)]);
                    }
                }
                respond(['error' => 'Naudotojas nerastas'], 404);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $newId = round(microtime(true) * 1000);
                $newUser = [
                    'id' => $newId,
                    'name' => $input['name'] ?? 'Naujas vartotojas',
                    'email' => $input['email'] ?? '',
                    'password' => $input['password'] ?? '',
                    'role' => $input['role'] ?? 'user',
                ];
                $data['users'][] = $newUser;
                save_data($dataFile, $data);
                respond(['user' => sanitize_user($newUser)]);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $id = $input['id'] ?? null;
                if (!$id) {
                    respond(['error' => 'Trūksta naudotojo ID'], 400);
                }
                foreach ($data['users'] as &$user) {
                    if ((string)$user['id'] === (string)$id) {
                        $user = array_merge($user, array_filter($input, function ($value, $key) {
                            return in_array($key, ['name', 'email', 'password', 'role', 'phone'], true) && $value !== null && $value !== '';
                        }, ARRAY_FILTER_USE_BOTH));
                        save_data($dataFile, $data);
                        respond(['user' => sanitize_user($user)]);
                    }
                }
                respond(['error' => 'Naudotojas nerastas'], 404);
            }
            respond(['error' => 'Nepalaikomas metodas'], 405);

        case 'admin':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['error' => 'Nepalaikomas metodas'], 405);
            }
            $action = $input['action'] ?? '';
            if ($action === 'update_status') {
                $eventId = $input['event_id'] ?? null;
                foreach ($data['events'] as &$event) {
                    if ((string)$event['id'] === (string)$eventId) {
                        $event['status'] = $input['status'] ?? $event['status'];
                        if (!empty($input['reason'])) {
                            $event['rejection_reason'] = $input['reason'];
                        }
                        $noteId = round(microtime(true) * 1000);
                        $data['notifications'][] = [
                            'id' => $noteId,
                            'type' => 'organizer',
                            'message' => sprintf('„%s“ %s.', $event['title'], $event['status']),
                            'created_at' => 'dabar',
                        ];
                        save_data($dataFile, $data);
                        respond(['event' => $event]);
                    }
                }
                respond(['error' => 'Renginys nerastas'], 404);
            }
            if ($action === 'block_user') {
                $userId = $input['user_id'] ?? null;
                if ($userId && !in_array($userId, $data['blocked_users'], true)) {
                    $data['blocked_users'][] = $userId;
                    save_data($dataFile, $data);
                }
                respond(['blocked_users' => $data['blocked_users']]);
            }
            respond(['error' => 'Nežinomas veiksmas'], 400);

        default:
            respond(['error' => 'Nežinomas resursas'], 404);
    }
} catch (Throwable $e) {
    respond(['error' => 'Serverio klaida', 'details' => $e->getMessage()], 500);
}
