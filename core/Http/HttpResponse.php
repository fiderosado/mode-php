<?php

namespace Core\Http;

use Core\Cookies\Cookie;
use Core\Cookies\Cookies;
use RuntimeException;


final class HttpResponse
{
    private static ?Cookies $cookieManager = null;

    /* =========================
       COOKIE MANAGER
       ========================= */

    private static function cookies(): Cookies
    {
        if (!self::$cookieManager) {
            self::$cookieManager = Cookie::response();
        }

        return self::$cookieManager;
    }

    /* =========================
       JSON
       ========================= */

    public static function json(mixed $data, array $options = []): never
    {
        http_response_code($options['status'] ?? 200);

        header('Content-Type: application/json; charset=utf-8');

        foreach ($options['headers'] ?? [] as $k => $v) {
            header("$k: $v");
        }

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        exit;
    }

    /* =========================
       HTML
       ========================= */

    public static function html(mixed $components, array $options = []): never
    {
        ob_clean();

        http_response_code($options['status'] ?? 200);

        header('Content-Type: text/html; charset=utf-8');

        foreach ($options['headers'] ?? [] as $k => $v) {
            header("$k: $v");
        }

        $items = is_array($components) ? $components : [$components];

        foreach ($items as $item) {
            if (is_object($item) && method_exists($item, 'build')) {
                echo $item->build();
            } elseif (is_string($item) || is_numeric($item)) {
                echo $item;
            }
        }

        exit;
    }

    /* =========================
       REDIRECT
       ========================= */

    public static function redirect(string $url, int $status = 302): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header("Location: $url");
        exit;
    }

    /* =========================
       HELPERS
       ========================= */

    private static function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            ($_SERVER['SERVER_PORT'] ?? 80) == 443
        );
    }
}
