<?php
namespace Core;

class Resolver
{
    public static function resolve(array $segments, string $basePath, array &$params)
    {
        if (empty($segments)) {
            return file_exists("$basePath/page.php")
                ? "$basePath/page.php"
                : null;
        }

        $segment = array_shift($segments);
        $path = "$basePath/$segment";

        if (is_dir($path)) {
            return self::resolve($segments, $path, $params);
        }

        foreach (glob("$basePath/[[]*[]]") as $dir) {
            $key = trim(basename($dir), '[]');
            $params[$key] = $segment;
            return self::resolve($segments, $dir, $params);
        }

        return null;
    }

    public static function findLayout(string $path)
    {
        while ($path !== dirname($path)) {
            if (file_exists("$path/layout.php")) {
                return "$path/layout.php";
            }
            $path = dirname($path);
        }
        return null;
    }
}
