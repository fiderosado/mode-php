<?php

namespace Core\Cookies;

interface Cookies
{
    public function get(string $name): mixed;

    public function getAll(?string $name = null): array;

    public function has(string $name): bool;

    public function set(string $key, string $value, array $options = []): static;

    public function delete(string|array $names): mixed;

    public function clear(): static;

    public function toString(): string;
}
