<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Notification.php';

function handleNotifications(): void
{
    $notificationModel = new Notification();
    $notifications = $notificationModel->findAll();
    respond(['notifications' => $notifications]);
    return;
}
