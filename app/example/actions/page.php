<?php
session_start();
use Core\Http\CSRF;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Actions Example</title>
    <?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($GLOBALS['css']); ?>">
    <?php endif; ?>
    <!-- Incluir SerJS -->
    <script src="../../SerJS/SerJS.js"></script>
</head>

<body>
    <div class="container mx-auto max-w-md">
        <h1>🚀 Server Actions Example</h1>
        <p>Prueba las diferentes acciones del servidor usando botones. Similar a React Server Actions.</p>

        <div class="actions-grid">

            <!-- Acción 4: Toggle con AJAX -->
            <div class="action-card">
                <h3>🔄 Toggle con AJAX</h3>
                <p>Ejecuta una acción usando JavaScript sin recargar la página.</p>
                <button class="btn-warning" id="toggleBtn">
                    Toggle Feature
                </button>

                <p id="togglemessage" class="p-2 mt-4"></p> 

            </div>
        </div>
    </div>

    <script type="module">
        const {
            useRef,
            useState,
            useEffect,
            setText,
            setHTML,
            Actions
        } = SerJS;

        const actionBoton = useRef('toggleBtn');
        const toggleMessage = useRef('togglemessage');

        actionBoton.onClick(toggleFeature);

        const toggleFeatureAction = await Actions(`<?= CSRF::token(); ?>`);
        
        async function toggleFeature() {
            const response = await toggleFeatureAction.call('toggleFeature', { enabled: true });
            if (response.success) {
                setText(toggleMessage,response.success.message);
            }
        }
    </script>
</body>

</html>