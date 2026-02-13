<?php
namespace Auth;
use Core\Cookies\Cookie;
class SessionManager
{
    private TokenManager $tokenManager;
    private array $config;
    public function __construct(TokenManager $tokenManager, array $config = [])
    {
        $this->tokenManager = $tokenManager;
        $this->config = array_merge([
            'session_name' => 'auth',
            'maxAge' => 86400,
            'updateAge' => 3600,
            'secure' => false,
            'httpOnly' => true,
            'sameSite' => 'Lax'
        ], $config);
        // Solo iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            // Obtener el dominio (igual que en TokenManager)
            $domain = $_ENV['COOKIE_DOMAIN'] ?? '';

            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            // Para desarrollo local
            if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])) {
                $isSecure = false;
                $domain = '';
            }

            // Configurar cookies de sesión ANTES de session_start()
            session_set_cookie_params([
                'lifetime' => $this->config['maxAge'],
                'path' => '/',
                'domain' => $domain,
                'secure' => $isSecure,
                'httponly' => $this->config['httpOnly'],
                'samesite' => $this->config['sameSite']
            ]);
            // Establecer nombre de sesión
            session_name($this->config['session_name']);
            // Iniciar sesión
            session_start();
        } else {
        }
    }
    public function create(array $user): array
    {
        // Normalizar user data para compatibilidad con diferentes providers
        $userId = $user['id'] ?? $user['sub'] ?? $user['email'];
        $userEmail = $user['email'] ?? '';
        $userName = $user['name'] ?? $user['given_name'] ?? '';
        $tokenPayload = [
            'sub' => $userId,
            'email' => $userEmail,
            'name' => $userName,
        ];
        // Incluir campos adicionales del user
        foreach ($user as $key => $value) {
            if (!isset($tokenPayload[$key]) && !in_array($key, ['iat', 'exp', 'nbf'])) {
                $tokenPayload[$key] = $value;
            }
        }
        $token = $this->tokenManager->generate($tokenPayload);
        $session = [
            'user' => $user,
            'token' => $token,
            'created_at' => time(),
            'expires_at' => time() + $this->config['maxAge']
        ];
        $_SESSION[$this->config['session_name']] = $session;
        $this->tokenManager->setTokenCookie($token);
        return $session;
    }
    public function get(): ?array
    {
        $session = $_SESSION[$this->config['session_name']] ?? null;
        if (!$session) {
            $token = $this->tokenManager->getTokenFromRequest();
            if ($token) {
                $payload = $this->tokenManager->verify($token);
                if ($payload) {
                    return [
                        'user' => (array) $payload,
                        'token' => $token,
                        'expires_at' => $payload->exp ?? null
                    ];
                } else {
                }
            } else {
            }
            return null;
        }
        if (isset($session['token'])) {
            $payload = $this->tokenManager->verify($session['token']);
            if (!$payload) {
                $this->destroy();
                return null;
            }
        }
        if ($session['expires_at'] < time()) {
            $this->destroy();
            return null;
        }
        return $session;
    }
    public function update(array $data): array
    {
        $session = $this->get();
        if (!$session) {
            throw new \Exception("No hay sesión activa");
        }
        $session['user'] = array_merge($session['user'], $data);
        $session['token'] = $this->tokenManager->refresh((object) $session['user']);
        $session['updated_at'] = time();
        $_SESSION[$this->config['session_name']] = $session;
        $this->tokenManager->setTokenCookie($session['token']);
        return $session;
    }
    public function destroy(): void
    {
        // Obtener el dominio usado al crear las cookies (igual que en TokenManager)
        $domain = $_ENV['COOKIE_DOMAIN'] ?? '';
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        // Para desarrollo local
        if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])) {
            $isSecure = false;
            $domain = '';
        }
        // 1. Eliminar datos de sesión específicos
        unset($_SESSION[$this->config['session_name']]);
        // 2. Eliminar cookies con el mismo dominio usado al crearlas
        $cookies = Cookie::response();
        $cookies->delete('auth.session-token');
        $cookies->delete('auth');
        $cookies->delete('oauth_state_backup');

        // 3. Limpiar todas las variables de sesión
        $_SESSION = [];
        // 4. Destruir la sesión completamente
        session_destroy();
    }
    public function isActive(): bool
    {
        $active = $this->get() !== null;
        return $active;
    }
    public function getUser(): ?array
    {
        $session = $this->get();
        $user = $session['user'] ?? null;
        return $user;
    }
    public function getToken(): ?string
    {
        $session = $this->get();
        return $session['token'] ?? null;
    }
}
