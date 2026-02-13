<?php

namespace Core\Cookies;

/**
 * A class for manipulating Response cookies (Set-Cookie header)
 * Based on Next.js ResponseCookies implementation
 * Loose implementation of the experimental Cookie Store API
 */
class ResponseCookies
{
    /** @var array<string, ResponseCookie> */
    private array $cookies = [];
    private bool $headersSent = false;

    public function __construct()
    {
        $this->headersSent = headers_sent();
    }

    /**
     * Get a cookie by name or options
     */
    public function get(string|ResponseCookie $keyOrOptions): ?ResponseCookie
    {
        $name = $keyOrOptions instanceof ResponseCookie
            ? $keyOrOptions->name
            : $keyOrOptions;

        return $this->cookies[$name] ?? null;
    }

    /**
     * Get all cookies matching the criteria
     * 
     * @param string|ResponseCookie|null $keyOrOptions Filter by name/cookie, or null for all
     * @return ResponseCookie[]
     */
    public function getAll(string|ResponseCookie|null $keyOrOptions = null): array
    {
        if ($keyOrOptions === null) {
            return array_values($this->cookies);
        }

        $name = $keyOrOptions instanceof ResponseCookie
            ? $keyOrOptions->name
            : $keyOrOptions;

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
     * Set a cookie
     * 
     * @param string|ResponseCookie $keyOrOptions Cookie name or ResponseCookie object
     * @param string|null $value Cookie value (if first param is string)
     * @param array|CookieOptions|null $cookieOptions Cookie options
     */
    public function set(
        string|ResponseCookie $keyOrOptions,
        ?string $value = null,
        array|CookieOptions|null $cookieOptions = null
    ): static {
        if ($keyOrOptions instanceof ResponseCookie) {
            $cookie = $keyOrOptions;
        } else {
            $options = $cookieOptions instanceof CookieOptions
                ? $cookieOptions
                : new CookieOptions($cookieOptions ?? []);

            $cookie = new ResponseCookie(
                name: $keyOrOptions,
                value: $value ?? '',
                domain: $options->domain,
                path: $options->path,
                secure: $options->secure,
                sameSite: $options->sameSite,
                partitioned: $options->partitioned,
                expires: $options->expires,
                httpOnly: $options->httpOnly,
                maxAge: $options->maxAge,
                priority: $options->priority
            );
        }

        $this->cookies[$cookie->name] = $cookie;

        // Send the cookie header if headers haven't been sent
        if (!$this->headersSent) {
            $this->sendCookie($cookie);
        }

        return $this;
    }

    /**
     * Delete a cookie
     * 
     * @param string|array $keyOrOptions Cookie name(s) or options
     */
    public function delete(string|array $keyOrOptions): static
    {
        if (\is_array($keyOrOptions)) {
            // If it's an array of names
            if (isset($keyOrOptions[0]) && \is_string($keyOrOptions[0])) {
                foreach ($keyOrOptions as $name) {
                    $this->deleteSingle($name);
                }
            } else {
                // If it's an options array
                $name = $keyOrOptions['name'] ?? '';
                if ($name) {
                    $this->deleteSingle($name, $keyOrOptions);
                }
            }
        } else {
            $this->deleteSingle($keyOrOptions);
        }

        return $this;
    }

    private function deleteSingle(string $name, array $options = []): void
    {
        $deleteOptions = CookieOptions::forDeletion([
            'path' => $options['path'] ?? '/',
            'domain' => $options['domain'] ?? null,
        ]);

        $cookie = new ResponseCookie(
            name: $name,
            value: '',
            domain: $deleteOptions->domain,
            path: $deleteOptions->path,
            secure: $deleteOptions->secure,
            sameSite: $deleteOptions->sameSite,
            partitioned: $deleteOptions->partitioned,
            expires: $deleteOptions->expires,
            httpOnly: $deleteOptions->httpOnly,
            maxAge: $deleteOptions->maxAge,
            priority: $deleteOptions->priority
        );

        unset($this->cookies[$name]);

        if (!$this->headersSent) {
            $this->sendCookie($cookie);
        }
    }

    /**
     * Clear all cookies
     */
    public function clear(): static
    {
        $names = array_keys($this->cookies);
        foreach ($names as $name) {
            $this->deleteSingle($name);
        }
        return $this;
    }

    /**
     * Send cookie via setcookie()
     */
    private function sendCookie(ResponseCookie $cookie): bool
    {
        $options = $cookie->getOptions()->toArray();

        return setcookie(
            $cookie->name,
            $cookie->value,
            $options
        );
    }

    /**
     * Convert to Set-Cookie header string(s)
     */
    public function toString(): string
    {
        $headers = [];
        foreach ($this->cookies as $cookie) {
            $headers[] = $this->stringifyCookie($cookie);
        }
        return implode("\n", $headers);
    }

    /**
     * Stringify a single cookie for Set-Cookie header
     */
    private function stringifyCookie(ResponseCookie $cookie): string
    {
        $parts = [$cookie->name . '=' . urlencode($cookie->value)];

        if ($cookie->expires !== null) {
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $cookie->expires);
        }

        if ($cookie->maxAge !== null) {
            $parts[] = 'Max-Age=' . $cookie->maxAge;
        }

        if ($cookie->domain !== null) {
            $parts[] = 'Domain=' . $cookie->domain;
        }

        if ($cookie->path !== null) {
            $parts[] = 'Path=' . $cookie->path;
        }

        if ($cookie->secure) {
            $parts[] = 'Secure';
        }

        if ($cookie->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($cookie->sameSite !== false) {
            $sameSite = $cookie->sameSite === true ? 'Strict' : ucfirst(strtolower((string) $cookie->sameSite));
            $parts[] = 'SameSite=' . $sameSite;
        }

        if ($cookie->partitioned) {
            $parts[] = 'Partitioned';
        }

        if ($cookie->priority !== null) {
            $parts[] = 'Priority=' . ucfirst(strtolower($cookie->priority));
        }

        return implode('; ', $parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
