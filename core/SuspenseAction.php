<?php

namespace Core;

class SuspenseAction
{
    private ?string $actionName = null;
    private string $hash;
    private array $send = [];

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

    public function getPayload(): array
    {
        return $this->send;
    }

    public function send(array $data): SuspenseAction
    {
        $this->send = $data;
        return $this;
    }

    /**
     * Transfiere el payload de data hasta el método loadUsers (alias de send)
     * 
     * @param array $data El payload que será pasado a la acción
     * @return $this Para encadenamiento de métodos
     */
    public function data(array $data): SuspenseAction
    {
        return $this->send($data);
    }
}
