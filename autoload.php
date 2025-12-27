<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Url/SearchParams.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Autoload tipo Next.js / Node
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $path = __DIR__ . '/' . strtolower($class) . '.php';

    if (file_exists($path)) {
        require $path;
    }
});

