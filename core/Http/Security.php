<?php

namespace Core\Http;

class Security
{

    // D:\\GitHub\\mode-php/app/example/actions/page.php
    public static function verifyOrigin(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (empty($origin)) {
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $originPort = parse_url($origin, PHP_URL_PORT);

        // Si no hay puerto explícito, usar el default
        if ($originPort === null) {
            $originPort = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 443 : 80;
        }

        [$hostOnly, $hostPort] = array_pad(explode(':', $host, 2), 2, null);

        if ($hostPort === null) {
            $hostPort = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 443 : 80;
        }

        return $originHost === $hostOnly && (int)$originPort === (int)$hostPort;
    }


    public static function verifyReferer(string $expectedPath): bool
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if (empty($referer)) {
            return false;
        }

        $refererPath = parse_url($referer, PHP_URL_PATH);
        return str_starts_with($refererPath, $expectedPath);
    }
}
