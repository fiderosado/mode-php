<?php

use Auth\Auth;
use Core\Http\Connect;
use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Http\ServerAction;
use Core\Utils\Console;

// Acción 4: Toggle feature (retorna JSON para AJAX)
ServerAction::define('logout-session', function ($data, $params) {
    $Auth = require __DIR__ . '/../../../auth.config.php';
    $Auth->signOut();
    HttpResponse::json([
        'success' => [
            'message' => 'Sesión cerrada correctamente'
        ]
    ]);
});
