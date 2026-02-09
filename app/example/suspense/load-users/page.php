<?php

use Core\Html\Suspense;
use Core\Html\Elements\Div;
use Core\SuspenseAction;

?>
<!DOCTYPE html>
<html>

<head>
    <title>Cargar Usuarios</title>
    <link href="/app/css/tailwind.css" rel="stylesheet">
    <script src="/SerJS/SerJS.js"></script>
</head>

<body class="p-8">
    <h1 class="text-3xl font-bold mb-6">👥 Lista de Usuarios</h1>

    <!-- ⭐ Componente Suspense -->
    <?php
    Suspense::in(
        // Fallback (mientras carga)
        Div::in(
            '<div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-blue-500 rounded-full animate-bounce"></div>
                <span>Cargando usuarios...</span>
            </div>'
        )->class("p-4 bg-yellow-100 border border-yellow-400 rounded"),

        // Acción a ejecutar
        SuspenseAction::in("loadUsers")->send([
            'userId' => 'fiderosado@gmail.com',
        ])
    )->render();
    ?>
</body>

</html>