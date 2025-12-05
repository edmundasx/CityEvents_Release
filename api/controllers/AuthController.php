<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

function handleAuth(string $method, array $input): void
{
    if ($method !== 'POST') {
        respond(['error' => 'Nepalaikomas metodas'], 405);
        return;
    }

    $missing = ensure_required_fields($input, ['email', 'password']);
    if ($missing) {
        respond(['error' => 'Trūksta prisijungimo duomenų', 'fields' => $missing], 400);
        return;
    }

    $userModel = new User();
    $user = $userModel->findByEmail($input['email']);

    if ($user && password_verify($input['password'], $user['password'])) {
        respond(['user' => sanitize_user($user)]);
        return;
    }

    respond(['error' => 'Neteisingi prisijungimo duomenys'], 401);
    return;
}
