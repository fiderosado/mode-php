<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspense PHP - Índice de Ejemplos</title>
    <?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($GLOBALS['css']); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <h1>🚀 PHP Suspense Component</h1>
        <p class="subtitle">
            Sistema de carga progresiva similar a React Suspense para PHP
        </p>
        
        <div class="features">
            <h3>✨ Características Principales</h3>
            <ul>
                <li>Streaming progresivo de contenido</li>
                <li>Múltiples instancias en una misma página</li>
                <li>Modo síncrono con streaming HTML</li>
                <li>Modo asíncrono con AJAX</li>
                <li>Eventos JavaScript personalizados</li>
                <li>Skeleton loading y spinners</li>
                <li>Compatible con cualquier framework PHP</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>📋 Demo Completa <span class="badge">Recomendado</span></h2>
            <p>
                Dashboard interactivo con múltiples componentes Suspense, mostrando:
                perfiles de usuario, estadísticas en tiempo real, y actividad reciente.
                Incluye animaciones skeleton, spinners y efectos de transición.
            </p>
            <div class="code-block">
                Suspense::render($fallback, $content)
            </div>
            <a href="suspense/dashboard" class="button">Ver Demo Completa →</a>
        </div>
        
        <div class="card">
            <h2>🎯 Ejemplos Simples</h2>
            <p>
                Colección de ejemplos básicos y directos mostrando diferentes casos de uso:
                texto simple, spinners, listas, delays personalizados, y múltiples suspense en paralelo.
                Perfecto para entender los fundamentos.
            </p>
            <div class="code-block">
                Suspense::stream($fallback, $content)
            </div>
            <a href="suspense/simple" class="button">Ver Ejemplos Simples →</a>
        </div>
        
        <div class="card">
            <h2>🌐 Ejemplos AJAX <span class="badge">Async</span></h2>
            <p>
                Demostración del modo asíncrono con carga AJAX. El contenido se obtiene
                desde endpoints separados después de renderizar la página inicial.
                Ideal para contenido dinámico y APIs externas.
            </p>
            <div class="code-block">
                Suspense::renderAsync($fallback, $url, $params)
            </div>
            <a href="suspense/ajax" class="button">Ver Ejemplos AJAX →</a>
        </div>
        
        <div class="card" style="background: #f8f9fa; border: 2px dashed #667eea;">
            <h2>📚 Documentación</h2>
            <p>
                Lee la documentación completa en <code>Core/Suspense.README.md</code>
                para conocer todos los métodos, parámetros, y mejores prácticas.
            </p>
            <div style="margin-top: 20px">
                <strong>Uso básico:</strong>
                <div class="code-block" style="margin-top: 10px;">
use Core\Suspense;

Suspense::render(
    function() {
        echo '&lt;div&gt;Cargando...&lt;/div&gt;';
    },
    function() {
        $data = fetchData(); // Operación lenta
        echo '&lt;div&gt;' . $data . '&lt;/div&gt;';
    }
);
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>
                💡 <strong>Tip:</strong> Abre la consola del navegador para ver los eventos de Suspense en tiempo real
            </p>
            <p style="margin-top: 10px;">
                Creado con ❤️ para mejorar la experiencia de usuario en aplicaciones PHP
            </p>
        </div>
    </div>
</body>
</html>
