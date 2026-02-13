<?php

/**
 * GET /api/auth/callback/google
 * Callback de Google OAuth 2.0 - Integrado con Auth
 * Este endpoint SOLO se llama UNA VEZ cuando Google redirige aquí
 */

use Core\Http\Http;
use Core\Cookies\Cookie;

Http::in(function ($req, $res) {
    // Asegurar que no haya output antes de establecer cookies
    if (!ob_get_level()) {
        ob_start();
    }






    if ($req->method() !== 'GET') {

        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        // Auth config carga y SessionManager inicia sesión automáticamente
        $Auth = require __DIR__ . '/../../../../../auth.config.php';






        // Verificar errores de Google
        if (isset($_GET['error'])) {
            $errorMsg = urlencode($_GET['error']);

            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        // Usar Cookie::request() para leer cookies
        $requestCookies = Cookie::request();
        $stateBackupCookie = $requestCookies->get('oauth_state_backup');
        $stateBackupValue = $stateBackupCookie?->value;






        if (!$code || !$state) {

            $res->redirect("/auth/error?error=invalid_request&provider=google");
            return;
        }

        // Intentar obtener state de sesión o cookie backup
        $savedState = $_SESSION['oauth_state'] ?? $stateBackupValue ?? null;

        if (!$savedState) {



            $res->redirect("/auth/error?error=no_state_found&provider=google");
            return;
        }

        // Validar CSRF
        if ($_GET['state'] !== $savedState) {



            $res->redirect("/auth/error?error=invalid_state&provider=google");
            return;
        }



        // Limpiar state backup cookie usando Cookie::response()
        if ($requestCookies->has('oauth_state_backup')) {
            $responseCookies = Cookie::response();
            $responseCookies->delete('oauth_state_backup');

        }

        // PUNTO CRÍTICO: Usar Auth->signIn() que ejecuta TODO el flujo


        $session = $Auth->signIn('google', [
            'code' => $code,
            'state' => $state
        ]);





        // Verificar headers enviados
        if (headers_sent($file, $line)) {

        } else {

        }

        // Limpiar state de OAuth
        unset($_SESSION['oauth_state']);


        // Obtener URL de redirección
        $callbackUrl = $_SESSION['callbackUrl'] ?? '/';



        // Ejecutar callback de redirect si existe
        $redirectCallback = $Auth->getConfig()['callbacks']['redirect'] ?? null;
        if ($redirectCallback && is_callable($redirectCallback)) {

            $callbackUrl = $redirectCallback($callbackUrl, $_ENV['APP_URL'] ?? '');

        }




        unset($_SESSION['callbackUrl']);

        $res->redirect($callbackUrl);

    } catch (\Exception $e) {




        $errorMsg = urlencode($e->getMessage());
        $res->redirect("/auth/error?error=$errorMsg&provider=google");
    }
});
