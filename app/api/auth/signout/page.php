<?php

/**
 * POST /api/auth/signout
 * Cierra la sesión del usuario actual
 */

use Auth\Auth;
use Core\Http\Http;

Http::in(function ($req, $res) {
    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../auth.config.php';

    // Verificar método HTTP
/*     if ($req->method() !== 'POST') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones POST'
        ], ['status' => 405]);
    }
 */
    try {
        // Cerrar la sesión
        $Auth->signOut();
        $res->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ]);
    } catch (\Exception $e) {

        $res->json([
            'status' => 'error',
            'error' => 'Error al cerrar sesión',
            'message' => $e->getMessage()
        ], ['status' => 400]);
    }
});
