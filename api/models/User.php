<?php

class User
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute([':email' => $email]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function create(array $data): ?array
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, phone) VALUES (:name, :email, :password, :role, :phone)'
        );
        $statement->execute([
            ':name' => trim($data['name']),
            ':email' => strtolower($data['email']),
            ':password' => $hashedPassword,
            ':role' => $data['role'],
            ':phone' => $data['phone'] ?? null,
        ]);

        $userId = (int)$this->pdo->lastInsertId();
        return $this->find($userId);
    }

    public function update(int $id, array $data): ?array
    {
        $allowedFields = ['name', 'email', 'password', 'role', 'phone'];
        $setParts = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                if ($field === 'password') {
                    $setParts[] = "password = :password";
                    $params[":password"] = password_hash($data['password'], PASSWORD_DEFAULT);
                } else {
                    $setParts[] = "$field = :$field";
                    $params[":$field"] = $field === 'email' ? strtolower($data[$field]) : $data[$field];
                }
            }
        }

        if (empty($setParts)) {
            return null;
        }

        $updateQuery = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $update = $this->pdo->prepare($updateQuery);
        $update->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute([':id' => $id]);
        return $statement->rowCount() > 0;
    }
}
