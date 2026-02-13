<?php

/**
 * GET /api/auth/session
 * Obtiene la sesión actual del usuario autenticado
 */

use Auth\Auth;
use Core\Http\Http;

Http::in(function ($req, $res) {

    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../auth.config.php';

    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
    }

    try {
        // Obtener la sesión actual
        $session = $Auth->getSession();
        // Responder con la sesión
        $res->json([
            'status' => 'success',
            'authenticated' => $session !== null,
            'session' => $session
        ]);
    } catch (\Exception $e) {

        $res->json([
            'status' => 'error',
            'error' => 'Error al obtener la sesión',
            'message' => $e->getMessage()
        ], ['status' => 500]);
    }
});
