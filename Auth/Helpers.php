<?php

namespace Auth;

use Auth\Auth;
use Auth\SessionManager;

function useSession(): ?array
{
    if (!function_exists('getSessionManager')) {
        return null;
    }

    $sessionManager = getSessionManager();
    return $sessionManager->get();
}

function useUser(): ?array
{
    if (!function_exists('getSessionManager')) {
        return null;
    }

    $sessionManager = getSessionManager();
    return $sessionManager->getUser();
}

function useToken(): ?string
{
    if (!function_exists('getSessionManager')) {
        return null;
    }

    $sessionManager = getSessionManager();
    return $sessionManager->getToken();
}

function isAuthenticated(): bool
{
    if (!function_exists('getSessionManager')) {
        return false;
    }

    $sessionManager = getSessionManager();
    return $sessionManager->isActive();
}

function getAuth(): ?Auth
{
    global $auth;
    return $auth ?? null;
}

function getSessionManager(): ?SessionManager
{
    global $sessionManager;
    return $sessionManager ?? null;
}

function requireAuth(): array
{
    if (!isAuthenticated()) {
        http_response_code(401);
        throw new \Exception("No autenticado", 401);
    }

    return useUser();
}

function requirePermission(string $permission): void
{
    $user = requireAuth();
}

function redirectToSignIn(): void
{
    if (!isAuthenticated()) {
        header('Location: /auth/signin?callbackUrl=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function getAuthStatus(): array
{
    return [
        'authenticated' => isAuthenticated(),
        'user' => useUser(),
        'session' => useSession()
    ];
}

class Permission
{
    private array $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function has(string $permission): bool
    {
        $permissions = $this->user['permissions'] ?? [];
        return $permissions[$permission] ?? false;
    }

    public function any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->has($permission)) {
                return true;
            }
        }
        return false;
    }

    public function all(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->has($permission)) {
                return false;
            }
        }
        return true;
    }
}
