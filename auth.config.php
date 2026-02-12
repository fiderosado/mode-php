<?php

/**
 * Auth Configuration
 * Sistema de autenticación profesional para PHP
 */

use Auth\Auth;
use Auth\Callbacks;
use Auth\Providers\Credentials;
use Auth\Providers\Google;

require_once __DIR__ . '/vendor/autoload.php';

$AuthConfig = [
    'secret' => $_ENV['AUTH_SECRET'] ?? 'tu-super-secret-key-de-minimo-32-caracteres',

    'session' => [
        'maxAge' => 86400,
        'updateAge' => 3600,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httpOnly' => true,
        'sameSite' => 'Lax'
    ],

    'jwt' => [
        'secret' => $_ENV['AUTH_SECRET'] ?? 'tu-super-secret-key-de-minimo-32-caracteres',
        'maxAge' => 86400
    ],

    'pages' => [
        'signIn' => '/auth/signin',
        'signOut' => '/auth/signout',
        'error' => '/auth/error',
        'verifyRequest' => '/auth/verify-request',
        'newUser' => '/auth/new-user'
    ],

    'providers' => [
        'google' => new Google([
            'clientId' => $_ENV['AUTH_GOOGLE_ID'] ?? '',
            'clientSecret' => $_ENV['AUTH_GOOGLE_SECRET'] ?? '',
            'redirectUri' => ($_ENV['APP_URL'] ?? '') . '/api/auth/callback/google'
        ]),
    ],

    'callbacks' => [
        'signIn' => function (array $user, string $provider = '') {
            error_log("Callback signIn ejecutado");
            error_log("  - User: " . json_encode($user));
            error_log("  - Provider: " . $provider);
            
            // Aquí puedes validar el usuario, crear en BD si no existe, etc.
            // Retornar false rechaza el inicio de sesión
            
            return true;
        },

        'jwt' => function (array $token, array $user = [], string $provider = '', array $profile = [], bool $isNewUser = false) {
            error_log("Callback jwt ejecutado");
            error_log("  - Token inicial: " . json_encode($token));
            error_log("  - User: " . json_encode($user));
            error_log("  - Provider: " . $provider);
            
            // Construir el payload del JWT con los datos del usuario
            $jwtPayload = [
                'sub' => $user['sub'] ?? $user['id'] ?? $user['email'],
                'email' => $user['email'] ?? '',
                'name' => $user['name'] ?? '',
            ];

            // Agregar campos adicionales de Google si existen
            if (isset($user['picture'])) {
                $jwtPayload['image'] = $user['picture'];
            }
            if (isset($user['email_verified'])) {
                $jwtPayload['email_verified'] = $user['email_verified'];
            }
            if (isset($user['given_name'])) {
                $jwtPayload['given_name'] = $user['given_name'];
            }
            if (isset($user['family_name'])) {
                $jwtPayload['family_name'] = $user['family_name'];
            }

            error_log("  - JWT payload final: " . json_encode($jwtPayload));

            return $jwtPayload;
        },

        'session' => function (array $session, array $user = []) {
            error_log("Callback session ejecutado");
            error_log("  - Session: " . json_encode($session));
            error_log("  - User: " . json_encode($user));
            
            // Aquí puedes agregar datos adicionales a la sesión
            // $session['customField'] = 'customValue';
            
            return $session;
        },

        'redirect' => function (string $url = '', string $baseUrl = '') {
            error_log("Callback redirect ejecutado");
            error_log("  - URL solicitada: " . $url);
            error_log("  - Base URL: " . $baseUrl);
            
            // Validar que la URL sea segura
            if (!empty($url) && str_starts_with($url, $baseUrl)) {
                error_log("  - Redirigiendo a: " . $url);
                return $url;
            }
            
            $defaultUrl = $baseUrl . '/';
            error_log("  - Redirigiendo a default: " . $defaultUrl);
            return $defaultUrl;
        },

        'error' => function (string $error) {
            return Callbacks::error($error);
        }
    ],

    'events' => [
        'signin' => function (array $message = []) {
            error_log("Event signin disparado: " . json_encode($message));
        },

        'signout' => function () {
            error_log("Event signout disparado");
        },

        'signInError' => function (array $message = []) {
            error_log("Event signInError disparado: " . json_encode($message));
        },

        'signInSuccess' => function (array $message = []) {
            error_log("Event signInSuccess disparado: " . json_encode($message));
        }
    ]
];

$Auth = new Auth($AuthConfig);

return $Auth;
