<?php

namespace Auth;

/**
 * AuthMiddleware
 * Middleware para proteger rutas y verificar autenticación
 */
class AuthMiddleware
{
    private SessionManager $sessionManager;
    private array $config = [];

    public function __construct(SessionManager $sessionManager, array $config = [])
    {
        $this->sessionManager = $sessionManager;
        $this->config = array_merge([
            'redirectTo' => '/auth/signin',
            'unauthorizedRedirectTo' => '/auth/unauthorized',
            'apiErrorCode' => 401,
            'isApi' => false
        ], $config);
    }

    /**
     * Verificar autenticación
     */
    public function check(): bool
    {
        return $this->sessionManager->isActive();
    }

    /**
     * Requerir autenticación
     */
    public function require(): void
    {
        if (!$this->check()) {
            $this->handleUnauthorized();
        }
    }

    /**
     * Requerir autenticación con permisos específicos
     */
    public function requireWithPermission(string $permission): void
    {
        $this->require();

        $user = $this->sessionManager->getUser();
        if (!$this->hasPermission($user, $permission)) {
            $this->handleForbidden();
        }
    }

    /**
     * Requerir autenticación con cualquiera de varios permisos
     */
    public function requireWithAnyPermission(array $permissions): void
    {
        $this->require();

        $user = $this->sessionManager->getUser();
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return;
            }
        }

        $this->handleForbidden();
    }

    /**
     * Requerir autenticación con todos los permisos
     */
    public function requireWithAllPermissions(array $permissions): void
    {
        $this->require();

        $user = $this->sessionManager->getUser();
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                $this->handleForbidden();
            }
        }
    }

    /**
     * Verificar si el usuario tiene un permiso
     */
    private function hasPermission(?array $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $userPermissions = $user['permissions'] ?? [];
        return $userPermissions[$permission] ?? false;
    }

    /**
     * Manejar acceso no autorizado
     */
    private function handleUnauthorized(): void
    {
        if ($this->config['isApi']) {
            http_response_code($this->config['apiErrorCode']);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Unauthorized',
                'status' => 401
            ]);
            exit;
        }

        // Guardar URL para redirigir después de login
        $_SESSION['callbackUrl'] = $_SERVER['REQUEST_URI'] ?? '/';

        header('Location: ' . $this->config['redirectTo']);
        exit;
    }

    /**
     * Manejar acceso prohibido
     */
    private function handleForbidden(): void
    {
        if ($this->config['isApi']) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Forbidden',
                'status' => 403
            ]);
            exit;
        }

        header('Location: ' . $this->config['unauthorizedRedirectTo']);
        exit;
    }

    /**
     * Método estático para crear middleware
     */
    public static function create(SessionManager $sessionManager, array $config = []): self
    {
        return new self($sessionManager, $config);
    }
}
