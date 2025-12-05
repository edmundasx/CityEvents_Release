<?php

require_once __DIR__ . '/../helpers.php';

function handleAuth(string $method, array $data, array $input): void
{
    $pdo = get_pdo();

    if ($method !== 'POST') {
        respond(['error' => 'Nepalaikomas metodas'], 405);
        return;
    }

    $missing = ensure_required_fields($input, ['email', 'password']);
    if ($missing) {
        respond(['error' => 'Trūksta prisijungimo duomenų', 'fields' => $missing], 400);
        return;
    }

    if ($pdo) {
        $statement = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute([':email' => $input['email']]);
        $user = $statement->fetch();

        if ($user && $user['password'] === $input['password']) {
            respond(['user' => sanitize_user($user)]);
            return;
        }

        respond(['error' => 'Neteisingi prisijungimo duomenys'], 401);
        return;
    }

    foreach ($data['users'] as $user) {
        if (strcasecmp($user['email'], $input['email']) === 0 && $user['password'] === $input['password']) {
            respond(['user' => sanitize_user($user)]);
            return;
        }
    }

    respond(['error' => 'Neteisingi prisijungimo duomenys'], 401);
    return;
}
