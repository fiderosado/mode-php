<?php

/**
 * GET /api/auth/google
 * Inicia el flujo de autenticación con Google OAuth 2.0
 * Redirige al usuario a la página de consentimiento de Google
 */

use Auth\Auth;
use Core\Http\Http;
use Core\Cookies\Cookie;

Http::in(function ($req, $res) {



    // NO llamar session_start() aquí - Auth lo hace en SessionManager
    $Auth = require __DIR__ . '/../../../../auth.config.php';






    if ($req->method() !== 'GET') {

        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        $googleProvider = $Auth->getProvider('google');

        if (!$googleProvider) {

            $res->json([
                'error' => 'Google Provider Not Configured',
                'message' => 'Google OAuth 2.0 no está configurado en la aplicación'
            ], ['status' => 500]);
            return;
        }



        // Guardar URL de callback si se proporciona
        if (isset($_GET['callbackUrl'])) {
            $_SESSION['callbackUrl'] = $_GET['callbackUrl'];

        }

        // Generar state para CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // WORKAROUND: También guardar en una cookie separada por si la sesión no persiste
        // Usar Cookie::response() para establecer la cookie
        $cookies = Cookie::response();
        $cookies->set('oauth_state_backup', $state, [
            'maxAge' => 600,  // 10 minutos
            'path' => '/',
            'httpOnly' => true,
            'sameSite' => 'lax'
        ]);






        // Obtener URL de autorización (el provider también establece el state backup)
        $authUrl = $googleProvider->getAuthorizationUrl($state);




        $res->redirect($authUrl);

    } catch (\Exception $e) {




        $res->json([
            'error' => 'Error al iniciar autenticación con Google',
            'message' => $e->getMessage()
        ], ['status' => 500]);
    }
});
