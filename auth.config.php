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




            // Aquí puedes validar el usuario, crear en BD si no existe, etc.
            // Retornar false rechaza el inicio de sesión
        
            return true;
        },

        'jwt' => function (array $token, array $user = [], string $provider = '', array $profile = [], bool $isNewUser = false) {





            // Construir el payload del JWT con los datos del usuario
            $jwtPayload = [
                'sub' => $user['sub'] ?? $user['id'] ?? $user['email'],
                'email' => $user['email'] ?? '',
                'name' => $user['name'] ?? '',
            ];

            // Agregar campos adicionales de Google si existen
            if (isset($user['picture'])) {
                $jwtPayload['picture'] = $user['picture'];
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



            return $jwtPayload;
        },

        'session' => function (array $session, array $user = []) {




            // Aquí puedes agregar datos adicionales a la sesión
            // $session['customField'] = 'customValue';
        
            return $session;
        },

        'redirect' => function (string $url = '', string $baseUrl = '') {




            // Si la URL está vacía, usar default
            if (empty($url)) {
                $defaultUrl = $baseUrl . '/';

                return $defaultUrl;
            }

            // Si es una ruta relativa (comienza con /), es segura
            if (str_starts_with($url, '/')) {

                return $url;
            }

            // Si es una URL completa, validar que comience con baseUrl
            if (str_starts_with($url, $baseUrl)) {

                return $url;
            }

            // URL no segura, usar default
            $defaultUrl = $baseUrl . '/';

            return $defaultUrl;
        },

        'error' => function (string $error) {
            return Callbacks::error($error);
        }
    ],

    'events' => [
        'signin' => function (array $message = []) {

        },

        'signout' => function () {

        },

        'signInError' => function (array $message = []) {

        },

        'signInSuccess' => function (array $message = []) {

        }
    ]
];

$Auth = new Auth($AuthConfig);

return $Auth;
