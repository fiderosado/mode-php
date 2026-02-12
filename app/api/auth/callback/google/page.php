<?php
session_start();

/**
 * GET /api/auth/callback/google
 * Callback de Google OAuth 2.0
 * Procesa el código de autorización y crea la sesión del usuario
 */

use Auth\Auth;
use Core\Http\Http;
use Core\Security\Jwt;
use Core\Utils\Console;

Http::in(function ($req, $res) {

    error_log("Recibiendo callback desde Google :");

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

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        // Log para debug
        error_log("State recibido de Google: " . ($state ?? 'NULL'));
        error_log("State guardado en sesión: " . ($_SESSION['oauth_state'] ?? 'NULL'));
        error_log("Code recibido: " . ($code ?? 'NULL'));
        error_log("State recibido: " . ($state ?? 'NULL'));
        error_log("Session ID en callback: " . session_id());

        if (!$code || !$state) {
            $errorMsg = 'Invalid code or state';
            error_log("Código o estado inválido: Code: $code, State: $state");
            $res->redirect("/auth/error?error=$errorMsg&provider=google&code=400");
            return;
        }

        if (
            !isset($_SESSION['oauth_state']) ||
            !isset($_GET['state']) ||
            $_GET['state'] !== $_SESSION['oauth_state']
        ) {
            $errorMsg = 'Invalid state';
            error_log("CSRF MISMATCH - State recibido: '{$_GET['state']}', State guardado: '{$_SESSION['oauth_state']}'");
            $res->redirect("/auth/error?error=$errorMsg&provider=google&code=401");
        }

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

        error_log("Google user data: " . json_encode($user));


        $jwt = Jwt::in([
            'secret' => $_ENV['JWT_SECRET_SIGN'],
            'issuer' => 'dev.anfitrion.us',
        ])
            ->encode([
                'sub' => $user['sub'],
                'email' => $user['email'],
                'name' => $user['name'],
                'image' => $user['picture'],
                'provider' => 'google'
            ], 3600);


        error_log("El token generado es: " . $jwt);

        // Registrar cookie igual que NextAuth
        $cookieName = 'auth.session-token';
        //'__Secure-next-auth.session-token';
        setcookie(
            $cookieName,
            $jwt,
            [
                'expires'  => time() + 3600,
                'path'     => '/',
                'domain'   => 'dev.anfitrion.us',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_SESSION['oauth_state']);

        // Obtener la URL de redirección guardada o usar dashboard por defecto
        $callbackUrl = $_SESSION['callbackUrl'] ?? '/';
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
