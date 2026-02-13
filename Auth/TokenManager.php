<?php
namespace Auth;
use Core\Cookies\Cookie;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class TokenManager
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expiration = 86400;
    private string $cookieName = 'auth.session-token';
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
    public function getCreatedCookie(): ?string
    {
        // Usar la clase Cookie para leer cookies
        $cookies = Cookie::request();
        return $cookies->get($this->cookieName);
    }
    public function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        // Usar la clase Cookie para leer cookies
        $cookies = Cookie::request();
        return $cookies->get($this->cookieName);
    }
    public function setTokenCookie(string $token, string $path = '/'): void
    {
        $domain = $_ENV['COOKIE_DOMAIN'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        // Para desarrollo local
        if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])) {
            $isSecure = false;
            $domain = '';
        }
        // Eliminar el primer punto si existe (ej: .domain -> domain)
        if (!empty($domain) && str_starts_with($domain, '.')) {
            $domain = preg_replace('/^\./', '', $domain);
        }
        // Usar la clase Cookie para establecer cookies
        $cookies = Cookie::response();
        $cookies->set($this->cookieName, $token, [
            'expires' => time() + $this->expiration,
            'path' => $path,
            'domain' => $domain,
            'secure' => $isSecure,
            'httpOnly' => true,
            'sameSite' => 'Lax'
        ]);
    }
    public function removeTokenCookie(): void
    {
        // Usar la clase Cookie para eliminar cookies
        $cookies = Cookie::response();
        $cookies->delete($this->cookieName);
    }
    public function refresh(object $payload): string
    {
        $newPayload = (array) $payload;
        unset($newPayload['iat'], $newPayload['exp'], $newPayload['nbf']);
        return $this->generate($newPayload);
    }
    public function setCookieName(string $name): void
    {
        $this->cookieName = $name;
    }
    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
