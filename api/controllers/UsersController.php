<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

function handleUsers(string $method, array $input): void
{
    $userModel = new User();

    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }

            $user = $userModel->find((int)$id);
            if ($user) {
                respond(['user' => sanitize_user($user)]);
                return;
            }

            respond(['error' => 'Naudotojas nerastas'], 404);
            return;

        case 'POST':
            $missing = ensure_required_fields($input, ['name', 'email', 'password', 'role']);
            if ($missing) {
                respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
                return;
            }

            if (!validate_email($input['email'])) {
                respond(['error' => 'Neteisingas el. pašto formatas'], 400);
                return;
            }

            if (!validate_user_role($input['role'])) {
                respond(['error' => 'Neteisinga rolė'], 400);
                return;
            }

            $existingUser = $userModel->findByEmail($input['email']);
            if ($existingUser) {
                respond(['error' => 'Vartotojas su tokiu el. paštu jau egzistuoja'], 400);
                return;
            }

            $user = $userModel->create($input);
            if ($user) {
                respond(['user' => sanitize_user($user)], 201);
                return;
            }
            
            respond(['error' => 'Nepavyko sukurti vartotojo'], 500);
            return;

        case 'PUT':
            $id = $input['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }

            if (isset($input['email']) && $input['email'] !== '' && !validate_email($input['email'])) {
                respond(['error' => 'Neteisingas el. pašto formatas'], 400);
                return;
            }

            if (isset($input['role']) && $input['role'] !== '' && !validate_user_role($input['role'])) {
                respond(['error' => 'Neteisinga rolė'], 400);
                return;
            }

            $user = $userModel->update((int)$id, $input);

            if ($user) {
                respond(['user' => sanitize_user($user)]);
                return;
            }

            respond(['error' => 'Nepavyko atnaujinti vartotojo'], 500);
            return;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }

            if ($userModel->delete((int)$id)) {
                respond(['message' => 'Naudotojas pašalintas']);
                return;
            }

            respond(['error' => 'Naudotojas nerastas'], 404);
            return;

        default:
            respond(['error' => 'Nepalaikomas metodas'], 405);
            return;
    }
}
