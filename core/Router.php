<?php

namespace Core;

use core\Resolver;

class Router
{
    public static function handle()
    {

        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = $uri ? explode('/', $uri) : [];

        $params = [];
        $basePath = dirname(__DIR__) . '/app';

        $page = Resolver::resolve($segments, $basePath, $params);

        if (!$page) {
            self::notFound();
            return;
        }

        $GLOBALS['params'] = $params;
        $GLOBALS['page'] = $page;

        extract([
            'params' => $params
        ]);

        self::render($page);
    }

    protected static function render($page)
    {
        $layout = Resolver::findLayout(dirname($page));
        $cssFile = Resolver::findCSS(dirname($page));

        // Convertir path del CSS a URL relativa si existe
        $cssUrl = null;
        if ($cssFile) {
            $cssUrl = self::pathToUrl($cssFile);
        }

        // Pasar CSS como variable global para que esté disponible en layouts y páginas
        $GLOBALS['css'] = $cssUrl;

        if ($layout) {
            require $layout;
        } else {
            require $page;
        }
    }

    /**
     * Convierte un path absoluto de archivo a una URL relativa
     */
    private static function pathToUrl(string $filePath): string
    {
        $projectRoot = realpath(dirname(__DIR__));
        $normalizedFilePath = realpath($filePath);
        
        // Normalizar ambos paths usando forward slashes
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $normalizedFilePath = str_replace('\\', '/', $normalizedFilePath);
        
        // Obtener la ruta relativa
        $relativePath = str_replace($projectRoot, '', $normalizedFilePath);
        
        // Limpiar y normalizar la ruta
        $relativePath = ltrim($relativePath, '/');
        
        return '/' . $relativePath;
    }
    protected static function notFound()
    {
        http_response_code(404);

        $nf = dirname(__DIR__) . '/app/not-found.php';
        $default404 = dirname(__DIR__) . '/Core/Error/404.php';

        if (file_exists($nf)) {
            require $nf;
        } elseif (file_exists($default404)) {
            require $default404;
        } else {
            echo '404';
        }
    }

}
