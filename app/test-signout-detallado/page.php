<?php

/**
 * Test detallado del proceso de signout
 * Verifica cada paso de la eliminación
 */

use Core\Http\Http;

Http::in(function ($req, $res) {
    $Auth = require __DIR__ . '/../../auth.config.php';

    $action = $req->query('action');

    if ($action === 'signout') {
        // Capturar estado ANTES del signout
        $beforeSessionId = session_id();
        $beforeSessionStatus = session_status();
        $beforeSessionData = $_SESSION ?? [];
        $beforeCookies = $_COOKIE;

        // Ejecutar signout
        ob_start();
        try {
            $Auth->signOut();
            $signoutSuccess = true;
            $signoutError = null;
        } catch (\Exception $e) {
            $signoutSuccess = false;
            $signoutError = $e->getMessage();
        }
        ob_end_clean();

        // Capturar estado DESPUÉS del signout
        $afterSessionId = session_status() === PHP_SESSION_ACTIVE ? session_id() : 'NO_ACTIVE';
        $afterSessionStatus = session_status();
        $afterSessionData = $_SESSION ?? [];

        // Redirigir con resultados
        $params = http_build_query([
            'result' => $signoutSuccess ? 'success' : 'error',
            'error' => $signoutError,
            'before_session_id' => $beforeSessionId,
            'before_status' => $beforeSessionStatus,
            'before_keys' => json_encode(array_keys($beforeSessionData)),
            'before_cookies' => json_encode(array_keys($beforeCookies)),
            'after_session_id' => $afterSessionId,
            'after_status' => $afterSessionStatus,
            'after_keys' => json_encode(array_keys($afterSessionData))
        ]);

        $res->redirect('/test-signout-detallado?' . $params);
        return;
    }

    // Obtener estado actual
    $session = $Auth->getSession();
    $isActive = $session !== null;
    $user = $session['user'] ?? null;
    $result = $req->query('result');

    ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Test Signout Detallado</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                .container {
                    max-width: 1000px;
                    margin: 0 auto;
                }
                .card {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    padding: 30px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #333;
                    margin-bottom: 20px;
                    font-size: 28px;
                }
                h2 {
                    color: #555;
                    margin: 20px 0 10px 0;
                    font-size: 20px;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 5px;
                }
                .status {
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    font-weight: 500;
                }
                .status.active {
                    background: #d4edda;
                    color: #155724;
                    border: 2px solid #c3e6cb;
                }
                .status.inactive {
                    background: #f8d7da;
                    color: #721c24;
                    border: 2px solid #f5c6cb;
                }
                .status.success {
                    background: #d1ecf1;
                    color: #0c5460;
                    border: 2px solid #bee5eb;
                }
                .status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 2px solid #f5c6cb;
                }
                button {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 14px 28px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 600;
                    transition: transform 0.2s;
                }
                button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }
                .comparison {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin: 20px 0;
                }
                .comparison-item {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    border: 2px solid #e9ecef;
                }
                .comparison-item h3 {
                    color: #495057;
                    margin-bottom: 10px;
                    font-size: 16px;
                }
                .comparison-item pre {
                    background: white;
                    padding: 10px;
                    border-radius: 4px;
                    overflow-x: auto;
                    font-size: 13px;
                    border: 1px solid #dee2e6;
                }
                .check-item {
                    display: flex;
                    align-items: center;
                    padding: 10px;
                    margin: 5px 0;
                    background: #f8f9fa;
                    border-radius: 6px;
                }
                .check-item.pass {
                    background: #d4edda;
                    border-left: 4px solid #28a745;
                }
                .check-item.fail {
                    background: #f8d7da;
                    border-left: 4px solid #dc3545;
                }
                .check-icon {
                    font-size: 20px;
                    margin-right: 10px;
                }
                a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 500;
                }
                a:hover {
                    text-decoration: underline;
                }
                .info-box {
                    background: #e7f3ff;
                    border-left: 4px solid #2196F3;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    <h1>🔬 Test Detallado de Signout</h1>
                
                    <?php if ($result === 'success'): ?>
                            <div class="status success">
                                <strong>✓ Signout ejecutado correctamente</strong>
                            </div>

                            <h2>📊 Comparación Antes/Después</h2>
                            <div class="comparison">
                                <div class="comparison-item">
                                    <h3>⏮️ ANTES del Signout</h3>
                                    <pre>Session ID: <?= $req->query('before_session_id') ?>
        Status: <?= match ((int) $req->query('before_status')) {
            PHP_SESSION_DISABLED => 'DISABLED',
            PHP_SESSION_NONE => 'NONE',
            PHP_SESSION_ACTIVE => 'ACTIVE',
            default => 'UNKNOWN'
        } ?>
        $_SESSION keys: <?= $req->query('before_keys') ?>
        Cookies: <?= $req->query('before_cookies') ?></pre>
                                </div>
                                <div class="comparison-item">
                                    <h3>⏭️ DESPUÉS del Signout</h3>
                                    <pre>Session ID: <?= $req->query('after_session_id') ?>
        Status: <?= match ((int) $req->query('after_status')) {
            PHP_SESSION_DISABLED => 'DISABLED',
            PHP_SESSION_NONE => 'NONE',
            PHP_SESSION_ACTIVE => 'ACTIVE',
            default => 'UNKNOWN'
        } ?>
        $_SESSION keys: <?= $req->query('after_keys') ?></pre>
                                </div>
                            </div>

                            <h2>✅ Verificaciones</h2>
                            <?php
                            $beforeKeys = json_decode($req->query('before_keys'), true);
                            $afterKeys = json_decode($req->query('after_keys'), true);
                            $beforeCookies = json_decode($req->query('before_cookies'), true);

                            $checks = [
                                [
                                    'name' => '$_SESSION limpiada',
                                    'pass' => empty($afterKeys),
                                    'detail' => empty($afterKeys) ? 'Todas las variables eliminadas' : 'Quedan: ' . implode(', ', $afterKeys)
                                ],
                                [
                                    'name' => 'Sesión destruida',
                                    'pass' => $req->query('after_status') == PHP_SESSION_NONE,
                                    'detail' => $req->query('after_status') == PHP_SESSION_NONE ? 'session_destroy() ejecutado' : 'Sesión aún activa'
                                ],
                                [
                                    'name' => 'Datos de auth eliminados',
                                    'pass' => !in_array('auth', $afterKeys),
                                    'detail' => !in_array('auth', $afterKeys) ? '$_SESSION[\'auth\'] eliminada' : '$_SESSION[\'auth\'] aún existe'
                                ],
                                [
                                    'name' => 'Cookie JWT presente antes',
                                    'pass' => in_array('auth.session-token', $beforeCookies ?? []),
                                    'detail' => in_array('auth.session-token', $beforeCookies ?? []) ? 'Cookie JWT existía' : 'No había cookie JWT'
                                ]
                            ];

                            foreach ($checks as $check): ?>
                                    <div class="check-item <?= $check['pass'] ? 'pass' : 'fail' ?>">
                                        <span class="check-icon"><?= $check['pass'] ? '✅' : '❌' ?></span>
                                        <div>
                                            <strong><?= $check['name'] ?></strong><br>
                                            <small><?= $check['detail'] ?></small>
                                        </div>
                                    </div>
                            <?php endforeach; ?>

                            <div class="info-box">
                                <strong>ℹ️ Nota sobre cookies:</strong> Las cookies se expiran en el navegador mediante headers Set-Cookie. 
                                Verifica las DevTools del navegador (Application → Cookies) para confirmar que se eliminaron.
                            </div>

                    <?php elseif ($result === 'error'): ?>
                            <div class="status error">
                                <strong>✗ Error al ejecutar signout:</strong><br>
                                <?= htmlspecialchars($req->query('error')) ?>
                            </div>
                    <?php endif; ?>

                    <h2>📍 Estado Actual</h2>
                    <div class="status <?= $isActive ? 'active' : 'inactive' ?>">
                        <strong>Sesión:</strong> <?= $isActive ? '✓ ACTIVA' : '✗ INACTIVA' ?>
                    </div>

                    <?php if ($isActive): ?>
                            <div class="comparison-item">
                                <h3>👤 Usuario Autenticado</h3>
                                <pre><?= json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                            </div>

                            <form method="GET" action="/test-signout-detallado" style="margin-top: 20px;">
                                <input type="hidden" name="action" value="signout">
                                <button type="submit">🚪 Ejecutar Signout</button>
                            </form>
                    <?php else: ?>
                            <p style="margin: 20px 0;">
                                No hay sesión activa. 
                                <a href="/example/google-auth">Iniciar sesión con Google</a>
                            </p>
                    <?php endif; ?>

                    <div class="comparison-item" style="margin-top: 30px;">
                        <h3>🔍 Debug Info</h3>
                        <pre>Session ID: <?= session_id() ?>
    Session Status: <?= match (session_status()) {
        PHP_SESSION_DISABLED => 'DISABLED',
        PHP_SESSION_NONE => 'NONE',
        PHP_SESSION_ACTIVE => 'ACTIVE'
    } ?>
    $_SESSION: <?= json_encode($_SESSION ?? [], JSON_PRETTY_PRINT) ?>
    Cookies: <?= json_encode(array_keys($_COOKIE), JSON_PRETTY_PRINT) ?></pre>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
});
