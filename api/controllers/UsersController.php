<?php

require_once __DIR__ . '/../helpers.php';

function handleUsers(string $method, array &$data, array $input, string $dataFile): void
{
    $pdo = get_pdo();

    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                respond(['error' => 'Trūksta naudotojo ID'], 400);
                return;
            }

            if ($pdo) {
                $statement = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
                $statement->execute([':id' => $id]);
                $user = $statement->fetch();
                if ($user) {
                    respond(['user' => sanitize_user($user)]);
                    return;
                }

                respond(['error' => 'Naudotojas nerastas'], 404);
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

            if ($pdo) {
                $duplicateCheck = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
                $duplicateCheck->execute([':email' => $input['email']]);
                if ($duplicateCheck->fetch()) {
                    respond(['error' => 'Vartotojas su tokiu el. paštu jau egzistuoja'], 400);
                    return;
                }

                $statement = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role, phone) VALUES (:name, :email, :password, :role, :phone)'
                );
                $statement->execute([
                    ':name' => trim($input['name']),
                    ':email' => strtolower($input['email']),
                    ':password' => $input['password'],
                    ':role' => $input['role'],
                    ':phone' => $input['phone'] ?? null,
                ]);

                $userId = (int)$pdo->lastInsertId();
                $created = [
                    'id' => $userId,
                    'name' => trim($input['name']),
                    'email' => strtolower($input['email']),
                    'role' => $input['role'],
                    'phone' => $input['phone'] ?? null,
                ];

                respond(['user' => $created], 201);
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

            if ($pdo) {
                $id = $input['id'] ?? null;
                if (!$id) {
                    respond(['error' => 'Trūksta naudotojo ID'], 400);
                    return;
                }

                $allowedFields = ['name', 'email', 'password', 'role', 'phone'];
                $setParts = [];
                $params = [':id' => $id];

                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                        if ($field === 'email' && !validate_email($input[$field])) {
                            respond(['error' => 'Neteisingas el. pašto formatas'], 400);
                            return;
                        }
                        if ($field === 'role' && !validate_user_role($input[$field])) {
                            respond(['error' => 'Neteisinga rolė'], 400);
                            return;
                        }

                        $setParts[] = "$field = :$field";
                        $params[":$field"] = $field === 'email' ? strtolower($input[$field]) : $input[$field];
                    }
                }

                if (empty($setParts)) {
                    respond(['error' => 'Nėra laukų atnaujinimui'], 400);
                    return;
                }

                $existing = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
                $existing->execute([':id' => $id]);
                if (!$existing->fetch()) {
                    respond(['error' => 'Naudotojas nerastas'], 404);
                    return;
                }

                $updateQuery = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
                $update = $pdo->prepare($updateQuery);
                $update->execute($params);

                $fresh = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
                $fresh->execute([':id' => $id]);
                $updated = $fresh->fetch();

                respond(['user' => sanitize_user($updated)]);
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

            if ($pdo) {
                $statement = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $statement->execute([':id' => $id]);

                if ($statement->rowCount() === 0) {
                    respond(['error' => 'Naudotojas nerastas'], 404);
                    return;
                }

                respond(['message' => 'Naudotojas pašalintas']);
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
