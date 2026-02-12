<?php

namespace Auth;

class SessionManager
{
    private TokenManager $tokenManager;
    private array $config;

    public function __construct(TokenManager $tokenManager, array $config = [])
    {
        $this->tokenManager = $tokenManager;
        $this->config = array_merge([
            'session_name' => 'auth',
            'max_age' => 86400,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'Lax'
        ], $config);

        if (session_status() === PHP_SESSION_NONE) {
            // Configurar parámetros de la sesión antes de iniciarla
            session_set_cookie_params([
                'lifetime' => $this->config['max_age'],
                'path' => '/',
                'domain' => '', // Dominio actual
                'secure' => $this->config['secure'],
                'httponly' => $this->config['http_only'],
                'samesite' => $this->config['same_site']
            ]);

            session_name($this->config['session_name']);
            session_start();
        }
    }

    public function create(array $user): array
    {
        $session = [
            'user' => $user,
            'token' => $this->tokenManager->generate([
                'sub' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'] ?? null,
            ]),
            'created_at' => time(),
            'expires_at' => time() + $this->config['max_age']
        ];

        $_SESSION[$this->config['session_name']] = $session;
        $this->tokenManager->setTokenCookie($session['token']);

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
                        'user' => (array)$payload,
                        'token' => $token,
                        'expires_at' => $payload->exp ?? null
                    ];
                }
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
        $session['token'] = $this->tokenManager->refresh((object)$session['user']);
        $session['updated_at'] = time();

        $_SESSION[$this->config['session_name']] = $session;
        $this->tokenManager->setTokenCookie($session['token']);

        return $session;
    }

    public function destroy(): void
    {
        unset($_SESSION[$this->config['session_name']]);
        $this->tokenManager->removeTokenCookie();
    }

    public function isActive(): bool
    {
        return $this->get() !== null;
    }

    public function getUser(): ?array
    {
        $session = $this->get();
        return $session['user'] ?? null;
    }

    public function getToken(): ?string
    {
        $session = $this->get();
        return $session['token'] ?? null;
    }
}
