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

        if ($layout) {
            require $layout;
        } else {
            require $page;
        }
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
