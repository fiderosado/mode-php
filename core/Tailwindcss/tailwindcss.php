#!/usr/bin/env php
<?php

/**
 * Tailwind CSS CLI
 * Generate Tailwind CSS with PHP
 * A 1:1 port of @tailwindcss/cli - same options, same behavior.
 * Usage: tailwindphp [--input input.css] [--output output.css] [--watch] [options]
 */

declare(strict_types=1);
$autoloaderPaths = [
    __DIR__ . '../../../vendor/autoload.php',
];

$autoloadLoaded = false;

foreach ($autoloaderPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadLoaded = true;
        break;
    }
}

if (!$autoloadLoaded) {
    fwrite(STDERR, "Autoload de Composer no encontrado.\n");
    fwrite(STDERR, "Ejecuta 'composer install' para generar las dependencias.\n");
    exit(1);
}

echo "Tailwind CSS CLI... OK";

// Run the CLI application
$app = new TailwindCSS\TailwindCSS();
$exitCode = $app->run();

exit($exitCode);