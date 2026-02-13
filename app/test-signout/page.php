<?php

/**
 * Página de prueba para verificar el signout
 * Acceder desde: http://localhost/test-signout
 */

use Core\Http\Http;

Http::in(function ($req, $res) {
    $Auth = require __DIR__ . '/../../auth.config.php';

    $action = $req->query('action');

    if ($action === 'signout') {
        // Ejecutar signout
        try {
            $Auth->signOut();
            $res->redirect('/test-signout?result=success');
        } catch (\Exception $e) {
            $res->redirect('/test-signout?result=error&msg=' . urlencode($e->getMessage()));
        }
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
            <title>Test Signout</title>
            <style>
                body {
                    font-family: system-ui, -apple-system, sans-serif;
                    max-width: 800px;
                    margin: 50px auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .card {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
                }
                .status {
                    padding: 15px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                }
                .status.active {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .status.inactive {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .status.success {
                    background: #d1ecf1;
                    color: #0c5460;
                    border: 1px solid #bee5eb;
                }
                button {
                    background: #dc3545;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                button:hover {
                    background: #c82333;
                }
                button:disabled {
                    background: #6c757d;
                    cursor: not-allowed;
                }
                .info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                .info pre {
                    margin: 10px 0 0 0;
                    overflow-x: auto;
                }
                a {
                    color: #007bff;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>🧪 Test de Signout</h1>
            
                <?php if ($result === 'success'): ?>
                        <div class="status success">
                            ✓ Signout ejecutado correctamente
                        </div>
                <?php elseif ($result === 'error'): ?>
                        <div class="status inactive">
                            ✗ Error: <?= htmlspecialchars($req->query('msg')) ?>
                        </div>
                <?php endif; ?>

                <div class="status <?= $isActive ? 'active' : 'inactive' ?>">
                    <strong>Estado de Sesión:</strong> <?= $isActive ? '✓ ACTIVA' : '✗ INACTIVA' ?>
                </div>

                <?php if ($isActive): ?>
                        <div class="info">
                            <strong>Usuario autenticado:</strong>
                            <pre><?= json_encode($user, JSON_PRETTY_PRINT) ?></pre>
                        </div>

                        <form method="GET" action="/test-signout" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="signout">
                            <button type="submit">🚪 Cerrar Sesión (Signout)</button>
                        </form>
                <?php else: ?>
                        <p>No hay sesión activa. <a href="/example/google-auth">Iniciar sesión con Google</a></p>
                <?php endif; ?>

                <div class="info" style="margin-top: 30px;">
                    <strong>Información de Debug:</strong>
                    <pre>Session ID: <?= session_id() ?>
    Session Status: <?= match (session_status()) {
        PHP_SESSION_DISABLED => 'DISABLED',
        PHP_SESSION_NONE => 'NONE',
        PHP_SESSION_ACTIVE => 'ACTIVE'
    } ?>
    $_SESSION keys: <?= json_encode(array_keys($_SESSION ?? [])) ?>
    Cookies: <?= json_encode(array_keys($_COOKIE)) ?></pre>
                </div>
            </div>
        </body>
        </html>
        <?php
});
