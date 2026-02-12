<?php

namespace Auth\Providers;

interface Provider
{
    public function authorize(array $credentials): ?array;
    public function getName(): string;
    public function getType(): string;
    public function getConfig(): array;
    public function handleCallback(): void;
}
