<?php

/**
 * Página de Debug General - Sistema de Autenticación
 * Ruta: /auth/debug
 * 
 * Muestra el estado completo del sistema de autenticación
 * Útil para desarrollo y diagnóstico
 */

// Solo permitir en desarrollo
$isDevelopment = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'dev.') === 0;

if (!$isDevelopment) {
    http_response_code(403);
    die('Acceso denegado. Esta página solo está disponible en desarrollo.');
}

// Cargar el sistema de Auth primero para iniciar sesión correctamente
$authError = null;
$Auth = null;
try {
    $Auth = require __DIR__ . '/../../../auth.config.php';
} catch (Exception $e) {
    $authError = $e->getMessage();
    // Fallback si falla Auth
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Función para verificar y formatear
function checkStatus($condition, $trueText = '✅ OK', $falseText = '❌ Error')
{
    return $condition ? $trueText : $falseText;
}

function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

// Obtener información del sistema
$systemInfo = [
    'PHP Version' => PHP_VERSION,
    'PHP SAPI' => php_sapi_name(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'OS' => PHP_OS,
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time') . 's',
    'Upload Max Size' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
];

// Verificar dependencias
$dependencies = [
    'Composer Autoload' => file_exists(__DIR__ . '/../../../vendor/autoload.php'),
    'Firebase JWT' => class_exists('Firebase\JWT\JWT'),
    'OpenSSL' => extension_loaded('openssl'),
    'JSON' => extension_loaded('json'),
    'cURL' => extension_loaded('curl'),
    'Session' => extension_loaded('session'),
];

// Verificar archivos del sistema
$requiredFiles = [
    'auth.config.php' => file_exists(__DIR__ . '/../../../auth.config.php'),
    'Auth/Auth.php' => file_exists(__DIR__ . '/../../../Auth/Auth.php'),
    'Auth/SessionManager.php' => file_exists(__DIR__ . '/../../../Auth/SessionManager.php'),
    'Auth/TokenManager.php' => file_exists(__DIR__ . '/../../../Auth/TokenManager.php'),
    'Auth/Providers/Google.php' => file_exists(__DIR__ . '/../../../Auth/Providers/Google.php'),
    'Auth/Providers/Provider.php' => file_exists(__DIR__ . '/../../../Auth/Providers/Provider.php'),
    '.env' => file_exists(__DIR__ . '/../../../.env'),
];

// Verificar variables de entorno
$envVars = [
    'APP_URL' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null,
    'AUTH_SECRET' => !empty($_ENV['AUTH_SECRET'] ?? getenv('AUTH_SECRET')),
    'AUTH_SECRET (length)' => strlen($_ENV['AUTH_SECRET'] ?? getenv('AUTH_SECRET') ?: '') . ' caracteres',
    'AUTH_GOOGLE_ID' => !empty($_ENV['AUTH_GOOGLE_ID'] ?? getenv('AUTH_GOOGLE_ID')),
    'AUTH_GOOGLE_SECRET' => !empty($_ENV['AUTH_GOOGLE_SECRET'] ?? getenv('AUTH_GOOGLE_SECRET')),
];

// Información de Auth
$authInfo = [];
$providersInfo = [];
$sessionInfo = [];

if ($Auth && !$authError) {
    try {
        $authInfo['Instancia Auth'] = '✅ Cargada correctamente';

        // Proveedores
        $providers = $Auth->getProviders();
        foreach ($providers as $name => $provider) {
            $providersInfo[$name] = [
                'Name' => $provider['name'],
                'Type' => $provider['type'],
                'ID' => $provider['id'],
            ];
        }

        // Sesión
        $session = $Auth->getSession();
        if ($session) {
            $sessionInfo['Estado'] = '✅ Sesión activa';
            $sessionInfo['Usuario ID'] = $session['user']['id'] ?? 'N/A';
            $sessionInfo['Email'] = $session['user']['email'] ?? 'N/A';
            $sessionInfo['Name'] = $session['user']['name'] ?? 'N/A';
            $sessionInfo['Provider'] = $session['user']['provider'] ?? 'N/A';
            $sessionInfo['Token (existe)'] = !empty($session['token']);
            $sessionInfo['Creada'] = date('Y-m-d H:i:s', $session['created_at'] ?? 0);
            $sessionInfo['Expira'] = date('Y-m-d H:i:s', $session['expires_at'] ?? 0);
        } else {
            $sessionInfo['Estado'] = '⚠️ No hay sesión activa';
        }
    } catch (Exception $e) {
        $authInfo['Error'] = $e->getMessage();
    }
} else {
    $authInfo['Error'] = $authError ?? 'No se pudo cargar Auth';
}

// Endpoints disponibles
$endpoints = [
    'Inicio Google OAuth' => '/api/auth/google',
    'Callback Google' => '/api/auth/callback/google',
    'Sign In' => '/api/auth/signin',
    'Sign Out' => '/api/auth/signout',
    'Session' => '/api/auth/session',
    'Providers' => '/api/auth/providers',
];

// Información de cookies
$cookies = $_COOKIE;

// Información de sesión PHP
$phpSessionInfo = [
    'Session ID' => session_id(),
    'Session Name' => session_name(),
    'Session Status' => [
        PHP_SESSION_DISABLED => 'Disabled',
        PHP_SESSION_NONE => 'None',
        PHP_SESSION_ACTIVE => 'Active'
    ][session_status()],
    'Save Path' => session_save_path(),
];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Sistema de Autenticación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #000;
            border: 2px solid #00ff00;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .status {
            font-size: 14px;
            opacity: 0.8;
        }

        .section {
            background: #000;
            border: 1px solid #00ff00;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .section h2 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 1px solid #00ff00;
            padding-bottom: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #003300;
            text-align: left;
            padding: 10px;
            border: 1px solid #00ff00;
            font-weight: bold;
        }

        td {
            padding: 8px 10px;
            border: 1px solid #003300;
        }

        tr:hover {
            background: #002200;
        }

        .status-ok {
            color: #00ff00;
        }

        .status-error {
            color: #ff0000;
        }

        .status-warning {
            color: #ffff00;
        }

        .code-block {
            background: #002200;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-top: 10px;
            border: 1px solid #00ff00;
        }

        .code-block pre {
            margin: 0;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            background: #003300;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
            font-family: inherit;
        }

        .btn:hover {
            background: #00ff00;
            color: #000;
        }

        .endpoint-list {
            list-style: none;
        }

        .endpoint-list li {
            padding: 8px 0;
            border-bottom: 1px solid #003300;
        }

        .endpoint-list li:last-child {
            border-bottom: none;
        }

        .endpoint-list a {
            color: #00ffff;
            text-decoration: none;
        }

        .endpoint-list a:hover {
            text-decoration: underline;
        }

        .provider-card {
            background: #002200;
            padding: 15px;
            border: 1px solid #00ff00;
            border-radius: 4px;
        }

        .provider-card h3 {
            color: #ffff00;
            margin-bottom: 10px;
        }

        .timestamp {
            opacity: 0.7;
            font-size: 12px;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🐛 SISTEMA DE AUTENTICACIÓN - DEBUG MODE</h1>
            <div class="status">
                Servidor: <?php echo $_SERVER['HTTP_HOST']; ?> |
                IP: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?> |
                Timestamp: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>

        <!-- Estado General -->
        <div class="section">
            <h2>📊 ESTADO GENERAL</h2>
            <div class="grid">
                <div>
                    <strong>Sistema de Auth:</strong>
                    <span class="<?php echo $Auth ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $Auth ? '✅ Operativo' : '❌ Error'; ?>
                    </span>
                </div>
                <div>
                    <strong>Sesión Activa:</strong>
                    <span class="<?php echo !empty($sessionInfo) && $sessionInfo['Estado'] === '✅ Sesión activa' ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo !empty($sessionInfo) && $sessionInfo['Estado'] === '✅ Sesión activa' ? '✅ Sí' : '⚠️ No'; ?>
                    </span>
                </div>
                <div>
                    <strong>Proveedores:</strong>
                    <span class="status-ok">
                        <?php echo count($providersInfo); ?> configurado(s)
                    </span>
                </div>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="section">
            <h2>⚙️ INFORMACIÓN DEL SISTEMA</h2>
            <table>
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($systemInfo as $key => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Dependencias -->
        <div class="section">
            <h2>📦 DEPENDENCIAS</h2>
            <table>
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dependencies as $name => $status): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <td class="<?php echo $status ? 'status-ok' : 'status-error'; ?>">
                                <?php echo checkStatus($status); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Archivos del Sistema -->
        <div class="section">
            <h2>📁 ARCHIVOS DEL SISTEMA</h2>
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requiredFiles as $file => $exists): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td class="<?php echo $exists ? 'status-ok' : 'status-error'; ?>">
                                <?php echo checkStatus($exists); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Variables de Entorno -->
        <div class="section">
            <h2>🔐 VARIABLES DE ENTORNO</h2>
            <table>
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Estado/Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($envVars as $name => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <td>
                                <?php
                                if (is_bool($value)) {
                                    echo '<span class="' . ($value ? 'status-ok' : 'status-error') . '">';
                                    echo checkStatus($value, '✅ Configurado', '❌ No configurado');
                                    echo '</span>';
                                } else {
                                    echo htmlspecialchars($value ?? 'N/A');
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Proveedores -->
        <?php if (!empty($providersInfo)): ?>
            <div class="section">
                <h2>🔌 PROVEEDORES CONFIGURADOS</h2>
                <div class="grid">
                    <?php foreach ($providersInfo as $name => $info): ?>
                        <div class="provider-card">
                            <h3><?php echo htmlspecialchars($info['Name']); ?></h3>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($info['ID']); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($info['Type']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sesión Actual -->
        <?php if (!empty($sessionInfo)): ?>
            <div class="section">
                <h2>👤 SESIÓN ACTUAL</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessionInfo as $key => $value): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($key); ?></td>
                                <td><?php echo is_bool($value) ? ($value ? 'true' : 'false') : htmlspecialchars($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Sesión PHP -->
        <div class="section">
            <h2>🔧 SESIÓN PHP</h2>
            <table>
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($phpSessionInfo as $key => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 style="margin-top: 20px; color: #ffff00;">Variables de Sesión:</h3>
            <div class="code-block">
                <pre><?php echo htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>

        <!-- Cookies -->
        <div class="section">
            <h2>🍪 COOKIES</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Valor (primeros 50 caracteres)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cookies)): ?>
                        <tr>
                            <td colspan="2">No hay cookies</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cookies as $name => $value): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($name); ?></td>
                                <td><?php echo htmlspecialchars(substr($value, 0, 50)) . (strlen($value) > 50 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Endpoints -->
        <div class="section">
            <h2>🌐 ENDPOINTS DISPONIBLES</h2>
            <ul class="endpoint-list">
                <?php foreach ($endpoints as $name => $path): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($name); ?>:</strong>
                        <a href="<?php echo htmlspecialchars($path); ?>" target="_blank">
                            <?php echo htmlspecialchars($path); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Acciones -->
        <div class="section">
            <h2>⚡ ACCIONES</h2>
            <div class="actions">
                <button onclick="location.reload()" class="btn">🔄 Recargar</button>
                <a href="/api/auth/google" class="btn">🔐 Test Login Google</a>
                <a href="/api/auth/session" class="btn" target="_blank">📊 Ver Sesión JSON</a>
                <a href="/api/auth/providers" class="btn" target="_blank">📋 Ver Proveedores JSON</a>
                <button onclick="testEndpoints()" class="btn">🧪 Test Endpoints</button>
                <button onclick="clearSession()" class="btn">🗑️ Limpiar Sesión</button>
            </div>
        </div>

        <!-- Timestamp -->
        <div class="timestamp">
            Generado: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

    <script>
        async function testEndpoints() {
            const endpoints = <?php echo json_encode($endpoints); ?>;
            console.log('🧪 Testing endpoints...');

            for (const [name, path] of Object.entries(endpoints)) {
                try {
                    const response = await fetch(path);
                    console.log(`${name} (${path}): ${response.status} ${response.statusText}`);
                } catch (error) {
                    console.error(`${name} (${path}): Error - ${error.message}`);
                }
            }

            alert('Test completado. Ver consola para resultados.');
        }

        async function clearSession() {
            if (confirm('¿Limpiar sesión?')) {
                try {
                    await fetch('/api/auth/signout', {
                        method: 'POST'
                    });
                    alert('Sesión limpiada');
                    location.reload();
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        }

        // Auto-refresh cada 30 segundos
        // setTimeout(() => location.reload(), 30000);
    </script>
</body>

</html>
