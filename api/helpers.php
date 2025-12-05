<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

function get_pdo(): ?PDO
{
    static $pdo;
    static $initialized = false;

    if ($initialized) {
        return $pdo;
    }

    $initialized = true;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'cityevents';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        $pdo = null;
    }

    return $pdo;
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function load_data(string $file, callable $defaultProvider): array
{
    if (is_file($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
    }

    return $defaultProvider();
}

function save_data(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function sanitize_user(array $user): array
{
    unset($user['password']);
    return $user;
}

function validate_user_role(string $role): bool
{
    return in_array($role, ['user', 'organizer', 'admin'], true);
}

function ensure_required_fields(array $input, array $fields): array
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            $missing[] = $field;
        }
    }

    return $missing;
}

function validate_email(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function require_admin(array $config): void
{
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
    if (empty($config['admin_token']) || $token !== $config['admin_token']) {
        respond(['error' => 'Prieiga negalima'], 403);
    }
}

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
