<?php

namespace Core\Cookies;

/**
 * Cookie serialization options based on RFC 6265
 * Similar to Next.js CookieSerializeOptions
 */
class CookieOptions
{
    public ?string $domain = null;
    public ?int $expires = null;
    public bool $httpOnly = false;
    public ?int $maxAge = null;
    public bool $partitioned = false;
    public string $path = '/';
    public ?string $priority = null; // 'low', 'medium', 'high'
    public string|bool $sameSite = 'lax'; // true, false, 'lax', 'strict', 'none'
    public bool $secure = false;

    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert to array format for setcookie()
     */
    public function toArray(): array
    {
        $options = [
            'path' => $this->path,
            'domain' => $this->domain ?? '',
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
        ];

        // Handle expires
        if ($this->expires !== null) {
            $options['expires'] = $this->expires;
        } elseif ($this->maxAge !== null) {
            $options['expires'] = time() + $this->maxAge;
        } else {
            $options['expires'] = 0;
        }

        // Handle SameSite
        if ($this->sameSite === true) {
            $options['samesite'] = 'Strict';
        } elseif ($this->sameSite === false) {
            $options['samesite'] = 'None';
        } else {
            $options['samesite'] = ucfirst(strtolower($this->sameSite));
        }

        return $options;
    }

    /**
     * Create options for deleting a cookie
     */
    public static function forDeletion(array $baseOptions = []): self
    {
        return new self([
            ...$baseOptions,
            'expires' => time() - 3600,
            'maxAge' => -1,
        ]);
    }
}
