<?php

namespace Auth;

class Callbacks
{
    public static function signIn(array $user, string $account = '', array $profile = []): bool
    {
        if (empty($user['email'])) {
            return false;
        }

        return true;
    }

    public static function redirect(string $url = '', string $baseUrl = ''): string
    {
        if (!empty($url) && str_starts_with($url, $baseUrl)) {
            return $url;
        }

        return $baseUrl . '/';
    }

    public static function session(array $session, array $user): array
    {
        $session['user'] = $user;
        $session['permissions'] = self::getUserPermissions($user['id'] ?? null);

        return $session;
    }

    public static function jwt(array $token, array $user, string $account, array $profile, bool $isNewUser): array
    {
        if (!empty($user['id'])) {
            $token['id'] = $user['id'];
        }

        if (!empty($user['email'])) {
            $token['email'] = $user['email'];
        }

        $token['role'] = $user['role'] ?? 'user';
        $token['permissions'] = self::getUserPermissions($user['id'] ?? null);

        return $token;
    }

    public static function error(string $error): string
    {
        error_log("Auth Error: " . $error);

        return match ($error) {
            'Callback' => 'Error de callback',
            'OAuthSignin' => 'Error al conectar con el proveedor',
            'OAuthCallback' => 'Error en el callback del proveedor',
            'EmailSignInError' => 'Error al enviar correo de confirmación',
            'CredentialsSignin' => 'Credenciales inválidas',
            'SessionCallback' => 'Error en el callback de sesión',
            'EmailCreateUserError' => 'No se puede crear el usuario',
            default => 'Error desconocido'
        };
    }

    private static function getUserPermissions(?string $userId): array
    {
        if (!$userId) {
            return [];
        }

        return [
            'read' => true,
            'write' => false,
            'admin' => false
        ];
    }
}
