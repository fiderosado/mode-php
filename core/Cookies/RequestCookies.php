<?php

namespace Core\Cookies;

use IteratorAggregate;
use ArrayIterator;
use Traversable;

/**
 * A class for manipulating Request cookies (Cookie header)
 * Based on Next.js RequestCookies implementation
 */
class RequestCookies implements IteratorAggregate
{
    /** @var array<string, RequestCookie> */
    private array $cookies = [];
    private ?string $cookieHeader = null;

    public function __construct(?string $cookieHeader = null)
    {
        $this->cookieHeader = $cookieHeader;
        $this->parseCookies();
    }

    /**
     * Parse cookies from Cookie header or $_COOKIE
     */
    private function parseCookies(): void
    {
        if ($this->cookieHeader !== null) {
            $parsed = $this->parseCookieHeader($this->cookieHeader);
            foreach ($parsed as $name => $value) {
                $this->cookies[$name] = new RequestCookie($name, $value);
            }
        } else {
            foreach ($_COOKIE as $name => $value) {
                $this->cookies[$name] = new RequestCookie($name, (string) $value);
            }
        }
    }

    /**
     * Parse Cookie header string
     */
    private function parseCookieHeader(string $header): array
    {
        $cookies = [];
        $pairs = explode(';', $header);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) {
                continue;
            }

            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $cookies[$name] = urldecode($value);
            }
        }

        return $cookies;
    }

    /**
     * Iterator support
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Get the amount of cookies
     */
    public function size(): int
    {
        return count($this->cookies);
    }

    /**
     * Get a cookie by name or RequestCookie object
     */
    public function get(string|RequestCookie $nameOrCookie): ?RequestCookie
    {
        $name = $nameOrCookie instanceof RequestCookie
            ? $nameOrCookie->name
            : $nameOrCookie;

        return $this->cookies[$name] ?? null;
    }

    /**
     * Get all cookies matching the criteria
     * 
     * @param string|RequestCookie|null $nameOrCookie Filter by name/cookie, or null for all
     * @return RequestCookie[]
     */
    public function getAll(string|RequestCookie|null $nameOrCookie = null): array
    {
        if ($nameOrCookie === null) {
            return array_values($this->cookies);
        }

        $name = $nameOrCookie instanceof RequestCookie
            ? $nameOrCookie->name
            : $nameOrCookie;

        return isset($this->cookies[$name])
            ? [$this->cookies[$name]]
            : [];
    }

    /**
     * Check if a cookie exists
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Set a cookie (modifies internal state, doesn't send headers)
     */
    public function set(string|RequestCookie $keyOrCookie, ?string $value = null): static
    {
        if ($keyOrCookie instanceof RequestCookie) {
            $this->cookies[$keyOrCookie->name] = $keyOrCookie;
        } else {
            $this->cookies[$keyOrCookie] = new RequestCookie($keyOrCookie, $value ?? '');
        }

        return $this;
    }

    /**
     * Delete cookies by name(s)
     * 
     * @param string|string[] $names
     * @return bool|bool[]
     */
    public function delete(string|array $names): bool|array
    {
        if (\is_array($names)) {
            $results = [];
            foreach ($names as $name) {
                $results[] = $this->deleteSingle($name);
            }
            return $results;
        }

        return $this->deleteSingle($names);
    }

    private function deleteSingle(string $name): bool
    {
        if (isset($this->cookies[$name])) {
            unset($this->cookies[$name]);
            return true;
        }
        return false;
    }

    /**
     * Clear all cookies
     */
    public function clear(): static
    {
        $this->cookies = [];
        return $this;
    }

    /**
     * Convert to Cookie header string
     */
    public function toString(): string
    {
        $parts = [];
        foreach ($this->cookies as $cookie) {
            $parts[] = $cookie->name . '=' . urlencode($cookie->value);
        }
        return implode('; ', $parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
