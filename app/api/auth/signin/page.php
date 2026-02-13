<?php

/**
 * POST /api/auth/signin
 * Endpoint genérico para iniciar sesión con cualquier proveedor
 * Soporta tanto OAuth (Google) como Credentials (email/password)
 */

use Core\Http\Http;
use Auth\Auth;

Http::in(function ($req, $res) {

    // Cargar la configuración de autenticación
    $Auth = require __DIR__ . '/../../../../auth.config.php';

    // Verificar método HTTP
    if ($req->method() !== 'POST') {
        $res->json([
            'error' => 'Method Not Allowed',
            'message' => 'Este endpoint solo acepta peticiones POST'
        ], ['status' => 405]);
        return;
    }

    try {
        // Obtener datos de la petición
        $provider = $req->input('provider');
        $credentials = $req->input('credentials') ?? [];
        $callbackUrl = $req->input('callbackUrl');

        // Validar que se proporcione un provider
        if (!$provider) {
            $res->json([
                'error' => 'Provider requerido',
                'message' => 'Debe especificar un proveedor de autenticación'
            ], ['status' => 400]);
            return;
        }

        // Obtener el proveedor
        $providerInstance = $Auth->getProvider($provider);

        if (!$providerInstance) {
            throw new \Exception("Proveedor no encontrado: $provider");
        }

        // Manejar según el tipo de proveedor
        $providerType = $providerInstance->getType();

        if ($providerType === 'oauth') {
            // Para OAuth (Google), devolver la URL de autorización

            // Guardar callback URL si se proporciona
            if ($callbackUrl) {
                $_SESSION['callbackUrl'] = $callbackUrl;
            }

            // Generar URL de autorización
            $state = bin2hex(random_bytes(16));
            $_SESSION['oauth_state'] = $state;

            $authUrl = $providerInstance->getAuthorizationUrl($state);

            $res->json([
                'status' => 'success',
                'type' => 'redirect',
                'url' => $authUrl,
                'message' => 'Redirigir al usuario a la URL proporcionada'
            ], ['status' => 200]);
        } elseif ($providerType === 'credentials') {
            // Para Credentials, autenticar directamente

            if (empty($credentials['email']) || empty($credentials['password'])) {
                throw new \Exception("Email y password son requeridos");
            }

            // Intentar autenticar
            $session = $Auth->signIn($provider, $credentials);

            $res->json([
                'status' => 'success',
                'type' => 'session',
                'session' => $session,
                'message' => 'Autenticación exitosa'
            ], ['status' => 200]);
        } else {
            throw new \Exception("Tipo de proveedor no soportado: $providerType");
        }
    } catch (\Exception $e) {
        // Log del error


        $res->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'provider' => $provider ?? null
        ], ['status' => 400]);
    }
});
