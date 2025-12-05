<?php

require_once __DIR__ . '/../helpers.php';

function handleAdmin(string $method, array &$data, array $input, string $dataFile, array $config): void
{
    require_admin($config);

    if ($method !== 'POST') {
        respond(['error' => 'Nepalaikomas metodas'], 405);
        return;
    }

    $action = $input['action'] ?? '';
    switch ($action) {
        case 'update_status':
            $missing = ensure_required_fields($input, ['event_id', 'status']);
            if ($missing) {
                respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
                return;
            }

            foreach ($data['events'] as &$event) {
                if ((string)$event['id'] === (string)$input['event_id']) {
                    $event['status'] = $input['status'];
                    if (!empty($input['reason'])) {
                        $event['rejection_reason'] = $input['reason'];
                    }
                    $noteId = round(microtime(true) * 1000);
                    $data['notifications'][] = [
                        'id' => $noteId,
                        'type' => 'organizer',
                        'message' => sprintf('„%s“ %s.', $event['title'], $event['status']),
                        'created_at' => 'dabar',
                    ];
                    save_data($dataFile, $data);
                    respond(['event' => $event]);
                    return;
                }
            }
            respond(['error' => 'Renginys nerastas'], 404);
            return;

        case 'block_user':
            $missing = ensure_required_fields($input, ['user_id']);
            if ($missing) {
                respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
                return;
            }

            if (!in_array($input['user_id'], $data['blocked_users'], true)) {
                $data['blocked_users'][] = $input['user_id'];
                save_data($dataFile, $data);
            }
            respond(['blocked_users' => $data['blocked_users']]);
            return;

        default:
            respond(['error' => 'Nežinomas veiksmas'], 400);
            return;
    }
}
