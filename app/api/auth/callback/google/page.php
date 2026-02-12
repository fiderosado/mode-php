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

    error_log("=== CALLBACK GOOGLE INICIADO ===");
    error_log("GET params: " . json_encode($_GET));
    error_log("COOKIE recibidas: " . json_encode($_COOKIE));
    error_log("Session ID ANTES de cargar Auth: " . session_id());

    if ($req->method() !== 'GET') {
        error_log("Callback: ERROR - Método no permitido: " . $req->method());
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
        return;
    }

    try {
        // Auth config carga y SessionManager inicia sesión automáticamente
        $Auth = require __DIR__ . '/../../../../../auth.config.php';

        error_log("Callback: Auth config cargado");
        error_log("Callback: Session ID DESPUÉS de cargar Auth: " . session_id());
        error_log("Callback: Session name: " . session_name());
        error_log("Callback: \$_SESSION keys: " . json_encode(array_keys($_SESSION)));

        // Verificar errores de Google
        if (isset($_GET['error'])) {
            $errorMsg = urlencode($_GET['error']);
            error_log("Callback: ERROR de Google: {$errorMsg}");
            $res->redirect("/auth/error?error=$errorMsg&provider=google");
            return;
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        // Usar Cookie::request() para leer cookies
        $requestCookies = Cookie::request();
        $stateBackupCookie = $requestCookies->get('oauth_state_backup');

        error_log("Callback: Code recibido: " . ($code ? 'presente' : 'null'));
        error_log("Callback: State recibido: " . ($state ?? 'null'));
        error_log("Callback: State en \$_SESSION: " . ($_SESSION['oauth_state'] ?? 'null'));
        error_log("Callback: State en cookie backup: " . ($stateBackupCookie ?? 'null'));

        if (!$code || !$state) {
            error_log("Callback: ERROR - Falta code o state");
            $res->redirect("/auth/error?error=invalid_request&provider=google");
            return;
        }

        // Intentar obtener state de sesión o cookie backup
        $savedState = $_SESSION['oauth_state'] ?? $stateBackupCookie ?? null;

        if (!$savedState) {
            error_log("Callback: ERROR - No se encontró state guardado en ningún lugar");
            error_log("  - \$_SESSION completo: " . json_encode($_SESSION));
            error_log("  - Cookies completas: " . json_encode($requestCookies->getAll()));
            $res->redirect("/auth/error?error=no_state_found&provider=google");
            return;
        }

        // Validar CSRF
        if ($_GET['state'] !== $savedState) {
            error_log("Callback: ERROR - CSRF validation failed");
            error_log("  - State recibido: " . $_GET['state']);
            error_log("  - State esperado: " . $savedState);
            $res->redirect("/auth/error?error=invalid_state&provider=google");
            return;
        }

        error_log("Callback: CSRF validado correctamente usando: " . (isset($_SESSION['oauth_state']) ? 'SESSION' : 'COOKIE_BACKUP'));

        // Limpiar state backup cookie usando Cookie::response()
        if ($requestCookies->has('oauth_state_backup')) {
            $responseCookies = Cookie::response();
            $responseCookies->delete('oauth_state_backup');
            error_log("Callback: Cookie backup de state eliminada usando Cookie::response()");
        }

        // PUNTO CRÍTICO: Usar Auth->signIn() que ejecuta TODO el flujo
        error_log("Callback: Llamando a Auth->signIn('google', ...)");

        $session = $Auth->signIn('google', [
            'code' => $code,
            'state' => $state
        ]);

        error_log("Callback: Auth->signIn() completado exitosamente");
        error_log("Callback: Session retornada: " . json_encode($session));
        error_log("Callback: Verificando si la cookie se estableció...");

        // Verificar headers enviados
        if (headers_sent($file, $line)) {
            error_log("Callback: ⚠️ WARNING - Headers ya enviados desde {$file}:{$line}");
        } else {
            error_log("Callback: ✓ Headers aún no enviados - las cookies deberían establecerse correctamente");
        }

        // Limpiar state de OAuth
        unset($_SESSION['oauth_state']);
        error_log("Callback: oauth_state limpiado de sesión");

        // Obtener URL de redirección
        $callbackUrl = $_SESSION['callbackUrl'] ?? '/';

        error_log("Callback: callbackUrl: {$callbackUrl}");

        // Ejecutar callback de redirect si existe
        $redirectCallback = $Auth->getConfig()['callbacks']['redirect'] ?? null;
        if ($redirectCallback && is_callable($redirectCallback)) {
            error_log("Callback: Ejecutando redirect callback");
            $callbackUrl = $redirectCallback($callbackUrl, $_ENV['APP_URL'] ?? '');
            error_log("Callback: URL final después de redirect callback: {$callbackUrl}");
        }

        error_log("Callback: Redirigiendo a: {$callbackUrl}");
        error_log("=== CALLBACK GOOGLE COMPLETADO ===");

        unset($_SESSION['callbackUrl']);

        $res->redirect($callbackUrl);

    } catch (\Exception $e) {
        error_log("=== CALLBACK GOOGLE ERROR ===");
        error_log("Callback: Exception: " . $e->getMessage());
        error_log("Callback: Stack trace: " . $e->getTraceAsString());

        $errorMsg = urlencode($e->getMessage());
        $res->redirect("/auth/error?error=$errorMsg&provider=google");
    }
});
