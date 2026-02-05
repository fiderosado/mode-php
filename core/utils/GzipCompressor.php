<?php
namespace Core\Utils;
// GzipCompressor.php

class GzipCompressor
{
    /**
     * Inicializa la compresión GZIP si el navegador lo soporta
     */
    public static function start(): void
    {
        if (!headers_sent() && extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                ob_start('ob_gzhandler');
                return;
            }
        }

        // Buffer normal si no hay soporte
        ob_start();
    }

    /**
     * Limpia y envía el buffer (opcional)
     */
    public static function end(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
