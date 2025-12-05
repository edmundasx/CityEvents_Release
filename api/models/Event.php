<?php

class Event
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $event = $statement->fetch();
        return $event ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $query = 'SELECT * FROM events WHERE 1=1';
        $params = [];

        if (!empty($filters['id'])) {
            $query .= ' AND id = :id';
            $params[':id'] = $filters['id'];
        }

        if (!empty($filters['organizer_id'])) {
            $query .= ' AND organizer_id = :organizer_id';
            $params[':organizer_id'] = $filters['organizer_id'];
        }

        if (!empty($filters['category'])) {
            $query .= ' AND category = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= ' AND (LOWER(title) LIKE :search OR LOWER(description) LIKE :search)';
            $params[':search'] = '%' . mb_strtolower($filters['search']) . '%';
        }

        if (!empty($filters['location'])) {
            $query .= ' AND LOWER(location) LIKE :location';
            $params[':location'] = '%' . mb_strtolower($filters['location']) . '%';
        }

        if (empty($filters['include_all'])) {
            $query .= " AND status = 'approved'";
        }

        $query .= ' ORDER BY event_date ASC';

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function create(array $data): ?array
    {
        $organizerName = 'Organizatorius';
        $userLookup = $this->pdo->prepare('SELECT name FROM users WHERE id = :id LIMIT 1');
        $userLookup->execute([':id' => $data['organizer_id']]);
        $userRow = $userLookup->fetch();
        if ($userRow && !empty($userRow['name'])) {
            $organizerName = $userRow['name'];
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO events (organizer_id, organizer_name, title, description, category, location, lat, lng, event_date, price, status, cover_image) ' .
            'VALUES (:organizer_id, :organizer_name, :title, :description, :category, :location, :lat, :lng, :event_date, :price, :status, :cover_image)'
        );

        $payload = [
            ':organizer_id' => $data['organizer_id'],
            ':organizer_name' => $organizerName,
            ':title' => trim($data['title']),
            ':description' => trim($data['description']),
            ':category' => $data['category'],
            ':location' => trim($data['location']),
            ':lat' => $data['lat'] ?? null,
            ':lng' => $data['lng'] ?? null,
            ':event_date' => $data['event_date'],
            ':price' => isset($data['price']) ? (float)$data['price'] : 0.00,
            ':status' => 'pending',
            ':cover_image' => $data['cover_image'] ?? null,
        ];

        $statement->execute($payload);

        $eventId = (int)$this->pdo->lastInsertId();
        return $this->find($eventId);
    }

    public function update(int $id, array $data): ?array
    {
        $allowedKeys = ['title', 'description', 'category', 'location', 'lat', 'lng', 'event_date', 'price', 'status', 'cover_image'];
        $setParts = [];
        $params = [':id' => $id];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $setParts[] = "$key = :$key";
                $params[":$key"] = $key === 'price' ? (float)$data[$key] : $data[$key];
            }
        }

        if (empty($setParts)) {
            return null;
        }

        $updateQuery = 'UPDATE events SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $update = $this->pdo->prepare($updateQuery);
        $update->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM events WHERE id = :id');
        $statement->execute([':id' => $id]);
        return $statement->rowCount() > 0;
    }
}
