<?php

namespace Core;

use Core\Http\CSRF;

class SuspenseAction
{

    private static ?SuspenseAction $instance = null;
    private array $args = [];
    private ?string $id = null;
    private ?string $suspenseId = null;
    private ?string $suspenseAction = null;

    public static string $BASE_URL;

    private function __construct(array $args = [])
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        self::$BASE_URL = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $this->args = $args;
        $this->suspenseAction = $args[0] ?? null;
    }

    public static function in(...$args): SuspenseAction
    {
        self::$instance = new self($args);
        return self::$instance;
    }

    public function setSuspenseId(string $id): void
    {
        $this->suspenseId = $id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
    //<script src="' . self::$BASE_URL . '/SerJS/SerJS.js">/* SuspenseAction */</script>
    public function build()
    {
        return '<head>
        <script type="module">
            (async () => {
                const { useRef, replaceHTML, Actions } = SerJS;
                window.__SerActions__ ??= {};
                window.__SerActions__["' . $this->id . '"] = await Actions("' . CSRF::token() . '");
                const response = await window.__SerActions__["' . $this->id . '"].call("' . $this->suspenseAction . '")
                replaceHTML(
                    useRef("' . $this->suspenseId . '"),
                    response ?? "Error en la accion.."
                );
            })();
            </script>
            </head>';
    }
}
