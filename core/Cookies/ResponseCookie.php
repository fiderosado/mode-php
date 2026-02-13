<?php

namespace Core\Cookies;

/**
 * Represents a cookie for a Response (Set-Cookie header)
 * Superset of CookieListItem with additional attributes
 */
class ResponseCookie
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly ?string $domain = null,
        public readonly ?string $path = '/',
        public readonly bool $secure = false,
        public readonly string|bool $sameSite = 'lax',
        public readonly bool $partitioned = false,
        public readonly ?int $expires = null,
        public readonly bool $httpOnly = false,
        public readonly ?int $maxAge = null,
        public readonly ?string $priority = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'domain' => $this->domain,
            'path' => $this->path,
            'secure' => $this->secure,
            'sameSite' => $this->sameSite,
            'partitioned' => $this->partitioned,
            'expires' => $this->expires,
            'httpOnly' => $this->httpOnly,
            'maxAge' => $this->maxAge,
            'priority' => $this->priority,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            value: $data['value'] ?? '',
            domain: $data['domain'] ?? null,
            path: $data['path'] ?? '/',
            secure: $data['secure'] ?? false,
            sameSite: $data['sameSite'] ?? 'lax',
            partitioned: $data['partitioned'] ?? false,
            expires: $data['expires'] ?? null,
            httpOnly: $data['httpOnly'] ?? false,
            maxAge: $data['maxAge'] ?? null,
            priority: $data['priority'] ?? null
        );
    }

    /**
     * Get CookieOptions from this ResponseCookie
     */
    public function getOptions(): CookieOptions
    {
        return new CookieOptions([
            'domain' => $this->domain,
            'path' => $this->path,
            'secure' => $this->secure,
            'sameSite' => $this->sameSite,
            'partitioned' => $this->partitioned,
            'expires' => $this->expires,
            'httpOnly' => $this->httpOnly,
            'maxAge' => $this->maxAge,
            'priority' => $this->priority,
        ]);
    }
}
