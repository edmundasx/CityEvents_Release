<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Favorite.php';

function handleFavorites(string $method, array $input): void
{
    if ($method === 'POST') {
        $missing = ensure_required_fields($input, ['event_id', 'user_id']);
        if ($missing) {
            respond(['error' => 'Trūksta privalomų laukų', 'fields' => $missing], 400);
            return;
        }

        $favoriteModel = new Favorite();
        $favorite = $favoriteModel->create($input);

        if ($favorite) {
            respond(['favorite' => $favorite], 201);
            return;
        }

        respond(['error' => 'Nepavyko pridėti įsiminto renginio'], 500);
        return;
    }

    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            respond(['error' => 'Trūksta naudotojo ID'], 400);
            return;
        }

        $favoriteModel = new Favorite();
        $favorites = $favoriteModel->findByUser((int)$userId);
        respond(['favorites' => $favorites]);
        return;
    }

    respond(['error' => 'Nepalaikomas metodas'], 405);
    return;
}
