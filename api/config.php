<?php

function get_pdo(): ?PDO
{
    static $pdo;
    static $initialized = false;

    if ($initialized) {
        return $pdo;
    }

    $initialized = true;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'cityevents';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        $pdo = null;
    }

    return $pdo;
}

return [
    // This token should be kept secret and ideally loaded from environment variables in production.
    'admin_token' => getenv('CITYEVENTS_ADMIN_TOKEN') ?: 'change-me-admin-token',
];
