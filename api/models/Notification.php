<?php

class Notification
{
    private ?PDO $pdo;

    public function __construct()
    {
        $this->pdo = get_pdo();
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM notifications ORDER BY created_at DESC');
        $statement->execute();
        return $statement->fetchAll();
    }
}
