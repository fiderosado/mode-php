<?php
/**
 * Script de Prueba - Verificación de Implementación de Cookies
 * 
 * Este script verifica que la clase Cookie esté correctamente implementada
 * en el sistema de autenticación.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Core\Cookies\Cookie;

echo "=== VERIFICACIÓN DE IMPLEMENTACIÓN DE COOKIES ===\n\n";

// Test 1: Verificar que la clase Cookie existe y es accesible
echo "Test 1: Verificando clase Cookie...\n";
try {
    $requestCookies = Cookie::request();
    $responseCookies = Cookie::response();
    echo "✓ Clase Cookie accesible correctamente\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Verificar que TokenManager existe y usa Cookie
echo "Test 2: Verificando TokenManager...\n";
try {
    require_once __DIR__ . '/Auth/TokenManager.php';
    
    // Verificar que el archivo contiene las referencias a Cookie
    $tokenManagerContent = file_get_contents(__DIR__ . '/Auth/TokenManager.php');
    
    if (strpos($tokenManagerContent, 'use Core\Cookies\Cookie') !== false) {
        echo "✓ TokenManager importa la clase Cookie\n";
    } else {
        echo "✗ TokenManager NO importa la clase Cookie\n";
    }
    
    if (strpos($tokenManagerContent, 'Cookie::request()') !== false) {
        echo "✓ TokenManager usa Cookie::request()\n";
    } else {
        echo "✗ TokenManager NO usa Cookie::request()\n";
    }
    
    if (strpos($tokenManagerContent, 'Cookie::response()') !== false) {
        echo "✓ TokenManager usa Cookie::response()\n";
    } else {
        echo "✗ TokenManager NO usa Cookie::response()\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Verificar Google Provider
echo "Test 3: Verificando Google Provider...\n";
try {
    $googleProviderContent = file_get_contents(__DIR__ . '/Auth/Providers/Google.php');
    
    if (strpos($googleProviderContent, 'use Core\Cookies\Cookie') !== false) {
        echo "✓ Google Provider importa la clase Cookie\n";
    } else {
        echo "✗ Google Provider NO importa la clase Cookie\n";
    }
    
    if (strpos($googleProviderContent, 'Cookie::response()') !== false) {
        echo "✓ Google Provider usa Cookie::response()\n";
    } else {
        echo "✗ Google Provider NO usa Cookie::response()\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Verificar archivos de API
echo "Test 4: Verificando archivos de API...\n";

$apiFiles = [
    '/app/api/auth/google/page.php',
    '/app/api/auth/callback/google/page.php'
];

foreach ($apiFiles as $file) {
    $fullPath = __DIR__ . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        
        if (strpos($content, 'use Core\Cookies\Cookie') !== false) {
            echo "✓ $file importa la clase Cookie\n";
        } else {
            echo "✗ $file NO importa la clase Cookie\n";
        }
        
        if (strpos($content, 'Cookie::request()') !== false || 
            strpos($content, 'Cookie::response()') !== false) {
            echo "✓ $file usa métodos de Cookie\n";
        } else {
            echo "✗ $file NO usa métodos de Cookie\n";
        }
    } else {
        echo "⚠ $file no encontrado\n";
    }
}

echo "\n";

// Test 5: Buscar uso antiguo de setcookie/$_COOKIE en archivos modificados
echo "Test 5: Verificando que no queden usos antiguos de cookies...\n";

$filesToCheck = [
    '/Auth/TokenManager.php',
    '/Auth/Providers/Google.php',
    '/app/api/auth/google/page.php',
    '/app/api/auth/callback/google/page.php'
];

$oldUsageFound = false;

foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        
        // Buscar setcookie() directo (excluyendo comentarios)
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            // Ignorar comentarios
            if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
                continue;
            }
            
            if (preg_match('/\bsetcookie\s*\(/', $line)) {
                echo "⚠ Uso de setcookie() encontrado en $file línea " . ($lineNum + 1) . "\n";
                $oldUsageFound = true;
            }
        }
    }
}

if (!$oldUsageFound) {
    echo "✓ No se encontraron usos antiguos de setcookie()\n";
}

echo "\n";

// Resumen
echo "=== RESUMEN ===\n";
echo "La implementación de la clase Cookie ha sido verificada.\n";
echo "Revisa los resultados anteriores para confirmar que todo está correcto.\n\n";

echo "Cookies que deben gestionarse:\n";
echo "1. auth.session-token - Token JWT (TokenManager)\n";
echo "2. oauth_state_backup - Backup del state OAuth (Google Provider)\n";
echo "3. auth - Sesión PHP (gestionada por session_start())\n\n";

echo "Para probar en el navegador:\n";
echo "1. Accede a /api/auth/google\n";
echo "2. Completa el flujo de autenticación con Google\n";
echo "3. Verifica en DevTools → Application → Cookies que existe 'auth.session-token'\n";
echo "4. Verifica en los logs del servidor que aparecen mensajes de 'Cookie establecida usando Cookie::response()'\n\n";

echo "=== FIN DE LA VERIFICACIÓN ===\n";
