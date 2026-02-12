<?php

namespace Core\Cookies;

class ResponseCookies implements Cookies
{
    private array $cookies = [];

    public function get(string $name): mixed
    {
        return $this->cookies[$name] ?? null;
    }

    public function getAll(?string $name = null): array
    {
        if ($name !== null) {
            return isset($this->cookies[$name])
                ? [$name => $this->cookies[$name]]
                : [];
        }

        return $this->cookies;
    }

    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    public function set(string $key, string $value, array $options = []): static
    {
        $this->cookies[$key] = array_merge([
            'value' => $value,
            'path' => '/',
        ], $options);

        $result = setcookie(
            $key,
            $value,
            [
                'expires'  => $options['expires'] ?? 0,
                'path'     => $options['path'] ?? '/',
                'domain'   => $options['domain'] ?? '',
                'secure'   => $options['secure'] ?? false,
                'httponly' => $options['httpOnly'] ?? false,
                'samesite' => $options['sameSite'] ?? 'Lax'
            ]
        );

        error_log("Respuesta de setcookie:" . $result);

        if (!$result) {
            error_log("ResponseCookies: FALLO al establecer cookie '{$key}' - posible headers already sent");
        } else {
            error_log("ResponseCookies: Cookie '{$key}' establecida correctamente");
        }

        return $this;
    }

    public function delete(string|array $names): mixed
    {
        if (is_array($names)) {
            foreach ($names as $name) {
                $this->delete($name);
            }
            return true;
        }

        setcookie($names, '', time() - 3600, '/');
        unset($this->cookies[$names]);

        return true;
    }

    public function clear(): static
    {
        foreach (array_keys($_COOKIE) as $cookie) {
            $this->delete($cookie);
        }

        return $this;
    }

    public function toString(): string
    {
        return json_encode($this->cookies);
    }
}
