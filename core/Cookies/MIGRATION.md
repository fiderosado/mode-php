# Guía de Migración - Sistema de Cookies

Esta guía te ayudará a migrar del sistema antiguo de cookies al nuevo sistema basado en Next.js.

## Cambios Principales

### 1. Estructura de Clases

**Antes:**
- `Cookies` (interfaz)
- `RequestCookies` y `ResponseCookies` implementaban `Cookies`
- Retornaban valores simples (strings/arrays)

**Ahora:**
- `RequestCookie` y `ResponseCookie` (clases de datos)
- `RequestCookies` y `ResponseCookies` (manejadores)
- `CookieOptions` (opciones de configuración)
- Retornan objetos tipados

### 2. Constructor de RequestCookies

**Antes:**
```php
$cookies = Cookie::request($arraySource);
// o
$cookies = new RequestCookies($_COOKIE);
```

**Ahora:**
```php
$cookies = Cookie::request(); // Lee de $_COOKIE automáticamente
// o
$cookies = Cookie::request($cookieHeaderString); // Parse header Cookie
// o
$cookies = new RequestCookies($cookieHeaderString);
```

### 3. Método get()

**Antes:**
```php
$value = $cookies->get('session'); // Retorna string|null
```

**Ahora:**
```php
$cookie = $cookies->get('session'); // Retorna RequestCookie|ResponseCookie|null
$value = $cookie?->value; // Acceder al valor
```

### 4. Método getAll()

**Antes:**
```php
$all = $cookies->getAll(); // Retorna array asociativo ['name' => 'value']
$filtered = $cookies->getAll('session'); // Retorna array filtrado
```

**Ahora:**
```php
$all = $cookies->getAll(); // Retorna RequestCookie[]|ResponseCookie[]
$filtered = $cookies->getAll('session'); // Retorna array con un elemento

// Para convertir a array asociativo:
$assoc = [];
foreach ($cookies->getAll() as $cookie) {
    $assoc[$cookie->name] = $cookie->value;
}
```

### 5. Método set() - Opciones

**Antes:**
```php
$cookies->set('session', 'value', [
    'expires' => time() + 3600,
    'sameSite' => 'Lax' // Mayúscula
]);
```

**Ahora:**
```php
$cookies->set('session', 'value', [
    'maxAge' => 3600, // Usar maxAge en lugar de expires
    'sameSite' => 'lax' // Minúscula
]);

// O usar CookieOptions
use Core\Cookies\CookieOptions;
$options = new CookieOptions([
    'maxAge' => 3600,
    'sameSite' => 'lax'
]);
$cookies->set('session', 'value', $options);
```

### 6. Método delete()

**Antes:**
```php
$result = $cookies->delete('session'); // Retorna bool
$results = $cookies->delete(['session', 'user']); // Retorna array de bool
```

**Ahora:**
```php
// RequestCookies
$result = $cookies->delete('session'); // Retorna bool
$results = $cookies->delete(['session', 'user']); // Retorna bool[]

// ResponseCookies
$cookies->delete('session'); // Retorna $this (fluent)
$cookies->delete(['session', 'user']); // Retorna $this (fluent)

// Con opciones específicas (importante para path/domain)
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);
```

### 7. Nuevo método size()

**Antes:**
```php
$count = count($cookies->getAll());
```

**Ahora:**
```php
$count = $cookies->size(); // Solo en RequestCookies
```

### 8. Opciones de Cookies

**Cambios en nombres de opciones:**

| Antes | Ahora | Notas |
|-------|-------|-------|
| `expires` | `maxAge` o `expires` | Preferir `maxAge` (segundos) |
| `httpOnly` | `httpOnly` | Sin cambios |
| `sameSite` | `sameSite` | Ahora en minúsculas: 'lax', 'strict', 'none' |
| `secure` | `secure` | Sin cambios |
| `domain` | `domain` | Ahora acepta `null` para vacío |
| `path` | `path` | Sin cambios |
| - | `priority` | Nuevo: 'low', 'medium', 'high' |
| - | `partitioned` | Nuevo: bool para CHIPS |

## Ejemplos de Migración

### Ejemplo 1: Leer Cookie

**Antes:**
```php
$cookies = Cookie::request();
$sessionValue = $cookies->get('session');
if ($sessionValue) {
    echo $sessionValue;
}
```

**Ahora:**
```php
$cookies = Cookie::request();
$sessionCookie = $cookies->get('session');
if ($sessionCookie) {
    echo $sessionCookie->value;
}
```

### Ejemplo 2: Establecer Cookie

**Antes:**
```php
$cookies = Cookie::response();
$cookies->set('session', 'abc123', [
    'expires' => time() + 3600,
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'Strict'
]);
```

**Ahora:**
```php
$cookies = Cookie::response();
$cookies->set('session', 'abc123', [
    'maxAge' => 3600,
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'strict' // minúscula
]);
```

### Ejemplo 3: Listar Todas las Cookies

**Antes:**
```php
$cookies = Cookie::request();
$allCookies = $cookies->getAll();
foreach ($allCookies as $name => $value) {
    echo "$name: $value\n";
}
```

**Ahora:**
```php
$cookies = Cookie::request();
$allCookies = $cookies->getAll();
foreach ($allCookies as $cookie) {
    echo "{$cookie->name}: {$cookie->value}\n";
}
```

### Ejemplo 4: Eliminar Cookie con Path/Domain

**Antes:**
```php
$cookies = Cookie::response();
$cookies->delete('session');
```

**Ahora:**
```php
$cookies = Cookie::response();
// Simple
$cookies->delete('session');

// Con opciones específicas (importante si se estableció con path/domain)
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);
```

### Ejemplo 5: Iterar Cookies

**Antes:**
```php
$cookies = Cookie::request();
foreach ($cookies->getAll() as $name => $value) {
    // ...
}
```

**Ahora:**
```php
$cookies = Cookie::request();
// Opción 1: getAll()
foreach ($cookies->getAll() as $cookie) {
    echo $cookie->name . ': ' . $cookie->value;
}

// Opción 2: Iterator
foreach ($cookies as $cookie) {
    echo $cookie->name . ': ' . $cookie->value;
}
```

## Checklist de Migración

- [ ] Actualizar todas las llamadas a `get()` para acceder a `->value`
- [ ] Cambiar `expires` por `maxAge` en opciones
- [ ] Cambiar `sameSite` a minúsculas ('lax', 'strict', 'none')
- [ ] Actualizar loops de `getAll()` para usar objetos en lugar de arrays
- [ ] Cambiar `domain` vacío de `''` a `null`
- [ ] Verificar llamadas a `delete()` con path/domain específicos
- [ ] Actualizar tests que dependan de la estructura de datos

## Beneficios del Nuevo Sistema

1. **Type Safety**: Objetos tipados en lugar de arrays asociativos
2. **Compatibilidad**: API similar a Next.js y Cookie Store API
3. **Mejor Parsing**: Manejo correcto de headers Cookie y Set-Cookie
4. **Más Opciones**: Soporte para `priority` y `partitioned`
5. **Fluent Interface**: Métodos encadenables en ResponseCookies
6. **Documentación**: Mejor documentación y ejemplos

## Soporte

Si encuentras problemas durante la migración, revisa:
- `core/Cookies/README.md` - Documentación completa
- Ejemplos en `app/example/cookies/`
- Tests (si existen)
