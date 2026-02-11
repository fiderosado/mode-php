<?php

/**
 * GET /api/auth/callback/google
 * Callback de Google OAuth 2.0
 * Procesa el código de autorización y crea la sesión del usuario
 */

use Auth\Auth;
use Core\Http\Http;

Http::in(function ($req, $res) {

    // CRÍTICO: Iniciar sesión PRIMERO, antes de cualquier otra cosa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../../auth.config.php';

    // Verificar que sea una petición GET
    if ($req->method() !== 'GET') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        // Verificar si hay errores de Google
        if (isset($_GET['error'])) {
            $errorMsg = urlencode($_GET['error']);
            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        // Obtener el código de autorización
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (!$code) {
            $errorMsg = urlencode('Código de autorización no recibido');
            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        // Log para debug
        error_log("State recibido de Google: " . ($state ?? 'NULL'));
        error_log("State guardado en sesión: " . ($_SESSION['oauth_state'] ?? 'NULL'));
        error_log("Session ID en callback: " . session_id());

        // Verificar el state (CSRF protection)
        $savedState = $_SESSION['oauth_state'] ?? null;

        if (!$state || !$savedState || $state !== $savedState) {
            error_log("CSRF MISMATCH - State recibido: '$state', State guardado: '$savedState'");
            $errorMsg = urlencode('Estado de OAuth inválido - posible ataque CSRF');
            $errorCode = 'csrf_mismatch';
            $res->redirect("/auth/error?error=$errorMsg&code=$errorCode&provider=google&debug_state_received=" . urlencode($state ?? 'null') . "&debug_state_saved=" . urlencode($savedState ?? 'null'));
            return;
        }

        // Limpiar el state usado
        unset($_SESSION['oauth_state']);

        // Obtener el proveedor de Google
        $googleProvider = $Auth->getProvider('google');

        if (!$googleProvider) {
            $errorMsg = urlencode('Google provider no configurado');
            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        // Autorizar con el código recibido
        $user = $googleProvider->authorize([
            'code' => $code,
            'state' => $state
        ]);

        if (!$user) {
            $errorMsg = urlencode('No se pudo autenticar el usuario con Google');
            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        // Crear la sesión usando Auth
        $session = $Auth->signIn('google', ['code' => $code, 'state' => $state]);

        // Obtener la URL de redirección guardada o usar dashboard por defecto
        $callbackUrl = $_SESSION['callbackUrl'] ?? '/dashboard';
        unset($_SESSION['callbackUrl']);

        // Ejecutar el callback de redirect si está configurado
        $redirectCallback = $Auth->getConfig()['callbacks']['redirect'] ?? null;
        if ($redirectCallback && is_callable($redirectCallback)) {
            $callbackUrl = $redirectCallback($callbackUrl, $_ENV['APP_URL'] ?? '');
        }

        error_log("Login exitoso, redirigiendo a: $callbackUrl");

        // Redirigir al usuario
        $res->redirect($callbackUrl);

    } catch (\Exception $e) {
        // Log del error con detalles
        error_log("Error en callback de Google: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());

        // Redirigir a página de error con información completa
        $errorMsg = urlencode($e->getMessage());
        $errorCode = urlencode($e->getCode() ?: 'unknown');
        $res->redirect("/auth/error?error=$errorMsg&code=$errorCode&provider=google");
    }
});
