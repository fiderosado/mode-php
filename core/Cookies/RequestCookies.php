<?php

namespace Core\Cookies;

use IteratorAggregate;
use ArrayIterator;
use Traversable;

class RequestCookies implements Cookies, IteratorAggregate
{
    private array $cookies = [];

    public function __construct(?array $source = null)
    {
        $this->cookies = $source ?? $_COOKIE;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

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
        $this->cookies[$key] = $value;
        return $this;
    }

    public function delete(string|array $names): mixed
    {
        if (is_array($names)) {
            $results = [];
            foreach ($names as $name) {
                $results[$name] = $this->delete($name);
            }
            return $results;
        }

        if (isset($this->cookies[$names])) {
            unset($this->cookies[$names]);
            return true;
        }

        return false;
    }

    public function clear(): static
    {
        $this->cookies = [];
        return $this;
    }

    public function toString(): string
    {
        return http_build_query($this->cookies, '', '; ');
    }
}
