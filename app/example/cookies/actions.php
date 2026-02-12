<?php

use Core\Cookies\Cookie;
use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Http\ServerAction;
use Core\Utils\Console;

// Acción 4: Toggle feature (retorna JSON para AJAX)
ServerAction::define('create-cookie', function ($data, $params) {

    $token = uniqid();

    $domain = $_ENV['COOKIE_DOMAIN'] ?? ($_SERVER['SERVER_NAME'] ?? '');

    $cookies = Cookie::response();
    $cookies->set(
        'example-cookie',
        $token,
        [
            'expires' => time() + 3600,
            'path' =>  '/',
            'domain' => $domain,
            'secure' =>  false,
            'httpOnly' => true,
            'sameSite' => 'Lax'
        ]
    );

    $exist = $cookies->getAll();

    error_log("aver esto--->" . json_encode($exist));

    HttpResponse::json([
        'success' => [
            'message' => "Cookie Generada",
            "data" => $exist
        ]
    ]);
});
