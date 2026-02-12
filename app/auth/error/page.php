<?php

/**
 * Página de Error y Debug - Sistema de Autenticación
 * Ruta: /auth/error
 * 
 * Captura y muestra errores del proceso de autenticación
 * Muestra variables de sesión, cookies, y parámetros relevantes
 */

// Cargar configuración de Auth primero para iniciar la sesión correctamente
try {
    $Auth = require __DIR__ . '/../../../auth.config.php';
} catch (Exception $e) {
    // Si falla Auth, intentamos iniciar sesión manualmente como fallback
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Capturar el error de la URL
$error = $_GET['error'] ?? 'Error desconocido';
$errorCode = $_GET['code'] ?? null;
$provider = $_GET['provider'] ?? 'unknown';

// Decodificar el error si viene codificado
$error = urldecode($error);

// Función para formatear valores
function formatValue($value)
{
    if (is_null($value)) {
        return '<span style="color: #999; font-style: italic;">null</span>';
    }
    if (is_bool($value)) {
        return $value ? '<span style="color: #10b981;">true</span>' : '<span style="color: #ef4444;">false</span>';
    }
    if (is_array($value)) {
        return '<pre style="margin: 0; font-size: 12px;">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
    }
    if (is_string($value) && strlen($value) > 100) {
        return '<span style="font-size: 11px; word-break: break-all;">' . htmlspecialchars($value) . '</span>';
    }
    return htmlspecialchars($value);
}

// Obtener información relevante
$debugInfo = [
    'Error Principal' => [
        'Mensaje' => $error,
        'Código' => $errorCode ?: 'N/A',
        'Provider' => $provider,
        'Timestamp' => date('Y-m-d H:i:s'),
    ],
    'Variables de Sesión' => [
        'oauth_state (guardado)' => $_SESSION['oauth_state'] ?? null,
        'callbackUrl' => $_SESSION['callbackUrl'] ?? null,
        'Todas las variables' => $_SESSION,
    ],
    'Parámetros GET' => $_GET ?: ['Ninguno'],
    'Parámetros POST' => $_POST ?: ['Ninguno'],
    'Cookies Relevantes' => [
        'auth.session-token' => $_COOKIE['auth.session-token'] ?? null,
        'PHPSESSID' => $_COOKIE['PHPSESSID'] ?? null,
    ],
    'Variables de Entorno' => [
        'APP_URL' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null,
        'AUTH_SECRET (existe)' => !empty($_ENV['AUTH_SECRET'] ?? getenv('AUTH_SECRET')),
        'AUTH_GOOGLE_ID (existe)' => !empty($_ENV['AUTH_GOOGLE_ID'] ?? getenv('AUTH_GOOGLE_ID')),
        'AUTH_GOOGLE_SECRET (existe)' => !empty($_ENV['AUTH_GOOGLE_SECRET'] ?? getenv('AUTH_GOOGLE_SECRET')),
    ],
    'Información del Servidor' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? null,
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? null,
        'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? null,
    ],
];

// Intentar obtener la sesión de Auth si existe
try {
    $Auth = require __DIR__ . '/../../../auth.config.php';
    $session = $Auth->getSession();
    $debugInfo['Sesión de Auth'] = [
        'Sesión activa' => $session !== null,
        'Usuario' => $session['user'] ?? null,
        'Token (existe)' => !empty($session['token'] ?? null),
        'Expira' => isset($session['expires_at']) ? date('Y-m-d H:i:s', $session['expires_at']) : null,
    ];
} catch (Exception $e) {
    $debugInfo['Sesión de Auth'] = [
        'Error' => $e->getMessage()
    ];
}

// Mapeo de errores comunes
$errorMessages = [
    'Estado de OAuth inválido' => [
        'titulo' => 'Error de Estado OAuth (CSRF)',
        'descripcion' => 'El token de estado no coincide. Esto puede ocurrir si:',
        'causas' => [
            'Las cookies están deshabilitadas en el navegador',
            'La sesión expiró durante el proceso de autenticación',
            'Se intentó usar el mismo link de autorización dos veces',
            'Hay un problema con la configuración de sesiones del servidor',
        ],
        'soluciones' => [
            'Asegúrate de que las cookies estén habilitadas',
            'Intenta en una ventana de incógnito/privada',
            'Limpia las cookies del navegador',
            'Verifica que session_start() se ejecute correctamente',
        ],
    ],
    'redirect_uri_mismatch' => [
        'titulo' => 'URI de Redirección no Coincide',
        'descripcion' => 'La URI de redirección no está configurada en Google Cloud Console.',
        'causas' => [
            'La URL no está en la lista de URIs autorizados',
            'Hay diferencias en el protocolo (http vs https)',
            'Hay diferencias en mayúsculas/minúsculas',
            'Falta o sobra una barra diagonal al final',
        ],
        'soluciones' => [
            'Ve a Google Cloud Console > Credenciales',
            'Agrega esta URI exacta: ' . ($_ENV['APP_URL'] ?? 'http://tu-dominio.com') . '/api/auth/callback/google',
            'Verifica que no haya espacios o caracteres extra',
        ],
    ],
    'access_denied' => [
        'titulo' => 'Acceso Denegado',
        'descripcion' => 'El usuario canceló o denegó la autorización en Google.',
        'causas' => [
            'El usuario hizo click en "Cancelar" en la pantalla de Google',
            'El usuario denegó los permisos solicitados',
        ],
        'soluciones' => [
            'Intenta el proceso de login nuevamente',
            'Acepta todos los permisos solicitados',
        ],
    ],
];

