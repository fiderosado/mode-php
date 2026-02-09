<?php

namespace Core;

use Core\Utils\Console;

class Suspense
{
    private static ?Suspense $instance = null;
    private static int $instanceCounter = 0;

    private $fallback;
    private $content;
    private string $id;

    /**
     * Constructor privado
     */
    private function __construct($fallback, $content)
    {
        $this->id = 'suspense-' . self::$instanceCounter++;
        if (is_object($fallback)) {
            $fallback->__suspense_id = $this->generateRandomId();
        }
        $this->fallback = $fallback;
        $this->content  = $content;
    }

    /**
     * Inicializa la instancia y recibe fallback + content
     */
    public static function in($fallback, $content): Suspense
    {
        $args = func_get_args()[0] ?? [];
        self::$instance = new self($fallback, $content);
        return self::$instance;
    }

    /**
     * Por ahora solo muestra info en consola
     */
    public function build(): void
    {
        if (is_object($this->fallback) && method_exists($this->fallback, 'build')) {
            $this->fallback->setId($this->fallback->__suspense_id);
            print_r($this->fallback->build());
        }

        if (is_object($this->content)) {
            if (method_exists($this->content, 'setId')) {
                $this->content->setId($this->id);
            }
            if (method_exists($this->content, 'setSuspenseId')) {
                $this->content->setSuspenseId($this->fallback->__suspense_id);
            }
            if (method_exists($this->content, 'build')) {
                print_r($this->content->build());
            }
        }
    }

    /**
     * Genera un ID aleatorio seguro
     */
    private function generateRandomId(): string
    {
        return 'suspense:fb_' . bin2hex(random_bytes(8));
    }
}
