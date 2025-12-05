<?php

require_once __DIR__ . '/../helpers.php';

function handleFavorites(string $method, array &$data, array $input, string $dataFile): void
{
    if ($method === 'POST') {
        $missing = ensure_required_fields($input, ['event_id', 'user_id']);
        if ($missing) {
            respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
            return;
        }

        $entry = [
            'id' => round(microtime(true) * 1000),
            'event_id' => $input['event_id'],
            'user_id' => $input['user_id'],
            'tag' => $input['tag'] ?? 'favorite',
        ];
        $data['favorites'][] = $entry;
        save_data($dataFile, $data);
        respond(['favorite' => $entry], 201);
        return;
    }

    respond(['error' => 'Nepalaikomas metodas'], 405);
    return;
}
