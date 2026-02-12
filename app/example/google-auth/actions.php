<?php

use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Http\ServerAction;
use Core\Utils\Console;

// Acción 4: Toggle feature (retorna JSON para AJAX)
ServerAction::define('google-login', function ($data, $params) {



    HttpResponse::json([
        'success' => [
            'message' => '',
        ]
    ]);
});
