<?php

namespace Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenManager
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expiration = 86400;

    public function __construct(string $secret, int $expiration = 86400)
    {
        if (empty($secret) || strlen($secret) < 32) {
            throw new Exception("Secret debe tener al menos 32 caracteres");
        }
        $this->secret = $secret;
        $this->expiration = $expiration;
    }

    public function generate(array $payload, ?int $customExpiration = null): string
    {
        $now = time();
        $expiration = $customExpiration ?? $this->expiration;

        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $expiration,
            'nbf' => $now
        ]);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function verify(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (Exception $e) {
            return null;
        }
    }

    public function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $cookieName = 'auth.session-token';
        return $_COOKIE[$cookieName] ?? null;
    }

    public function setTokenCookie(string $token, string $path = '/'): void
    {
        setcookie(
            'auth.session-token',
            $token,
            [
                'expires' => time() + $this->expiration,
                'path' => $path,
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => !in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function removeTokenCookie(): void
    {
        setcookie(
            'auth.session-token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => !in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        unset($_COOKIE['auth.session-token']);
    }

    public function refresh(object $payload): string
    {
        $newPayload = (array)$payload;
        unset($newPayload['iat'], $newPayload['exp'], $newPayload['nbf']);

        return $this->generate($newPayload);
    }
}
