<?php

namespace Core;

class SuspenseAction
{
    private ?string $actionName = null;
    private string $hash;

    private function __construct(array $args = [])
    {
        $this->actionName = $args[0] ?? null;
        $this->hash = hash('sha256', $this->actionName);
    }

    public static function in(...$args): SuspenseAction
    {
        return new self($args);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getName(): ?string
    {
        return $this->actionName;
    }
}
