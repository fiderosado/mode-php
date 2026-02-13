<?php

/**
 * GET /api/auth/providers
 * Obtiene la lista de proveedores de autenticación disponibles
 */

use Auth\Auth;
use Core\Http\Http;

Http::in(function ($req, $res) {

    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../auth.config.php';

    // Verificar método HTTP
    if ($req->method() !== 'GET') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones GET'
        ], ['status' => 405]);
    }

    try {
        // Obtener los proveedores disponibles
        $providers = $Auth->getProviders();
        $res->json([
            'status' => 'success',
            'providers' => $providers
        ]);
    } catch (\Exception $e) {

        $res->json([
            'status' => 'error',
            'error' => 'Error al obtener proveedores',
            'message' => $e->getMessage()
        ], ['status' => 500]);
    }
});
