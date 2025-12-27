<?php

namespace Core\Utils;

use Throwable;
use JsonSerializable;

class Console
{
    // constructor aqui
    public function __construct()
    {
        
    }
    /**
     * Log en consola del navegador (tipo console.log)
     */
    public static function log(...$args): void
    {
        // Evita romper respuestas JSON / headers
        if (headers_sent()) {
            return;
        }

        $output = [];

        foreach ($args as $arg) {
            // Manejo de excepciones / errores
            if ($arg instanceof Throwable) {
                $output[] = [
                    'type'    => get_class($arg),
                    'message' => $arg->getMessage(),
                    'file'    => $arg->getFile(),
                    'line'    => $arg->getLine(),
                    'trace'   => $arg->getTrace(),
                ];
                continue;
            }

            // Recursos
            if (is_resource($arg)) {
                $output[] = '[resource]';
                continue;
            }

            // Objetos no serializables
            if (is_object($arg) && !($arg instanceof JsonSerializable)) {
                $output[] = get_object_vars($arg);
                continue;
            }

            $output[] = $arg;
        }

        $json = json_encode(
            $output,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        echo "<script>console.log(...$json);</script>";
    }

    /**
     * Niveles: log | info | warn | error
     */
    public static function level(string $level, ...$args): void
    {
        $allowed = ['log', 'info', 'warn', 'error'];
        $level = in_array($level, $allowed, true) ? $level : 'log';

        if (headers_sent()) {
            return;
        }

        $json = json_encode(
            $args,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        echo "<script>console.$level(...$json);</script>";
    }
}
