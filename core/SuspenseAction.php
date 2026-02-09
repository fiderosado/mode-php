<?php

namespace Core;

use Core\Http\CSRF;

class SuspenseAction
{

    //private static ?SuspenseAction $instance = null;
    private array $args = [];
    private ?string $id = null;
    private ?string $suspenseId = null;
    private ?string $suspenseAction = null;

    public static string $BASE_URL;
    private string $hash;

    private function __construct(array $args = [])
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        self::$BASE_URL = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $this->args = $args;
        $this->suspenseAction = $args[0] ?? null;
        $this->hash = hash('sha256', $this->suspenseAction);
    }

    public static function in(...$args): SuspenseAction
    {
        /* self::$instance = new self($args);
        return self::$instance; */
        return new self($args);
    }

    public function setSuspenseId(string $id): void
    {
        $this->suspenseId = $id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getName(): ?string
    {
        return $this->suspenseAction;
    }

    //<script src="' . self::$BASE_URL . '/SerJS/SerJS.js">/* SuspenseAction */</script>
    public function build(): string
    {
        return sprintf(
            '<div data-suspense="%s" data-action="%s" data-target="%s"></div>',
            htmlspecialchars($this->hash),
            htmlspecialchars($this->suspenseAction ?? ''),
            htmlspecialchars($this->suspenseId ?? '')
        );
    }
}
