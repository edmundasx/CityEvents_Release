<?php

require_once __DIR__ . '/../helpers.php';

function handleUsers(string $method, array &$data, array $input, string $dataFile): void
{
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }
            foreach ($data['users'] as $user) {
                if ((string)$user['id'] === (string)$id) {
                    respond(['user' => sanitize_user($user)]);
                    return;
                }
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

            foreach ($data['users'] as $existingUser) {
                if (strcasecmp($existingUser['email'], $input['email']) === 0) {
                    respond(['error' => 'Vartotojas su tokiu el. paštu jau egzistuoja'], 400);
                    return;
                }
            }

            $newId = round(microtime(true) * 1000);
            $newUser = [
                'id' => $newId,
                'name' => trim($input['name']),
                'email' => strtolower($input['email']),
                'password' => $input['password'],
                'role' => $input['role'],
            ];
            $data['users'][] = $newUser;
            save_data($dataFile, $data);
            respond(['user' => sanitize_user($newUser)], 201);
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

            foreach ($data['users'] as &$user) {
                if ((string)$user['id'] === (string)$id) {
                    $user = array_merge($user, array_filter($input, function ($value, $key) {
                        return in_array($key, ['name', 'email', 'password', 'role', 'phone'], true) && $value !== null && $value !== '';
                    }, ARRAY_FILTER_USE_BOTH));
                    save_data($dataFile, $data);
                    respond(['user' => sanitize_user($user)]);
                    return;
                }
            }

            respond(['error' => 'Naudotojas nerastas'], 404);
            return;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }

            foreach ($data['users'] as $index => $user) {
                if ((string)$user['id'] === (string)$id) {
                    array_splice($data['users'], $index, 1);
                    save_data($dataFile, $data);
                    respond(['message' => 'Naudotojas pašalintas']);
                    return;
                }
            }

            respond(['error' => 'Naudotojas nerastas'], 404);
            return;

        default:
            respond(['error' => 'Nepalaikomas metodas'], 405);
            return;
    }
}
