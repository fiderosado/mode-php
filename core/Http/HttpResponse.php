<?php
namespace Core\Http;

use RuntimeException;

final class HttpResponse
{
    /* =========================
       JSON , ['status' => 201]
       ========================= */

    public static function json(mixed $data, array $options = []): never
    {
        ob_clean();

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
       SECURE COOKIE
       ========================= */

    public static function setCookie(
        string $name,
        string $value,
        array $options = []
    ): void {
        // Defaults ultra seguros
        $defaults = [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => null,
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ];

        $opts = array_merge($defaults, $options);

        /* =========================
           Prefijos de seguridad
           ========================= */

        if (str_starts_with($name, '__Host-')) {
            $opts['secure'] = true;
            $opts['path'] = '/';
            $opts['domain'] = null;
        }

        if (str_starts_with($name, '__Secure-')) {
            $opts['secure'] = true;
        }

        /* =========================
           Validaciones duras
           ========================= */

        if ($opts['samesite'] === 'None' && !$opts['secure']) {
            throw new RuntimeException(
                'SameSite=None requiere Secure=true'
            );
        }

        setcookie($name, $value, [
            'expires'  => $opts['expires'],
            'path'     => $opts['path'],
            'domain'   => $opts['domain'],
            'secure'   => $opts['secure'],
            'httponly' => $opts['httponly'],
            'samesite' => $opts['samesite'],
        ]);
    }

    /* =========================
       DELETE COOKIE
       ========================= */

    public static function deleteCookie(string $name): void
    {
        self::setCookie($name, '', [
            'expires' => time() - 3600
        ]);
    }

    /* =========================
       REDIRECT
       ========================= */

    public static function redirect(string $url, int $status = 302): never
    {
        ob_clean();
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
