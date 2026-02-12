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

    error_log("=== /api/auth/google INICIADO ===");
    
    // NO llamar session_start() aquí - Auth lo hace en SessionManager
    $Auth = require __DIR__ . '/../../../../auth.config.php';
    
    error_log("/api/auth/google: Auth config cargado");
    error_log("/api/auth/google: Session ID: " . session_id());
    error_log("/api/auth/google: Session name: " . session_name());
    error_log("/api/auth/google: Cookie params: " . json_encode(session_get_cookie_params()));

    if ($req->method() !== 'GET') {
        error_log("/api/auth/google: ERROR - Método no permitido");
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        $googleProvider = $Auth->getProvider('google');

        if (!$googleProvider) {
            error_log("/api/auth/google: ERROR - Google Provider no configurado");
            $res->json([
                'error' => 'Google Provider Not Configured',
                'message' => 'Google OAuth 2.0 no está configurado en la aplicación'
            ], ['status' => 500]);
            return;
        }

        error_log("/api/auth/google: Google Provider obtenido");

        // Guardar URL de callback si se proporciona
        if (isset($_GET['callbackUrl'])) {
            $_SESSION['callbackUrl'] = $_GET['callbackUrl'];
            error_log("/api/auth/google: callbackUrl guardado: " . $_GET['callbackUrl']);
        }

        // Generar state para CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // WORKAROUND: También guardar en una cookie separada por si la sesión no persiste
        // Usar Cookie::response() para establecer la cookie
        $cookies = Cookie::response();
        $cookies->set('oauth_state_backup', $state, [
            'expires' => time() + 600,  // 10 minutos
            'path' => '/',
            'httpOnly' => true,
            'sameSite' => 'Lax'
        ]);

        error_log("/api/auth/google: State generado y guardado: {$state}");
        error_log("/api/auth/google: State guardado en \$_SESSION['oauth_state']: " . $_SESSION['oauth_state']);
        error_log("/api/auth/google: State guardado también en cookie oauth_state_backup usando Cookie::response()");
        error_log("/api/auth/google: Todas las keys de \$_SESSION: " . json_encode(array_keys($_SESSION)));

        // Obtener URL de autorización (el provider también establece el state backup)
        $authUrl = $googleProvider->getAuthorizationUrl($state);
        
        error_log("/api/auth/google: URL de autorización obtenida");
        error_log("=== /api/auth/google REDIRIGIENDO A GOOGLE ===");
        
        $res->redirect($authUrl);
        
    } catch (\Exception $e) {
        error_log("=== /api/auth/google ERROR ===");
        error_log("/api/auth/google: Exception: " . $e->getMessage());
        error_log("/api/auth/google: Stack trace: " . $e->getTraceAsString());
        
        $res->json([
            'error' => 'Error al iniciar autenticación con Google',
            'message' => $e->getMessage()
        ], ['status' => 500]);
    }
});
