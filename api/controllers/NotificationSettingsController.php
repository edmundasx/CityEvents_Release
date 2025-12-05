<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/NotificationSetting.php';

function handleNotificationSettings(string $method, array $input): void
{
    $settingModel = new NotificationSetting();

    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            respond(['error' => 'Trūksta naudotojo ID'], 400);
            return;
        }
        $settings = $settingModel->findByUser((int)$userId);
        respond(['notification_settings' => $settings]);
        return;
    }

    if ($method === 'POST') {
        $missing = ensure_required_fields($input, ['user_id', 'event_id']);
        if ($missing) {
            respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
            return;
        }

        $existing = $settingModel->findByUserAndEvent((int)$input['user_id'], (int)$input['event_id']);

        if ($existing) {
            // If it exists, delete it (toggle off)
            $settingModel->deleteByUserAndEvent((int)$input['user_id'], (int)$input['event_id']);
            respond(['removed' => true]);
            return;
        } else {
            // If it doesn't exist, create it (toggle on)
            $missing = ensure_required_fields($input, ['time_offset', 'channels']);
            if ($missing) {
                respond(['error' => 'Trūksta pranešimo nustatymų', 'fields' => $missing], 400);
                return;
            }
            $setting = $settingModel->create($input);
            if ($setting) {
                respond(['setting' => $setting], 201);
                return;
            }
            respond(['error' => 'Nepavyko išsaugoti nustatymo'], 500);
            return;
        }
    }

    respond(['error' => 'Nepalaikomas metodas'], 405);
}
