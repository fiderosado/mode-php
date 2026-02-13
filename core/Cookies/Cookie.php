<?php

namespace Core\Cookies;

/**
 * Factory class for creating cookie handlers
 * Similar to Next.js cookie() function
 */
class Cookie
{
    /**
     * Create a RequestCookies instance for reading cookies from request
     * 
     * @param string|null $cookieHeader Optional Cookie header string
     */
    public static function request(?string $cookieHeader = null): RequestCookies
    {
        return new RequestCookies($cookieHeader);
    }

    /**
     * Create a ResponseCookies instance for setting cookies in response
     */
    public static function response(): ResponseCookies
    {
        return new ResponseCookies();
    }

    /**
     * Parse a Cookie header string into a map
     */
    public static function parse(string $cookieHeader): array
    {
        $cookies = [];
        $pairs = explode(';', $cookieHeader);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) {
                continue;
            }

            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $cookies[$name] = urldecode($value);
            }
        }

        return $cookies;
    }

    /**
     * Parse a Set-Cookie header string into a ResponseCookie
     */
    public static function parseSetCookie(string $setCookieHeader): ?ResponseCookie
    {
        $parts = explode(';', $setCookieHeader);
        if (empty($parts)) {
            return null;
        }

        // First part is name=value
        $nameValue = explode('=', trim($parts[0]), 2);
        if (count($nameValue) !== 2) {
            return null;
        }

        $name = trim($nameValue[0]);
        $value = urldecode(trim($nameValue[1]));

        $options = [
            'domain' => null,
            'path' => '/',
            'secure' => false,
            'httpOnly' => false,
            'sameSite' => 'lax',
            'expires' => null,
            'maxAge' => null,
            'priority' => null,
            'partitioned' => false,
        ];

        // Parse attributes
        for ($i = 1; $i < count($parts); $i++) {
            $attr = trim($parts[$i]);
            $attrParts = explode('=', $attr, 2);
            $attrName = strtolower(trim($attrParts[0]));
            $attrValue = isset($attrParts[1]) ? trim($attrParts[1]) : true;

            match ($attrName) {
                'domain' => $options['domain'] = $attrValue,
                'path' => $options['path'] = $attrValue,
                'secure' => $options['secure'] = true,
                'httponly' => $options['httpOnly'] = true,
                'samesite' => $options['sameSite'] = strtolower($attrValue),
                'expires' => $options['expires'] = strtotime($attrValue),
                'max-age' => $options['maxAge'] = (int) $attrValue,
                'priority' => $options['priority'] = strtolower($attrValue),
                'partitioned' => $options['partitioned'] = true,
                default => null,
            };
        }

        return new ResponseCookie(
            name: $name,
            value: $value,
            domain: $options['domain'],
            path: $options['path'],
            secure: $options['secure'],
            sameSite: $options['sameSite'],
            partitioned: $options['partitioned'],
            expires: $options['expires'],
            httpOnly: $options['httpOnly'],
            maxAge: $options['maxAge'],
            priority: $options['priority']
        );
    }

    /**
     * Stringify a cookie for Set-Cookie header
     */
    public static function stringify(ResponseCookie|RequestCookie $cookie): string
    {
        if ($cookie instanceof RequestCookie) {
            return $cookie->name . '=' . urlencode($cookie->value);
        }

        $parts = [$cookie->name . '=' . urlencode($cookie->value)];

        if ($cookie->expires !== null) {
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $cookie->expires);
        }

        if ($cookie->maxAge !== null) {
            $parts[] = 'Max-Age=' . $cookie->maxAge;
        }

        if ($cookie->domain !== null) {
            $parts[] = 'Domain=' . $cookie->domain;
        }

        if ($cookie->path !== null) {
            $parts[] = 'Path=' . $cookie->path;
        }

        if ($cookie->secure) {
            $parts[] = 'Secure';
        }

        if ($cookie->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($cookie->sameSite !== false) {
            $sameSite = $cookie->sameSite === true ? 'Strict' : ucfirst(strtolower((string) $cookie->sameSite));
            $parts[] = 'SameSite=' . $sameSite;
        }

        if ($cookie->partitioned) {
            $parts[] = 'Partitioned';
        }

        if ($cookie->priority !== null) {
            $parts[] = 'Priority=' . ucfirst(strtolower($cookie->priority));
        }

        return implode('; ', $parts);
    }
}
