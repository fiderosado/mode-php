<?php
session_start();
/**
 * GET /api/auth/google
 * Inicia el flujo de autenticación con Google OAuth 2.0
 * Redirige al usuario a la página de consentimiento de Google
 */

use Auth\Auth;
use Core\Http\Http;

Http::in(function ($req, $res) {

    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../auth.config.php';

    // La sesión es iniciada automáticamente por Auth -> SessionManager


    // Verificar método HTTP
    if ($req->method() !== 'GET') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        // Obtener el proveedor de Google
        $googleProvider = $Auth->getProvider('google');

        if (!$googleProvider) {
            $res->json([
                'error' => 'Google Provider Not Configured',
                'message' => 'Google OAuth 2.0 no está configurado en la aplicación'
            ], ['status' => 500]);
            return;
        }

        // Guardar la URL de callback si se proporciona
        if (isset($_GET['callbackUrl'])) {
            $_SESSION['callbackUrl'] = $_GET['callbackUrl'];
        }

        // Generar el state para CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // Log para debug (puedes comentar después)
        error_log("OAuth state generado y guardado: $state");
        error_log("Session ID: " . session_id());

        // Obtener la URL de autorización de Google
        // IMPORTANTE: Pasar el state generado a getAuthorizationUrl
        // El proveedor NO debe generar otro state internamente si ya se lo pasamos
        $authUrl = $googleProvider->getAuthorizationUrl($state);
        error_log("Redirigir al usuario a Google :" . $authUrl);
        // Redirigir al usuario a Google
        $res->redirect($authUrl);
    } catch (\Exception $e) {
        error_log("Error en /api/auth/google: " . $e->getMessage());
        $res->json([
            'error' => 'Error al iniciar autenticación con Google',
            'message' => $e->getMessage()
        ], ['status' => 500]);
    }
});
