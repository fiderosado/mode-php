<?php

namespace Core;

use Core\Http\ServerAction;
use Core\Http\CSRF;
use Core\Http\HttpResponse;
use Core\Http\Redirect;
use Core\Http\Security;
use core\Resolver;
use Core\Utils\Console;
use Exception;

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

        // Cargar actions.php si existe
        $actionsPath = dirname($page) . '/actions.php';
        if (file_exists($actionsPath)) {
            // Registrar la ruta actual para scope de acciones
            ServerAction::setCurrentPath($page);
            require_once $actionsPath;
        }

        // NUEVO: Interceptar Server Actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['__action'])) {

            /* ob_clean(); // elimina cualquier salida previa

            header('Content-Type: application/json; charset=utf-8');
            $arr = [
                "params"=> $params,
                "__action"=> $_GET['__action'],
                "page"=> $page,
                "POST"=> $_POST,
                "GET"=> $_GET,
            ];

            echo json_encode($arr);
            exit; */

            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            self::handleAction($_GET['__action'], $token, $params, $page);
            return; // Terminar aquí, no renderizar la página
        }

        self::render($page);
    }

    // Método para manejar Server Actions con seguridad
    protected static function handleAction(string $actionName, string $actionToken, array $params, string $pagePath)
    {
        try {
            session_start();
            // Limpiar cualquier salida previa (como <script> de Console::log)
            /* ob_clean(); // elimina cualquier salida previa

            header('Content-Type: application/json; charset=utf-8');

            $arr = [
                "actionName" => $actionName,
                "actionToken" => $actionToken,
                "params" => $params,
                "pagePath" => $pagePath,// "D:\\GitHub\\mode-php/app/example/actions/page.php"
                "isValidToken" => CSRF::verify($actionToken),
                "actualToken" => $_SESSION['csrf_token']
            ];

            echo json_encode($arr);
            exit; */

            // VERIFICAR CSRF TOKEN

            /* if (!CSRF::verify($actionToken)) {
                self::actionError('Invalid CSRF token', 403);
                return;
            } */

            // VERIFICAR ORIGIN (mismo dominio)
            if (!Security::verifyOrigin()) {
                self::actionError('Invalid origin', 403);
                return;
            }

            // RATE LIMITING
            $rateLimitKey = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ':' . $actionName;
            if (!RateLimit::check($rateLimitKey, 20, 1)) {
                self::actionError('Too many requests', 429);
                return;
            }

            // OBTENER DATOS DEL REQUEST
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
            } else {
                $data = $_POST;
            }

            // EJECUTAR LA ACCIÓN (verifica scope automáticamente)
            $result = ServerAction::execute($actionName, $data, $params, $pagePath);

            // MANEJAR RESPUESTA
            if ($result instanceof Redirect) {
                $result->execute();
                exit;
            }

            if ($result instanceof HttpResponse) {
                // retornar la instancia
                echo $result;
                exit;
            }

            // Retornar JSON
            //header('Content-Type: application/json');
            //echo json_encode($result);

            HttpResponse::json($result, ['status' => 200]);

            exit;
        } catch (\Exception $e) {
            self::actionError($e->getMessage(), 403);
        }
    }

    // Helper para errores de acciones
    protected static function actionError(string $message, int $code = 400)
    {
        HttpResponse::json(['error' => $message], ['status' => $code]);
        exit;
    }

    protected static function render($page)
    {
        $layout = Resolver::findLayout(dirname($page));
        $cssFile = Resolver::findCSS(dirname($page));
        $jsFile = Resolver::findJS(dirname($page));

        // Convertir path del CSS a URL relativa si existe
        $cssUrl = null;
        if ($cssFile) {
            $cssUrl = self::pathToUrl($cssFile);
        }

        // Convertir path del JS a URL relativa si existe
        $jsUrl = null;
        if ($jsFile) {
            $jsUrl = self::pathToUrl($jsFile);
        }

        // Pasar CSS y JS como variables globales para que estén disponibles en layouts y páginas
        $GLOBALS['css'] = $cssUrl;
        $GLOBALS['js'] = $jsUrl;

        // Capturar el output
        ob_start();

        if ($layout) {
            require $layout;
        } else {
            require $page;
        }

        $html = ob_get_clean();

        // Inyectar CSS automáticamente antes del </head>
        if ($cssUrl) {
            $linkTag = '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '">';
            $html = preg_replace('/<\/head>/i', $linkTag . "\n</head>", $html, 1);
        }

        // Inyectar script automáticamente antes del </body>
        if ($jsUrl) {
            $csrfToken = CSRF::token();
            $scriptTag = '<script src="' . htmlspecialchars($jsUrl, ENT_QUOTES, 'UTF-8') . '" data-csrf="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '" defer></script>';
            $html = preg_replace('/<\/body>/i', $scriptTag . "\n</body>", $html, 1);
        }

        echo $html;
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
