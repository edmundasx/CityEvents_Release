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
        $statement = $this->pdo->prepare(
            'SELECT f.id AS favorite_id, f.event_id, f.created_at AS favorite_created_at, f.tag, e.* ' .
            'FROM favorites f ' .
            'JOIN events e ON f.event_id = e.id ' .
            'WHERE f.user_id = :user_id ' .
            'ORDER BY f.created_at DESC'
        );
        $statement->execute([':user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function findByUserAndEvent(int $userId, int $eventId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM favorites WHERE user_id = :user_id AND event_id = :event_id LIMIT 1');
        $statement->execute([
            ':user_id' => $userId,
            ':event_id' => $eventId,
        ]);

        $favorite = $statement->fetch();
        return $favorite ?: null;
    }

    public function deleteByUserAndEvent(int $userId, int $eventId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM favorites WHERE user_id = :user_id AND event_id = :event_id');
        $statement->execute([
            ':user_id' => $userId,
            ':event_id' => $eventId,
        ]);

        return $statement->rowCount() > 0;
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

    public function findWithEvent(int $favoriteId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT f.id AS favorite_id, f.event_id, f.created_at AS favorite_created_at, f.tag, e.* ' .
            'FROM favorites f JOIN events e ON f.event_id = e.id WHERE f.id = :id LIMIT 1'
        );
        $statement->execute([':id' => $favoriteId]);

        $favorite = $statement->fetch();
        return $favorite ?: null;
    }
}
