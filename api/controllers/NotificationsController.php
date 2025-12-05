<?php

require_once __DIR__ . '/../helpers.php';

function handleNotifications(array $data): void
{
    respond(['notifications' => $data['notifications']]);
    return;
}
