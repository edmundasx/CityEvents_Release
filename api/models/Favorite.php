<?php

class Favorite
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM favorites WHERE user_id = :user_id');
        $statement->execute([':user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function create(array $data): ?array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO favorites (event_id, user_id, tag) VALUES (:event_id, :user_id, :tag)'
        );
        $statement->execute([
            ':event_id' => $data['event_id'],
            ':user_id' => $data['user_id'],
            ':tag' => $data['tag'] ?? 'favorite',
        ]);

        $favId = (int)$this->pdo->lastInsertId();
        $statement = $this->pdo->prepare('SELECT * FROM favorites WHERE id = :id');
        $statement->execute([':id' => $favId]);
        return $statement->fetch() ?: null;
    }
}