// Obtener información del error
$errorInfo = null;
foreach ($errorMessages as $key => $info) {
    if (stripos($error, $key) !== false) {
        $errorInfo = $info;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Autenticación - SerPro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #ef4444;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-icon {
            font-size: 40px;
        }

        .error-message {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 16px 20px;
            border-radius: 8px;
            margin-top: 20px;
            color: #991b1b;
            font-size: 16px;
            font-weight: 500;
        }

        .error-details {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .error-details h2 {
            color: #111827;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .error-details p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .causes-list,
        .solutions-list {
            margin: 16px 0;
            padding-left: 20px;
        }

        .causes-list li,
        .solutions-list li {
            color: #4b5563;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .solutions-list li {
            color: #059669;
        }

        .debug-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .debug-section h2 {
            color: #111827;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .debug-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .debug-table caption {
            text-align: left;
            font-weight: 600;
            font-size: 18px;
            color: #374151;
            padding: 12px 0;
            border-bottom: 2px solid #e5e7eb;
        }

        .debug-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            width: 30%;
        }

        .debug-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            word-break: break-word;
        }

        .debug-table tr:hover {
            background: #f9fafb;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .toggle-section {
            cursor: pointer;
            user-select: none;
        }

        .toggle-section::before {
            content: '▼ ';
            display: inline-block;
            transition: transform 0.3s;
        }

        .toggle-section.collapsed::before {
            transform: rotate(-90deg);
        }

        .collapsible-content {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .collapsible-content.hidden {
            max-height: 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .copy-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            color: #374151;
            margin-left: 8px;
        }

        .copy-btn:hover {
            background: #e5e7eb;
        }

        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding: 20px;
        }

        .footer a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span class="error-icon">⚠️</span>
                Error de Autenticación
            </h1>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        </div>

        <!-- Error Details (si existe información específica) -->
        <?php if ($errorInfo): ?>
            <div class="error-details">
                <h2><?php echo htmlspecialchars($errorInfo['titulo']); ?></h2>
                <p><?php echo htmlspecialchars($errorInfo['descripcion']); ?></p>

                <h3 style="color: #374151; margin-top: 20px; margin-bottom: 12px;">🔍 Posibles Causas:</h3>
                <ul class="causes-list">
                    <?php foreach ($errorInfo['causas'] as $causa): ?>
                        <li><?php echo htmlspecialchars($causa); ?></li>
                    <?php endforeach; ?>
                </ul>

                <h3 style="color: #059669; margin-top: 20px; margin-bottom: 12px;">✅ Soluciones:</h3>
                <ul class="solutions-list">
                    <?php foreach ($errorInfo['soluciones'] as $solucion): ?>
                        <li><?php echo htmlspecialchars($solucion); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Debug Information -->
        <div class="debug-section">
            <h2 class="toggle-section" onclick="toggleSection(this)">
                🐛 Información de Debug
            </h2>
            <div class="collapsible-content">
                <?php foreach ($debugInfo as $section => $data): ?>
                    <table class="debug-table">
                        <caption><?php echo htmlspecialchars($section); ?></caption>
                        <thead>
                            <tr>
                                <th>Variable</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $key => $value): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                    <td>
                                        <?php echo formatValue($value); ?>
                                        <?php if (is_string($value) && strlen($value) > 20): ?>
                                            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($value)); ?>')">
                                                Copiar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="debug-section">
            <h2>🔧 Acciones</h2>
            <div class="actions">
                <a href="/api/auth/google" class="btn btn-primary">🔄 Intentar de Nuevo</a>
                <a href="/" class="btn btn-secondary">🏠 Volver al Inicio</a>
                <button onclick="clearSession()" class="btn btn-danger">🗑️ Limpiar Sesión</button>
                <button onclick="copyDebugInfo()" class="btn btn-secondary">📋 Copiar Info Debug</button>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Sistema de Autenticación SerPro</p>
            <p><a href="https://developers.google.com/identity/protocols/oauth2" target="_blank">Documentación de Google OAuth</a></p>
        </div>
    </div>

    <script>
        function toggleSection(element) {
            element.classList.toggle('collapsed');
            const content = element.nextElementSibling;
            content.classList.toggle('hidden');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copiado al portapapeles');
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        }

        function copyDebugInfo() {
            const debugInfo = <?php echo json_encode($debugInfo, JSON_PRETTY_PRINT); ?>;
            const text = JSON.stringify(debugInfo, null, 2);
            navigator.clipboard.writeText(text).then(() => {
                alert('Información de debug copiada al portapapeles');
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        }

        async function clearSession() {
            if (confirm('¿Estás seguro de que quieres limpiar la sesión?')) {
                try {
                    await fetch('/api/auth/signout', {
                        method: 'POST',
                        credentials: 'include'
                    });
                    alert('Sesión limpiada. Recargando página...');
                    location.reload();
                } catch (error) {
                    alert('Error al limpiar sesión: ' + error.message);
                }
            }
        }

        // Auto-colapsar secciones largas al cargar
        document.addEventListener('DOMContentLoaded', () => {
            const tables = document.querySelectorAll('.debug-table');
            tables.forEach(table => {
                if (table.querySelectorAll('tbody tr').length > 10) {
                    const section = table.closest('.collapsible-content');
                    if (section) {
                        section.classList.add('hidden');
                        const toggle = section.previousElementSibling;
                        if (toggle) {
                            toggle.classList.add('collapsed');
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>