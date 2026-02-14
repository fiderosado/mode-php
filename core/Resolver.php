<?php

namespace Core;

use Core\Utils\Console;

class Resolver
{
    /**
     * Rutas (relativas al proyecto) donde NO se debe buscar layout
     */
    private const OMIT_LAYOUT_PATHS = [
        '/app/api/',
        '/app/test-js/',
    ];
    public static function resolve(array $segments, string $basePath, array &$params)
    {
        if (empty($segments)) {
            return file_exists("$basePath/page.php")
                ? "$basePath/page.php"
                : null;
        }

        $segment = array_shift($segments);
        $path = "$basePath/$segment";

        // Si existe un directorio exacto, continuar por ahí
        if (is_dir($path)) {
            return self::resolve($segments, $path, $params);
        }

        foreach (glob("$basePath/*") as $dir) {
            if (!is_dir($dir))
                continue;
            $basename = basename($dir);
            // [slug] dinámico simple (EXCLUSIVO)
            if (preg_match('/^\[(?!\.\.\.)[^\[\]]+\]$/', $basename)) {
                $key = trim($basename, '[]');
                $params[$key] = $segment;
                return self::resolve($segments, $dir, $params);
            }
        }

        // Buscar patrón [[...variable]] para capturar múltiples segmentos (catch-all)
        foreach (glob("$basePath/[[...]*[]]") as $dir) {
            $basename = basename($dir);
            if (preg_match('/^\[\[\.\.\.(.+)\]\]$/', $basename, $matches)) {
                $key = $matches[1];
                $params[$key] = array_merge([$segment], $segments);
                return self::resolve([], $dir, $params);
            }
        }
        return null;
    }

    /**
     * Verifica si la ruta está dentro de una ruta omitida
     */
    private static function isOmittedPath(string $absolutePath): bool
    {
        $projectRoot = self::normalizePath(realpath(__DIR__ . '/../'));
        $relativePath = str_replace($projectRoot, '', $absolutePath) . '/';

        foreach (self::OMIT_LAYOUT_PATHS as $omit) {
            if (str_starts_with($relativePath, $omit)) {
                return true;
            }
        }

        return false;
    }

    /* public static function findLayout(string $path)
    {
        var_dump($path);
        while ($path !== dirname($path)) {
            if (file_exists("$path/layout.php")) {
                return "$path/layout.php";
            }
            $path = dirname($path);
        }
        return null;
    }
 */

    public static function findLayout(string $path): ?string
    {
        $path = self::normalizePath(realpath($path));

        while ($path && $path !== dirname($path)) {

            if (self::isOmittedPath($path)) {
                return null;
            }

            if (file_exists($path . '/layout.php')) {
                return $path . '/layout.php';
            }

            $path = dirname($path);
        }

        return null;
    }

    /**
     * Busca archivos page.css en la ruta de la página y sus directorios padres
     * Similar a findLayout pero para archivos CSS
     */
    public static function findCSS(string $path): ?string
    {
        $path = self::normalizePath(realpath($path));

        while ($path && $path !== dirname($path)) {

            if (self::isOmittedPath($path)) {
                $path = dirname($path);
                continue;
            }

            if (file_exists($path . '/page.css')) {
                return $path . '/page.css';
            }

            $path = dirname($path);
        }

        return null;
    }

    /**
     * Busca archivos script.js en la ruta de la página y sus directorios padres
     * Similar a findCSS pero para archivos JavaScript
     */
    public static function findJS(string $path): ?string
    {
        $path = self::normalizePath(realpath($path));

        while ($path && $path !== dirname($path)) {

            if (self::isOmittedPath($path)) {
                $path = dirname($path);
                continue;
            }

            if (file_exists($path . '/script.js')) {
                return $path . '/script.js';
            }

            $path = dirname($path);
        }

        return null;
    }

    /**
     * Normaliza separadores de ruta
     */
    private static function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
