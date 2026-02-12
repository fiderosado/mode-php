<?php

namespace Core\Http;

class HttpRequest
{
    protected array $query;
    protected array $body;
    protected array $files;
    protected array $headers;
    protected array $cookies;
    protected array $server;
    protected string $method;
    protected string $uri;
    protected string $rawBody;

    public function __construct()
    {
        $this->query   = $_GET ?? [];
        $this->files   = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->server  = $_SERVER ?? [];
        $this->headers = $this->getHeaders();
        $this->method  = $this->server['REQUEST_METHOD'] ?? 'GET';
        $this->uri     = $this->server['REQUEST_URI'] ?? '/';

        $this->rawBody = file_get_contents('php://input');
        $this->body    = $this->parseBody();
    }

    protected function parseBody(): array
    {
        // Si es JSON
        if ($this->isJson()) {
            $json = json_decode($this->rawBody, true);
            return is_array($json) ? $json : [];
        }

        // Form normal
        return $_POST ?? [];
    }

    protected function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    // ==============================
    // 🔹 Métodos principales
    // ==============================

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function fullUrl(): string
    {
        $protocol = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $this->server['HTTP_HOST'] . $this->uri;
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    // ==============================
    // 🔹 Query Params (?id=1)
    // ==============================

    public function query(string $key = null, $default = null)
    {
        if (!$key) return $this->query;
        return $this->query[$key] ?? $default;
    }

    // ==============================
    // 🔹 Body (POST / JSON)
    // ==============================

    public function input(string $key = null, $default = null)
    {
        if (!$key) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function raw(): string
    {
        return $this->rawBody;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !empty($value);
    }

    // ==============================
    // 🔹 Files
    // ==============================

    public function file(string $key = null)
    {
        if (!$key) return $this->files;
        return $this->files[$key] ?? null;
    }

    // ==============================
    // 🔹 Headers
    // ==============================

    public function header(string $key = null, $default = null)
    {
        if (!$key) return $this->headers;

        return $this->headers[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function isJson(): bool
    {
        return str_contains(
            strtolower($this->header('Content-Type', '')),
            'application/json'
        );
    }

    // ==============================
    // 🔹 Cookies
    // ==============================

    public function cookie(string $key = null, $default = null)
    {
        if (!$key) return $this->cookies;
        return $this->cookies[$key] ?? $default;
    }

    // ==============================
    // 🔹 Server
    // ==============================

    public function server(string $key = null, $default = null)
    {
        if (!$key) return $this->server;
        return $this->server[$key] ?? $default;
    }
}
