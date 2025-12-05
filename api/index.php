<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/controllers/EventsController.php';
require_once __DIR__ . '/controllers/UsersController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/FavoritesController.php';
require_once __DIR__ . '/controllers/NotificationsController.php';
require_once __DIR__ . '/controllers/NotificationSettingsController.php';

// Use require_once to prevent function redeclaration errors.
$config = require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$resource = $_GET['resource'] ?? 'events';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$dataFile = __DIR__ . '/data.json';
$data = load_data($dataFile, 'default_data');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($resource) {
        case 'events':
        case 'recommendations':
            handleEvents($method, $input);
            return;
        case 'notifications':
            handleNotifications($data);
            return;
        case 'notification_settings':
            handleNotificationSettings($method, $input);
            return;
        case 'favorites':
            handleFavorites($method, $input);
            return;
        case 'auth':
            handleAuth($method, $input);
            return;
        case 'users':
            handleUsers($method, $input);
            return;
        case 'admin':
            handleAdmin($method, $data, $input, $dataFile, $config);
            return;
        default:
            respond(['error' => 'Nežinomas resursas'], 404);
            return;
    }
} catch (Throwable $e) {
    respond(['error' => 'Serverio klaida', 'details' => $e->getMessage()], 500);
}
