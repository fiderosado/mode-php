# 🍪 Sistema de Gestión de Cookies

Sistema moderno de manejo de cookies para PHP inspirado en Next.js y la Cookie Store API.

## 🚀 Inicio Rápido

```php
use Core\Cookies\Cookie;

// Leer cookies del request
$cookies = Cookie::request();
$session = $cookies->get('session');
echo $session?->value;

// Establecer cookies en el response
$cookies = Cookie::response();
$cookies->set('session', 'abc123', [
    'maxAge' => 3600,
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'lax'
]);

// Eliminar cookies
$cookies->delete('session');
```

## ✨ Características

- ✅ API moderna inspirada en Next.js
- ✅ Type-safe con objetos tipados
- ✅ RFC 6265 compliant
- ✅ Soporte para atributos modernos (Priority, Partitioned)
- ✅ Fluent interface (métodos encadenables)
- ✅ Parsing robusto de headers
- ✅ Iterator support

## 📚 Documentación

Para documentación completa, ejemplos y casos de uso, consulta:

- **[DOCUMENTATION.md](./DOCUMENTATION.md)** - Documentación completa con ejemplos reales
- **[MIGRATION.md](./MIGRATION.md)** - Guía de migración desde el sistema antiguo

## 🏗️ Arquitectura

```
Core\Cookies\
├── Cookie.php              # Factory y utilidades estáticas
├── RequestCookies.php      # Manejo de cookies del request
├── ResponseCookies.php     # Manejo de cookies del response
├── RequestCookie.php       # Objeto de cookie del request
├── ResponseCookie.php      # Objeto de cookie del response
└── CookieOptions.php       # Opciones de configuración
```

## 📖 Ejemplos Básicos

### Leer Cookies

```php
$cookies = Cookie::request();

// Obtener una cookie
$session = $cookies->get('session');
if ($session) {
    echo $session->value;
}

// Obtener todas
foreach ($cookies->getAll() as $cookie) {
    echo "{$cookie->name}: {$cookie->value}\n";
}

// Verificar existencia
if ($cookies->has('session')) {
    // ...
}

// Cantidad de cookies
$count = $cookies->size();
```

### Establecer Cookies

```php
$cookies = Cookie::response();

// Cookie simple
$cookies->set('user_id', '123');

// Cookie con opciones
$cookies->set('session', 'abc123', [
    'maxAge' => 3600,        // 1 hora
    'path' => '/',
    'domain' => '.example.com',
    'secure' => true,
    'httpOnly' => true,
    'sameSite' => 'strict',
    'priority' => 'high'
]);

// Encadenar métodos
$cookies
    ->set('session', 'abc123', ['httpOnly' => true])
    ->set('user_id', '123')
    ->delete('temp_token');
```

### Eliminar Cookies

```php
$cookies = Cookie::response();

// Eliminar una
$cookies->delete('session');

// Eliminar múltiples
$cookies->delete(['session', 'user_id']);

// Eliminar con path/domain específico
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);

// Limpiar todas
$cookies->clear();
```

## 🔐 Opciones de Cookies

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `maxAge` | `int` | - | Tiempo de vida en segundos |
| `expires` | `int` | - | Timestamp de expiración |
| `path` | `string` | `'/'` | Ruta de la cookie |
| `domain` | `string` | `null` | Dominio de la cookie |
| `secure` | `bool` | `false` | Solo HTTPS |
| `httpOnly` | `bool` | `false` | No accesible desde JS |
| `sameSite` | `string\|bool` | `'lax'` | 'lax', 'strict', 'none' |
| `priority` | `string` | `null` | 'low', 'medium', 'high' |
| `partitioned` | `bool` | `false` | CHIPS |

## 💡 Casos de Uso Comunes

### Autenticación

```php
// Login
$cookies = Cookie::response();
$cookies->set('session_token', $token, [
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'strict',
    'maxAge' => 86400 // 24 horas
]);

// Verificar sesión
$cookies = Cookie::request();
$token = $cookies->get('session_token')?->value;

// Logout
Cookie::response()->delete('session_token');
```

### Preferencias de Usuario

```php
// Guardar
$cookies = Cookie::response();
$cookies->set('theme', 'dark', [
    'maxAge' => 2592000, // 30 días
    'sameSite' => 'lax'
]);

// Leer
$theme = Cookie::request()->get('theme')?->value ?? 'light';
```

### OAuth State (CSRF Protection)

```php
// Generar state
$state = bin2hex(random_bytes(16));
$cookies = Cookie::response();
$cookies->set('oauth_state', $state, [
    'maxAge' => 600, // 10 minutos
    'httpOnly' => true,
    'sameSite' => 'lax'
]);

// Validar state
$savedState = Cookie::request()->get('oauth_state')?->value;
if ($state !== $savedState) {
    throw new Exception('Invalid state');
}
```

## ✅ Mejores Prácticas

### Seguridad

```php
// ✅ BIEN: Cookie segura
$cookies->set('session', $token, [
    'httpOnly' => true,  // Protección XSS
    'secure' => true,    // Solo HTTPS
    'sameSite' => 'strict' // Protección CSRF
]);

// ❌ MAL: Cookie insegura
$cookies->set('session', $token, [
    'httpOnly' => false,
    'secure' => false,
    'sameSite' => 'none'
]);
```

### Lectura Segura

```php
// ✅ BIEN: Null-safe
$value = $cookies->get('session')?->value;

// ❌ MAL: Puede lanzar error
$value = $cookies->get('session')->value;
```

### Eliminación Correcta

```php
// ✅ BIEN: Mismo path y domain que al crear
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);
```

## 🔗 Enlaces

- [Documentación Completa](./DOCUMENTATION.md)
- [Guía de Migración](./MIGRATION.md)
- [RFC 6265](https://tools.ietf.org/html/rfc6265)
- [MDN - Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie)

---

**Versión:** 1.0.0  
**Framework:** Mode-PHP
