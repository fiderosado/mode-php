<?php

use Core\Cookies\Cookie;
use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Http\ServerAction;
use Core\Utils\Console;

// Acción 4: Toggle feature (retorna JSON para AJAX)
ServerAction::define('create-cookie', function ($data, $params) {

    $token = uniqid();

    $domain = $_ENV['COOKIE_DOMAIN'] ?? '';

    $cookies = Cookie::response();
    $cookies->set(
        'example-cookie',
        $token,
        [
            'maxAge' => 3600,
            'path' => '/',
            'domain' => $domain ?: null,
            'secure' => false,
            'httpOnly' => true,
            'sameSite' => 'lax'
        ]
    );

    $allCookies = $cookies->getAll();
    $cookiesData = [];
    foreach ($allCookies as $cookie) {
        $cookiesData[$cookie->name] = [
            'value' => $cookie->value,
            'path' => $cookie->path,
            'domain' => $cookie->domain,
            'secure' => $cookie->secure,
            'httpOnly' => $cookie->httpOnly,
        ];
    }

    HttpResponse::json([
        'success' => [
            'message' => "Cookie Generada",
            "data" => $cookiesData,
            'domain' => $domain,
        ]
    ]);
});
