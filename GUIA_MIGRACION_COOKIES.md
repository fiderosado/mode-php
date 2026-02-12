# Guía Rápida de Migración a Cookie Class

## ⚡ Conversión Rápida

### Leer una Cookie
```php
// ❌ Antes
$value = $_COOKIE['nombre'] ?? null;

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::request();
$value = $cookies->get('nombre');
```

### Verificar si existe una Cookie
```php
// ❌ Antes
if (isset($_COOKIE['nombre'])) { ... }

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::request();
if ($cookies->has('nombre')) { ... }
```

### Establecer una Cookie
```php
// ❌ Antes
setcookie('nombre', 'valor', [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::response();
$cookies->set('nombre', 'valor', [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httpOnly' => true,  // ⚠️ Nota el camelCase
    'sameSite' => 'Lax'  // ⚠️ Nota el camelCase
]);
```

### Eliminar una Cookie
```php
// ❌ Antes
setcookie('nombre', '', time() - 3600, '/');
unset($_COOKIE['nombre']);

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::response();
$cookies->delete('nombre');
```

### Eliminar Múltiples Cookies
```php
// ❌ Antes
foreach (['cookie1', 'cookie2', 'cookie3'] as $name) {
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
}

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::response();
$cookies->delete(['cookie1', 'cookie2', 'cookie3']);
```

### Obtener Todas las Cookies
```php
// ❌ Antes
$allCookies = $_COOKIE;

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::request();
$allCookies = $cookies->getAll();
```

### Limpiar Todas las Cookies
```php
// ❌ Antes
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
    unset($_COOKIE[$name]);
}

// ✅ Después
use Core\Cookies\Cookie;
$cookies = Cookie::response();
$cookies->clear();
```

## 🎯 Patrones Comunes

### Patrón: Cookie con Valor por Defecto
```php
// Opción 1: Usando ??
$cookies = Cookie::request();
$value = $cookies->get('nombre') ?? 'default';

// Opción 2: Verificando primero
$cookies = Cookie::request();
if ($cookies->has('nombre')) {
    $value = $cookies->get('nombre');
} else {
    $value = 'default';
}
```

### Patrón: Cookie Temporal
```php
$cookies = Cookie::response();
$cookies->set('temp_data', 'value', [
    'expires' => time() + 600,  // 10 minutos
    'httpOnly' => true,
    'sameSite' => 'Strict'
]);
```

### Patrón: Cookie de Sesión
```php
$cookies = Cookie::response();
$cookies->set('session_data', 'value', [
    'expires' => 0,  // Cookie de sesión (expira al cerrar navegador)
    'httpOnly' => true,
    'sameSite' => 'Lax'
]);
```

### Patrón: Cookie Segura (HTTPS)
```php
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

$cookies = Cookie::response();
$cookies->set('secure_data', 'value', [
    'expires' => time() + 86400,
    'secure' => $isSecure,
    'httpOnly' => true,
    'sameSite' => 'Strict'
]);
```

### Patrón: Cookie con Dominio
```php
$cookies = Cookie::response();
$cookies->set('shared_data', 'value', [
    'expires' => time() + 86400,
    'domain' => '.ejemplo.com',  // Compartida entre subdominios
    'httpOnly' => true,
    'sameSite' => 'Lax'
]);
```

## 🔍 Búsqueda y Reemplazo

### Expresiones Regulares para Buscar Código Antiguo

1. **Buscar setcookie():**
   ```regex
   setcookie\s*\(
   ```

2. **Buscar $_COOKIE directo:**
   ```regex
   \$_COOKIE\[
   ```

3. **Buscar isset($_COOKIE):**
   ```regex
   isset\s*\(\s*\$_COOKIE\[
   ```

4. **Buscar unset($_COOKIE):**
   ```regex
   unset\s*\(\s*\$_COOKIE\[
   ```

## ⚙️ Opciones de Configuración

### Opciones Disponibles para set()
```php
[
    'expires' => int,        // Timestamp de expiración (0 = sesión)
    'path' => string,        // Ruta de la cookie (default: '/')
    'domain' => string,      // Dominio de la cookie (default: '')
    'secure' => bool,        // Solo HTTPS (default: false)
    'httpOnly' => bool,      // No accesible por JavaScript (default: false)
    'sameSite' => string     // 'Strict', 'Lax', o 'None' (default: null)
]
```

## 🚨 Errores Comunes y Soluciones

### Error 1: Headers Already Sent
```php
// ❌ Problema
echo "Algo";
$cookies = Cookie::response();
$cookies->set('nombre', 'valor');  // Error: headers already sent

// ✅ Solución: Establecer cookies ANTES de cualquier output
$cookies = Cookie::response();
$cookies->set('nombre', 'valor');
echo "Algo";
```

### Error 2: CamelCase vs snake_case
```php
// ❌ Incorrecto
$cookies->set('nombre', 'valor', [
    'httponly' => true,   // snake_case
    'samesite' => 'Lax'   // snake_case
]);

// ✅ Correcto
$cookies->set('nombre', 'valor', [
    'httpOnly' => true,   // camelCase
    'sameSite' => 'Lax'   // camelCase
]);
```

### Error 3: Olvidar importar la clase
```php
// ❌ Error
$cookies = Cookie::request();  // Fatal error: Class 'Cookie' not found

// ✅ Solución
use Core\Cookies\Cookie;
$cookies = Cookie::request();
```

## 📝 Checklist de Migración

Para migrar un archivo:

- [ ] Importar la clase: `use Core\Cookies\Cookie;`
- [ ] Reemplazar lecturas: `$_COOKIE['x']` → `Cookie::request()->get('x')`
- [ ] Reemplazar verificaciones: `isset($_COOKIE['x'])` → `Cookie::request()->has('x')`
- [ ] Reemplazar escrituras: `setcookie(...)` → `Cookie::response()->set(...)`
- [ ] Reemplazar eliminaciones: `setcookie(..., time()-3600)` → `Cookie::response()->delete(...)`
- [ ] Verificar nombres de opciones en camelCase
- [ ] Actualizar logs si es necesario
- [ ] Probar el código modificado

## 🧪 Testing

### Probar Lectura de Cookie
```php
// Crear cookie de prueba manualmente
$_COOKIE['test'] = 'value';

// Probar con Cookie::request()
$cookies = Cookie::request();
assert($cookies->get('test') === 'value');
assert($cookies->has('test') === true);
```

### Probar Escritura de Cookie
```php
// Establecer cookie
$cookies = Cookie::response();
$cookies->set('test', 'value');

// Verificar que se envió (requiere inspección de headers)
// O verificar en el navegador después de hacer la petición
```

## 💡 Tips

1. **Usar constantes para nombres de cookies:**
   ```php
   const COOKIE_AUTH_TOKEN = 'auth.session-token';
   const COOKIE_OAUTH_STATE = 'oauth_state_backup';
   
   $cookies->set(self::COOKIE_AUTH_TOKEN, $token);
   ```

2. **Crear métodos helper:**
   ```php
   private function setSecureCookie(string $name, string $value, int $ttl = 3600): void
   {
       $cookies = Cookie::response();
       $cookies->set($name, $value, [
           'expires' => time() + $ttl,
           'httpOnly' => true,
           'secure' => $this->isHttps(),
           'sameSite' => 'Lax'
       ]);
   }
   ```

3. **Logging para debugging:**
   ```php
   $cookies = Cookie::response();
   $cookies->set('nombre', 'valor', $options);
   error_log("Cookie establecida: nombre = valor");
   ```

---
**Última actualización:** 2024
**Compatibilidad:** PHP 8.0+
