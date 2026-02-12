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

// Configuración de Auth
$AuthConfig = [
    'secret' => $_ENV['AUTH_SECRET'] ?? 'tu-super-secret-key-de-minimo-32-caracteres',

    'session' => [
        'maxAge' => 86400, // 24 horas
        'updateAge' => 3600, // 1 hora
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httpOnly' => true,
        'sameSite' => 'Lax'
    ],

    'jwt' => [
        'secret' => $_ENV['AUTH_SECRET'] ?? 'tu-super-secret-key-de-minimo-32-caracteres',
        'maxAge' => 86400 // 24 horas
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
        // Descomentar para habilitar autenticación con credenciales (email/password)
        /* 'credentials' => new Credentials([
            'db' => null, // Pasar instancia de PDO aquí
        ]) */
    ],

    'callbacks' => [
        /**
         * Callback ejecutado antes de crear la sesión
         * Retornar false para rechazar el inicio de sesión
         */
        'signIn' => function (array $user, string $provider = '') {
            // Aquí puedes validar el usuario, crear en BD si no existe, etc.

            // Ejemplo: Validar dominio de email
            // if (!str_ends_with($user['email'], '@miempresa.com')) {
            //     return false;
            // }

            // Ejemplo: Crear usuario en BD si no existe
            // $db = getDatabase();
            // $db->createUserIfNotExists($user);

            return true;
        },

        /**
         * Callback para personalizar el payload del JWT
         */
        'jwt' => function (array $token, array $user = [], string $provider = '', array $profile = [], bool $isNewUser = false) {
            if (!empty($user['id'])) {
                $token['id'] = $user['id'];
            }
            if (!empty($user['email'])) {
                $token['email'] = $user['email'];
            }
            if (!empty($user['name'])) {
                $token['name'] = $user['name'];
            }

            error_log('Session tokn: ---->> ' . json_encode($token));

            // Agregar campos personalizados
            // $token['role'] = $user['role'] ?? 'user';
            // $token['permissions'] = getUserPermissions($user['id']);

            return $token;
        },

        /**
         * Callback para personalizar la sesión
         */
        'session' => function (array $session, array $user = []) {
            // Agregar datos adicionales a la sesión
            // $session['lastLogin'] = date('Y-m-d H:i:s');
            // $session['permissions'] = getUserPermissions($user['id']);

            error_log('Session data: ---->>' . json_encode($session));
            error_log('Session user: ---->> ' . json_encode($user));

            return $session;
        },

        /**
         * Callback para controlar la redirección después del login
         */
        'redirect' => function (string $url = '', string $baseUrl = '') {
            // Validar que la URL sea segura
            if (!empty($url) && str_starts_with($url, $baseUrl)) {
                return $url;
            }
            return $baseUrl . '/';
        },

        /**
         * Callback para manejo de errores
         */
        'error' => function (string $error) {
            return Callbacks::error($error);
        }
    ],

    'events' => [
        /**
         * Evento disparado al iniciar el proceso de login
         */
        'signin' => function (array $message = []) {
            // Log de intento de login
            // logger()->info('Intento de login', $message);
            error_log('Sign in attempt: ' . json_encode($message));
        },

        /**
         * Evento disparado al cerrar sesión
         */
        'signout' => function () {
            // Limpiar datos temporales, cache, etc.
            // logger()->info('Cierre de sesión');
            error_log('Sign out event');
        },

        /**
         * Evento disparado cuando hay un error en el login
         */
        'signInError' => function (array $message = []) {
            error_log('Sign in error ------>> ' . json_encode($message));

            // Aquí puedes:
            // - Enviar alertas de seguridad
            // - Bloquear IPs sospechosas
            // - Notificar al usuario
        },

        /**
         * Evento disparado cuando el login es exitoso
         */
        'signInSuccess' => function (array $message = []) {
            // Actualizar último login en BD
            // Enviar email de notificación
            // Log de auditoría
        }
    ]
];

// Crear la instancia de Auth
$Auth = new Auth($AuthConfig);

return $Auth;
