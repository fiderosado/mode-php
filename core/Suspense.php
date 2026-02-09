<?php

namespace Core;

class Suspense
{
    private static int $instanceCounter = 0;

    private $fallback;
    private $content;

    private string $id;
    private string $fallbackId;

    private function __construct($fallback, $content)
    {
        $this->id = 'suspense-' . self::$instanceCounter++;
        $this->fallbackId = $this->generateRandomId();

        $this->fallback = $fallback;
        $this->content  = $content;
    }

    public static function in($fallback, $content): Suspense
    {
        return new self($fallback, $content);
    }

    public function build(): void
    {
        // 1️⃣ Render fallback
        if (is_object($this->fallback) && method_exists($this->fallback, 'setId')) {
            $this->fallback->setId($this->fallbackId);
        }

        if (is_object($this->fallback) && method_exists($this->fallback, 'build')) {
            echo $this->fallback->build();
        }

        // 2️⃣ Obtener datos del content (Action)
        $hash = null;
        $actionName = null;

        if (is_object($this->content)) {
            if (method_exists($this->content, 'getHash')) {
                $hash = $this->content->getHash();
            }
            if (method_exists($this->content, 'getName')) {
                $actionName = $this->content->getName();
            }
        }

        // 3️⃣ Render marcador declarativo
        echo sprintf(
            '<div data-suspense="%s" data-action="%s" data-target="%s"></div>',
            htmlspecialchars($hash ?? ''),
            htmlspecialchars($actionName ?? ''),
            htmlspecialchars($this->fallbackId)
        );
    }

    private function generateRandomId(): string
    {
        return 'suspense:fb_' . bin2hex(random_bytes(8));
    }
}
