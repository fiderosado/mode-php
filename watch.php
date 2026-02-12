<?php

set_time_limit(0);
ini_set('memory_limit', '256M');

// Ruta del log (ajusta si es necesario)
$logFile = __DIR__ . '/php_error_log';

// Verifica que exista
if (!file_exists($logFile)) {
    die("El archivo de log no existe\n");
}

echo "Monitoreando: $logFile\n";
echo "Presiona CTRL + C para salir\n\n";

$lastSize = filesize($logFile);

while (true) {

    clearstatcache();
    $currentSize = filesize($logFile);

    if ($currentSize > $lastSize) {
        $fp = fopen($logFile, "r");
        fseek($fp, $lastSize);

        while (($line = fgets($fp)) !== false) {
            echo $line;
        }

        fclose($fp);
        $lastSize = $currentSize;
    }

    usleep(500000); // 0.5 segundos
}
