<?php

use Core\Suspense;
use Core\Html\Elements\Div;
use Core\Action;
use Core\Utils\Console;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspense - Ejemplo Simple Reconstruido</title>
    <?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($GLOBALS['css']); ?>">
    <?php endif; ?>
</head>

<body>
    <div class="container">
        <h1>🚀 Suspense con Patrón Fluent</h1>

        <div class="info">
            <strong>💡 Nota:</strong> Abre la consola del navegador (F12) para ver los logs de debug de Suspense.
        </div>

        <!-- Ejemplo 1: Suspense básico con Div -->
        <div class="box">
            <h2>Ejemplo 1: Suspense Básico</h2>
            <?php
            Suspense::in(
                Div::in(
                    "loading....."
                )->class("p-4 bg-red-500"),
                Action::in("hola-mundo",["userId"=>123])
            )->build();
            ?>
        </div>
</body>

</html>