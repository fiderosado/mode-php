<?php

use Core\Http\Action;
use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Utils\Console;

// Acción 4: Toggle feature (retorna JSON para AJAX)
Action::define('toggleFeature', function ($data, $params) {

    // Simular toggle de alguna característica
    $enabled = (bool) rand(0, 1);

    HttpResponse::json([
        'success' => [
            'message' => $enabled ? 'Feature activado' : 'Feature desactivado',
        ]
    ]);
});
