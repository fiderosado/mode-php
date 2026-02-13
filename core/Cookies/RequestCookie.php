<?php

namespace Core\Cookies;

/**
 * Represents a cookie from a Request (Cookie header)
 * Subset of CookieListItem, only containing name and value
 * since other cookie attributes aren't available on a Request
 */
class RequestCookie
{
    public function __construct(
        public readonly string $name,
        public readonly string $value
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['value'] ?? ''
        );
    }
}
