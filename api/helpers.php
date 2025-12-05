<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

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

/**
 * Loads data from a JSON file. This is part of the old file-based data
 * system and is still required by some parts of the application.
 */
function load_data(string $filePath, string $defaultKey = 'data'): array
{
    if (!file_exists($filePath)) {
        return [$defaultKey => []];
    }
    $json = file_get_contents($filePath);
    return json_decode($json, true) ?: [$defaultKey => []];
}
