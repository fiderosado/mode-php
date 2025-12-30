<?php

namespace Core;

use core\Resolver;

class Router
{
    public static function handle()
    {

        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = $uri ? explode('/', $uri) : [];

        // Verificar si es una solicitud de archivo estático permitido
        if (self::isStaticFileRequest($uri)) {
            self::serveStaticFile($uri);
            return;
        }

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
     * Verifica si la solicitud es para un archivo estático permitido
     */
    private static function isStaticFileRequest(string $uri): bool
    {
        $allowedExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        
        return in_array($extension, $allowedExtensions);
    }

    /**
     * Sirve un archivo estático solo si está permitido y fue detectado por el sistema
     */
    private static function serveStaticFile(string $uri): void
    {
        $projectRoot = realpath(dirname(__DIR__));
        $filePath = $projectRoot . '/' . ltrim($uri, '/');
        
        // Normalizar path
        $filePath = realpath($filePath);
        
        // Verificar que el archivo existe y está dentro del proyecto
        if (!$filePath || !file_exists($filePath)) {
            self::notFound();
            return;
        }
        
        // Verificar que el archivo está dentro del directorio del proyecto
        $projectRootNormalized = str_replace('\\', '/', $projectRoot);
        $filePathNormalized = str_replace('\\', '/', $filePath);
        
        if (strpos($filePathNormalized, $projectRootNormalized) !== 0) {
            // Intento de acceso fuera del proyecto
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        
        // Validar que es un archivo estático permitido (no PHP ni otros sensibles)
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $blockedExtensions = ['php', 'env', 'json', 'log', 'md', 'yml', 'yaml', 'ini', 'conf'];
        
        if (in_array($extension, $blockedExtensions)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        
        // Verificar que el archivo fue detectado por el Resolver (es un page.css válido)
        // O está en directorios públicos permitidos
        $allowedDirs = ['/app/css/', '/SerJS/', '/vendor/'];
        $relativePath = str_replace($projectRootNormalized, '', $filePathNormalized);
        
        $isAllowed = false;
        foreach ($allowedDirs as $dir) {
            if (strpos($relativePath, $dir) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        // También permitir archivos page.css detectados por el sistema
        if (!$isAllowed && $extension === 'css' && basename($filePath) === 'page.css') {
            // Verificar que existe un page.php en el mismo directorio (archivo válido)
            $dir = dirname($filePath);
            if (file_exists($dir . '/page.php')) {
                $isAllowed = true;
            }
        }
        
        if (!$isAllowed) {
            http_response_code(403);
            echo 'Forbidden: File not allowed';
            return;
        }
        
        // Servir el archivo con headers apropiados
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');
        
        readfile($filePath);
        exit;
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
