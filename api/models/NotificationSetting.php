<?php

class NotificationSetting
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM notification_settings WHERE user_id = :user_id');
        $statement->execute([':user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function findByUserAndEvent(int $userId, int $eventId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM notification_settings WHERE user_id = :user_id AND event_id = :event_id LIMIT 1');
        $statement->execute([':user_id' => $userId, ':event_id' => $eventId]);
        $setting = $statement->fetch();
        return $setting ?: null;
    }

    public function create(array $data): ?array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO notification_settings (user_id, event_id, time_offset, channels) VALUES (:user_id, :event_id, :time_offset, :channels)'
        );
        $statement->execute([
            ':user_id' => $data['user_id'],
            ':event_id' => $data['event_id'],
            ':time_offset' => $data['time_offset'],
            ':channels' => json_encode($data['channels']),
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }
    
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM notification_settings WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $setting = $statement->fetch();
        return $setting ?: null;
    }

    public function deleteByUserAndEvent(int $userId, int $eventId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM notification_settings WHERE user_id = :user_id AND event_id = :event_id');
        $statement->execute([':user_id' => $userId, ':event_id' => $eventId]);
        return $statement->rowCount() > 0;
    }
}
