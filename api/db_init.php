<?php

require_once __DIR__ . '/config.php';

try {
    $pdo = get_pdo();
    if (!$pdo) {
        echo "Failed to connect to the database." . PHP_EOL;
        exit(1);
    }

    $sql = file_get_contents(__DIR__ . '/../database.sql');
    if ($sql === false) {
        echo "Failed to read the database.sql file." . PHP_EOL;
        exit(1);
    }

    // Split the SQL file into individual queries.
    $queries = preg_split('/;(\s*[\r\n]+|$)/', $sql, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }

    echo "Database schema and seed data loaded successfully." . PHP_EOL;

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

