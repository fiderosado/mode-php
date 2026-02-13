# 🍪 Sistema de Gestión de Cookies

Sistema completo de manejo de cookies para PHP basado en la implementación de Next.js y la Cookie Store API.

## 📋 Tabla de Contenidos

- [Características](#características)
- [Arquitectura](#arquitectura)
- [Guía Rápida](#guía-rápida)
- [Clases Principales](#clases-principales)
- [Ejemplos Reales](#ejemplos-reales)
- [API Completa](#api-completa)

## ✨ Características

- ✅ API moderna inspirada en Next.js
- ✅ Type-safe con objetos tipados
- ✅ RFC 6265 compliant
- ✅ Atributos modernos (Priority, Partitioned)
- ✅ Fluent interface
- ✅ Parsing robusto de headers
- ✅ Auto-send de cookies
- ✅ Iterator support

## 🏗️ Arquitectura

```
Core\Cookies\
├── Cookie.php              # Factory y utilidades
├── RequestCookies.php      # Cookies del request
├── ResponseCookies.php     # Cookies del response
├── RequestCookie.php       # Objeto cookie request
├── ResponseCookie.php      # Objeto cookie response
└── CookieOptions.php       # Opciones de configuración
```

## 🚀 Guía Rápida

### Leer Cookies del Request

```php
use Core\Cookies\Cookie;

$cookies = Cookie::request();
$sessionCookie = $cookies->get('session');

if ($sessionCookie) {
    echo $sessionCookie->value;
}
```


### Establecer Cookies en el Response

```php
use Core\Cookies\Cookie;

$cookies = Cookie::response();
$cookies->set('session', 'abc123', [
    'maxAge' => 3600,
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'lax'
]);
```

### Eliminar Cookies

```php
$cookies = Cookie::response();
$cookies->delete('session');
```

## 📚 Clases Principales

### 1. Cookie (Factory)

Clase factory para crear instancias de manejadores de cookies.

```php
// Leer cookies del request
$requestCookies = Cookie::request();

// Establecer cookies en el response
$responseCookies = Cookie::response();

// Parsear header Cookie
$parsed = Cookie::parse('session=abc; user=john');

// Parsear header Set-Cookie
$cookie = Cookie::parseSetCookie('session=abc; Path=/; HttpOnly');

// Convertir cookie a string
$header = Cookie::stringify($cookieObject);
```


### 2. RequestCookies

Maneja cookies del request (header `Cookie`).

**Métodos principales:**

```php
$cookies = Cookie::request();

// Obtener cantidad
$count = $cookies->size();

// Obtener una cookie
$cookie = $cookies->get('session');
$value = $cookie?->value;

// Obtener todas
$all = $cookies->getAll();

// Verificar existencia
if ($cookies->has('session')) {
    // ...
}

// Establecer (solo modifica estado interno)
$cookies->set('temp', 'value');

// Eliminar
$cookies->delete('session');
$cookies->delete(['session', 'user']); // múltiples

// Limpiar todas
$cookies->clear();

// Convertir a string
$headerValue = $cookies->toString();

// Iterar
foreach ($cookies as $cookie) {
    echo $cookie->name . ': ' . $cookie->value;
}
```


### 3. ResponseCookies

Maneja cookies del response (header `Set-Cookie`).

**Métodos principales:**

```php
$cookies = Cookie::response();

// Establecer cookie simple
$cookies->set('session', 'abc123');

// Establecer con opciones
$cookies->set('session', 'abc123', [
    'httpOnly' => true,
    'secure' => true,
    'sameSite' => 'strict',
    'maxAge' => 3600,
    'path' => '/',
    'domain' => '.example.com'
]);

// Establecer con CookieOptions
use Core\Cookies\CookieOptions;
$options = new CookieOptions(['httpOnly' => true, 'maxAge' => 3600]);
$cookies->set('session', 'abc123', $options);

// Establecer con ResponseCookie
use Core\Cookies\ResponseCookie;
$cookie = new ResponseCookie(
    name: 'session',
    value: 'abc123',
    httpOnly: true,
    maxAge: 3600
);
$cookies->set($cookie);

// Obtener cookie
$session = $cookies->get('session');

// Verificar existencia
if ($cookies->has('session')) {
    // ...
}

// Eliminar cookie
$cookies->delete('session');

// Eliminar con opciones (importante para path/domain)
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);

// Limpiar todas
$cookies->clear();
```


### 4. RequestCookie

Representa una cookie del request (solo name y value).

```php
use Core\Cookies\RequestCookie;

$cookie = new RequestCookie('session', 'abc123');

// Propiedades readonly
echo $cookie->name;  // 'session'
echo $cookie->value; // 'abc123'

// Convertir a array
$array = $cookie->toArray();

// Crear desde array
$cookie = RequestCookie::fromArray(['name' => 'session', 'value' => 'abc123']);
```

### 5. ResponseCookie

Representa una cookie del response con todos los atributos.

```php
use Core\Cookies\ResponseCookie;

$cookie = new ResponseCookie(
    name: 'session',
    value: 'abc123',
    domain: '.example.com',
    path: '/',
    secure: true,
    sameSite: 'strict',
    httpOnly: true,
    maxAge: 3600,
    priority: 'high'
);

// Todas las propiedades son readonly
echo $cookie->name;
echo $cookie->httpOnly;

// Convertir a array
$array = $cookie->toArray();

// Crear desde array
$cookie = ResponseCookie::fromArray($array);

// Obtener opciones
$options = $cookie->getOptions();
```


### 6. CookieOptions

Opciones de serialización de cookies basadas en RFC 6265.

```php
use Core\Cookies\CookieOptions;

$options = new CookieOptions([
    'domain' => '.example.com',
    'path' => '/',
    'secure' => true,
    'httpOnly' => true,
    'sameSite' => 'strict', // true, false, 'lax', 'strict', 'none'
    'maxAge' => 3600,
    'expires' => time() + 3600,
    'priority' => 'high', // 'low', 'medium', 'high'
    'partitioned' => false
]);

// Convertir a formato para setcookie()
$phpOptions = $options->toArray();

// Crear opciones para eliminar cookie
$deleteOptions = CookieOptions::forDeletion([
    'path' => '/',
    'domain' => '.example.com'
]);
```

## 🔥 Ejemplos Reales

### Ejemplo 1: Sistema de Autenticación (TokenManager)

Basado en `Auth/TokenManager.php`:

```php
use Core\Cookies\Cookie;

class TokenManager
{
    private string $cookieName = 'auth.session-token';
    private int $expiration = 86400;

    // Leer token desde cookie
    public function getTokenFromRequest(): ?string
    {
        $cookies = Cookie::request();
        $cookie = $cookies->get($this->cookieName);
        return $cookie?->value;
    }

    // Establecer token en cookie
    public function setTokenCookie(string $token): void
    {
        $domain = $_ENV['COOKIE_DOMAIN'] ?? '';
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        $cookies = Cookie::response();
        $cookies->set($this->cookieName, $token, [
            'maxAge' => $this->expiration,
            'path' => '/',
            'domain' => $domain ?: null,
            'secure' => $isSecure,
            'httpOnly' => true,
            'sameSite' => 'lax'
        ]);
    }

    // Eliminar token
    public function removeTokenCookie(): void
    {
        $cookies = Cookie::response();
        $cookies->delete($this->cookieName);
    }
}
```


### Ejemplo 2: OAuth State Management (Google Provider)

Basado en `Auth/Providers/Google.php`:

```php
use Core\Cookies\Cookie;

class GoogleProvider
{
    public function getAuthorizationUrl(): string
    {
        // Generar state para CSRF protection
        $state = bin2hex(random_bytes(16));
        
        // Guardar en sesión
        $_SESSION['oauth_state'] = $state;
        
        // Backup en cookie (por si la sesión no persiste)
        $cookies = Cookie::response();
        $cookies->set('oauth_state_backup', $state, [
            'maxAge' => 600, // 10 minutos
            'path' => '/',
            'httpOnly' => true,
            'sameSite' => 'lax'
        ]);
        
        return "https://accounts.google.com/o/oauth2/v2/auth?state=$state&...";
    }
    
    public function handleCallback(): void
    {
        $state = $_GET['state'] ?? null;
        
        // Leer state desde cookie backup
        $cookies = Cookie::request();
        $stateBackup = $cookies->get('oauth_state_backup');
        $savedState = $_SESSION['oauth_state'] ?? $stateBackup?->value;
        
        // Validar CSRF
        if ($state !== $savedState) {
            throw new Exception('Invalid state');
        }
        
        // Limpiar cookie backup
        Cookie::response()->delete('oauth_state_backup');
    }
}
```


### Ejemplo 3: Listar Cookies en Vista

Basado en `app/example/cookies/page.php`:

```php
use Core\Cookies\Cookie;

// Obtener todas las cookies
$request = Cookie::request();
$allCookies = $request->getAll();

// Convertir a array asociativo para la vista
$cookies = [];
foreach ($allCookies as $cookie) {
    $cookies[$cookie->name] = $cookie->value;
}

// Mostrar en tabla
?>
<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cookies as $name => $value): ?>
            <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= htmlspecialchars($value) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p>Total de cookies: <?= count($cookies) ?></p>
```

### Ejemplo 4: Server Action para Crear Cookie

Basado en `app/example/cookies/actions.php`:

```php
use Core\Cookies\Cookie;
use Core\Http\HttpResponse;
use Core\Http\ServerAction;

ServerAction::define('create-cookie', function ($data, $params) {
    $token = uniqid();
    $domain = $_ENV['COOKIE_DOMAIN'] ?? '';

    // Crear cookie
    $cookies = Cookie::response();
    $cookies->set('example-cookie', $token, [
        'maxAge' => 3600,
        'path' => '/',
        'domain' => $domain ?: null,
        'secure' => false,
        'httpOnly' => true,
        'sameSite' => 'lax'
    ]);

    // Obtener todas las cookies para respuesta
    $allCookies = $cookies->getAll();
    $cookiesData = [];
    foreach ($allCookies as $cookie) {
        $cookiesData[$cookie->name] = [
            'value' => $cookie->value,
            'path' => $cookie->path,
            'domain' => $cookie->domain,
            'secure' => $cookie->secure,
            'httpOnly' => $cookie->httpOnly,
        ];
    }

    HttpResponse::json([
        'success' => [
            'message' => 'Cookie creada',
            'data' => $cookiesData
        ]
    ]);
});
```


## 📖 API Completa

### Cookie (Factory)

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `request(?string $header)` | Crea RequestCookies | `RequestCookies` |
| `response()` | Crea ResponseCookies | `ResponseCookies` |
| `parse(string $header)` | Parsea header Cookie | `array` |
| `parseSetCookie(string $header)` | Parsea header Set-Cookie | `ResponseCookie\|null` |
| `stringify(RequestCookie\|ResponseCookie $cookie)` | Convierte a string | `string` |

### RequestCookies

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `size()` | Cantidad de cookies | `int` |
| `get(string\|RequestCookie $name)` | Obtiene una cookie | `RequestCookie\|null` |
| `getAll(string\|RequestCookie\|null $filter)` | Obtiene cookies | `RequestCookie[]` |
| `has(string $name)` | Verifica existencia | `bool` |
| `set(string\|RequestCookie $key, ?string $value)` | Establece cookie | `$this` |
| `delete(string\|string[] $names)` | Elimina cookie(s) | `bool\|bool[]` |
| `clear()` | Limpia todas | `$this` |
| `toString()` | Convierte a header | `string` |

### ResponseCookies

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `get(string\|ResponseCookie $name)` | Obtiene una cookie | `ResponseCookie\|null` |
| `getAll(string\|ResponseCookie\|null $filter)` | Obtiene cookies | `ResponseCookie[]` |
| `has(string $name)` | Verifica existencia | `bool` |
| `set(string\|ResponseCookie $key, ?string $value, array\|CookieOptions $options)` | Establece cookie | `$this` |
| `delete(string\|array $name)` | Elimina cookie | `$this` |
| `clear()` | Limpia todas | `$this` |
| `toString()` | Convierte a headers | `string` |

### RequestCookie

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `name` | `string` | Nombre de la cookie (readonly) |
| `value` | `string` | Valor de la cookie (readonly) |

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `toArray()` | Convierte a array | `array` |
| `fromArray(array $data)` | Crea desde array | `RequestCookie` |


### ResponseCookie

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `name` | `string` | Nombre de la cookie (readonly) |
| `value` | `string` | Valor de la cookie (readonly) |
| `domain` | `?string` | Dominio (readonly) |
| `path` | `?string` | Ruta (readonly) |
| `secure` | `bool` | Solo HTTPS (readonly) |
| `sameSite` | `string\|bool` | SameSite policy (readonly) |
| `partitioned` | `bool` | CHIPS (readonly) |
| `expires` | `?int` | Timestamp expiración (readonly) |
| `httpOnly` | `bool` | No accesible desde JS (readonly) |
| `maxAge` | `?int` | Tiempo de vida en segundos (readonly) |
| `priority` | `?string` | Prioridad (readonly) |

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `toArray()` | Convierte a array | `array` |
| `fromArray(array $data)` | Crea desde array | `ResponseCookie` |
| `getOptions()` | Obtiene CookieOptions | `CookieOptions` |

### CookieOptions

| Propiedad | Tipo | Valor por defecto | Descripción |
|-----------|------|-------------------|-------------|
| `domain` | `?string` | `null` | Dominio de la cookie |
| `expires` | `?int` | `null` | Timestamp de expiración |
| `httpOnly` | `bool` | `false` | No accesible desde JavaScript |
| `maxAge` | `?int` | `null` | Tiempo de vida en segundos |
| `partitioned` | `bool` | `false` | Cookies particionadas (CHIPS) |
| `path` | `string` | `'/'` | Ruta de la cookie |
| `priority` | `?string` | `null` | Prioridad: 'low', 'medium', 'high' |
| `sameSite` | `string\|bool` | `'lax'` | 'lax', 'strict', 'none', true, false |
| `secure` | `bool` | `false` | Solo enviar sobre HTTPS |

| Método | Descripción | Retorno |
|--------|-------------|---------|
| `toArray()` | Convierte a formato setcookie() | `array` |
| `forDeletion(array $options)` | Crea opciones para eliminar | `CookieOptions` |


## 🔐 Atributos de Cookies

### domain

Especifica el dominio para el cual la cookie es válida.

```php
$cookies->set('session', 'value', [
    'domain' => '.example.com' // Válida para todos los subdominios
]);
```

### path

Especifica la ruta para la cual la cookie es válida.

```php
$cookies->set('admin_token', 'value', [
    'path' => '/admin' // Solo válida en /admin/*
]);
```

### secure

Si es `true`, la cookie solo se envía sobre HTTPS.

```php
$cookies->set('session', 'value', [
    'secure' => true // Solo HTTPS
]);
```

### httpOnly

Si es `true`, la cookie no es accesible desde JavaScript (`document.cookie`).

```php
$cookies->set('session', 'value', [
    'httpOnly' => true // Protección XSS
]);
```

### sameSite

Controla el comportamiento de cookies cross-site:

- `'strict'` o `true`: Solo same-site requests
- `'lax'`: Same-site y navegación top-level (por defecto)
- `'none'` o `false`: Permite cross-site (requiere `secure: true`)

```php
$cookies->set('session', 'value', [
    'sameSite' => 'strict' // Máxima protección CSRF
]);
```

### maxAge

Tiempo de vida en segundos. Tiene precedencia sobre `expires`.

```php
$cookies->set('session', 'value', [
    'maxAge' => 3600 // 1 hora
]);
```

### expires

Timestamp de expiración. Si se establece `maxAge`, este se calcula automáticamente.

```php
$cookies->set('session', 'value', [
    'expires' => time() + 86400 // 24 horas
]);
```

### priority

Prioridad de la cookie cuando el navegador necesita eliminar cookies.

- `'low'`: Baja prioridad
- `'medium'`: Prioridad media (por defecto)
- `'high'`: Alta prioridad

```php
$cookies->set('session', 'value', [
    'priority' => 'high' // No eliminar fácilmente
]);
```

### partitioned

Habilita cookies particionadas (CHIPS - Cookies Having Independent Partitioned State).

```php
$cookies->set('tracking', 'value', [
    'partitioned' => true,
    'secure' => true // Requerido para partitioned
]);
```


## 💼 Casos de Uso

### 1. Autenticación con Session Cookie

```php
use Core\Cookies\Cookie;

// Login
function login(string $userId): void
{
    $token = generateToken($userId);
    
    $cookies = Cookie::response();
    $cookies->set('session_token', $token, [
        'httpOnly' => true,
        'secure' => true,
        'sameSite' => 'strict',
        'maxAge' => 86400, // 24 horas
        'path' => '/'
    ]);
}

// Verificar sesión
function getAuthenticatedUser(): ?string
{
    $cookies = Cookie::request();
    $sessionCookie = $cookies->get('session_token');
    
    if (!$sessionCookie) {
        return null;
    }
    
    return validateToken($sessionCookie->value);
}

// Logout
function logout(): void
{
    $cookies = Cookie::response();
    $cookies->delete('session_token');
}
```

### 2. Preferencias de Usuario

```php
use Core\Cookies\Cookie;

// Guardar preferencias
function saveUserPreferences(array $prefs): void
{
    $cookies = Cookie::response();
    $cookies->set('user_prefs', json_encode($prefs), [
        'maxAge' => 2592000, // 30 días
        'path' => '/',
        'sameSite' => 'lax'
    ]);
}

// Leer preferencias
function getUserPreferences(): array
{
    $cookies = Cookie::request();
    $prefsCookie = $cookies->get('user_prefs');
    
    if (!$prefsCookie) {
        return getDefaultPreferences();
    }
    
    return json_decode($prefsCookie->value, true) ?? [];
}
```

### 3. Cookie de Consentimiento

```php
use Core\Cookies\Cookie;

// Guardar consentimiento
function saveConsent(bool $analytics, bool $marketing): void
{
    $consent = [
        'analytics' => $analytics,
        'marketing' => $marketing,
        'timestamp' => time()
    ];
    
    $cookies = Cookie::response();
    $cookies->set('cookie_consent', json_encode($consent), [
        'maxAge' => 31536000, // 1 año
        'path' => '/',
        'sameSite' => 'lax'
    ]);
}

// Verificar consentimiento
function hasConsent(string $type): bool
{
    $cookies = Cookie::request();
    $consentCookie = $cookies->get('cookie_consent');
    
    if (!$consentCookie) {
        return false;
    }
    
    $consent = json_decode($consentCookie->value, true);
    return $consent[$type] ?? false;
}
```


### 4. Remember Me

```php
use Core\Cookies\Cookie;

// Activar "Remember Me"
function setRememberMe(string $userId): void
{
    $token = generateLongLivedToken($userId);
    
    $cookies = Cookie::response();
    $cookies->set('remember_token', $token, [
        'httpOnly' => true,
        'secure' => true,
        'sameSite' => 'strict',
        'maxAge' => 2592000, // 30 días
        'path' => '/'
    ]);
}

// Verificar Remember Me
function checkRememberMe(): ?string
{
    $cookies = Cookie::request();
    $rememberCookie = $cookies->get('remember_token');
    
    if (!$rememberCookie) {
        return null;
    }
    
    return validateLongLivedToken($rememberCookie->value);
}
```

### 5. CSRF Token

```php
use Core\Cookies\Cookie;

// Generar CSRF token
function generateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    
    $cookies = Cookie::response();
    $cookies->set('csrf_token', $token, [
        'httpOnly' => false, // Accesible desde JS
        'secure' => true,
        'sameSite' => 'strict',
        'maxAge' => 3600,
        'path' => '/'
    ]);
    
    return $token;
}

// Validar CSRF token
function validateCsrfToken(string $token): bool
{
    $cookies = Cookie::request();
    $csrfCookie = $cookies->get('csrf_token');
    
    if (!$csrfCookie) {
        return false;
    }
    
    return hash_equals($csrfCookie->value, $token);
}
```

### 6. Cookie con Dominio Específico

```php
use Core\Cookies\Cookie;

// Cookie para todos los subdominios
function setGlobalCookie(string $name, string $value): void
{
    $cookies = Cookie::response();
    $cookies->set($name, $value, [
        'domain' => '.example.com', // Válida para *.example.com
        'path' => '/',
        'maxAge' => 86400,
        'secure' => true,
        'sameSite' => 'lax'
    ]);
}

// Eliminar cookie con dominio específico
function deleteGlobalCookie(string $name): void
{
    $cookies = Cookie::response();
    $cookies->delete([
        'name' => $name,
        'domain' => '.example.com',
        'path' => '/'
    ]);
}
```


## ✅ Mejores Prácticas

### 1. Seguridad

```php
// ✅ BIEN: Cookie segura para autenticación
$cookies->set('session', $token, [
    'httpOnly' => true,  // No accesible desde JS
    'secure' => true,    // Solo HTTPS
    'sameSite' => 'strict', // Protección CSRF
    'maxAge' => 3600
]);

// ❌ MAL: Cookie insegura
$cookies->set('session', $token, [
    'httpOnly' => false, // Vulnerable a XSS
    'secure' => false,   // Puede ser interceptada
    'sameSite' => 'none' // Vulnerable a CSRF
]);
```

### 2. Tiempo de Vida

```php
// ✅ BIEN: Usar maxAge (más claro)
$cookies->set('session', $value, [
    'maxAge' => 3600 // 1 hora
]);

// ⚠️ ACEPTABLE: Usar expires
$cookies->set('session', $value, [
    'expires' => time() + 3600
]);

// ❌ MAL: Sin expiración (cookie de sesión)
$cookies->set('session', $value); // Se elimina al cerrar navegador
```

### 3. Dominio y Path

```php
// ✅ BIEN: Especificar dominio y path
$cookies->set('admin_token', $value, [
    'domain' => '.example.com',
    'path' => '/admin',
    'httpOnly' => true,
    'secure' => true
]);

// ⚠️ CUIDADO: Eliminar con mismo dominio/path
$cookies->delete([
    'name' => 'admin_token',
    'domain' => '.example.com',
    'path' => '/admin'
]);
```

### 4. Lectura de Cookies

```php
// ✅ BIEN: Verificar existencia y usar null-safe
$cookies = Cookie::request();
$sessionCookie = $cookies->get('session');
$value = $sessionCookie?->value;

if ($value) {
    // Usar valor
}

// ❌ MAL: Asumir que existe
$value = $cookies->get('session')->value; // Puede lanzar error
```

### 5. Iteración

```php
// ✅ BIEN: Usar foreach directamente
$cookies = Cookie::request();
foreach ($cookies as $cookie) {
    echo "{$cookie->name}: {$cookie->value}\n";
}

// ✅ BIEN: Usar getAll()
foreach ($cookies->getAll() as $cookie) {
    echo "{$cookie->name}: {$cookie->value}\n";
}
```

### 6. Fluent Interface

```php
// ✅ BIEN: Encadenar métodos
Cookie::response()
    ->set('session', $token, ['httpOnly' => true])
    ->set('user_id', $userId, ['maxAge' => 3600])
    ->delete('temp_token');
```

### 7. Desarrollo vs Producción

```php
// ✅ BIEN: Adaptar según entorno
$isProduction = $_ENV['APP_ENV'] === 'production';
$isSecure = $isProduction && isset($_SERVER['HTTPS']);

$cookies->set('session', $token, [
    'secure' => $isSecure,
    'domain' => $isProduction ? '.example.com' : null,
    'httpOnly' => true,
    'sameSite' => 'strict'
]);
```

### 8. Validación de Valores

```php
// ✅ BIEN: Validar antes de usar
$cookies = Cookie::request();
$sessionCookie = $cookies->get('session');

if ($sessionCookie && validateToken($sessionCookie->value)) {
    // Token válido
} else {
    // Token inválido o no existe
}
```


## 🔄 Comparación con Next.js

Este sistema está inspirado en Next.js, con adaptaciones para PHP:

| Característica | Next.js | Este Sistema |
|----------------|---------|--------------|
| Factory | `cookies()` | `Cookie::request()` / `Cookie::response()` |
| Request Cookies | `cookies().get()` | `Cookie::request()->get()` |
| Response Cookies | `cookies().set()` | `Cookie::response()->set()` |
| Objetos tipados | ✅ RequestCookie, ResponseCookie | ✅ RequestCookie, ResponseCookie |
| Async/Promises | ✅ Async | ❌ Síncrono (PHP) |
| Auto-send headers | ✅ En render | ✅ En setcookie() |
| Iterator | ✅ Iterable | ✅ IteratorAggregate |
| Parsing | ✅ Headers | ✅ Headers + $_COOKIE |

## 🐛 Troubleshooting

### Headers Already Sent

```php
// Problema: Headers ya enviados
$cookies = Cookie::response();
$cookies->set('session', 'value'); // Error: headers already sent

// Solución: Establecer cookies antes de cualquier output
ob_start(); // Buffer de salida
$cookies = Cookie::response();
$cookies->set('session', 'value');
// ... resto del código
ob_end_flush();
```

### Cookie No Se Elimina

```php
// Problema: Cookie no se elimina
$cookies->delete('session'); // No funciona

// Solución: Usar mismo path y domain que al crear
$cookies->delete([
    'name' => 'session',
    'path' => '/admin',
    'domain' => '.example.com'
]);
```

### Cookie No Visible en JavaScript

```php
// Problema: No puedo leer la cookie desde JS
$cookies->set('data', 'value', ['httpOnly' => true]);

// Solución: Desactivar httpOnly (solo si es seguro)
$cookies->set('data', 'value', ['httpOnly' => false]);
```

### Cookie No Se Envía en Cross-Site

```php
// Problema: Cookie no se envía en requests cross-site
$cookies->set('tracking', 'value', ['sameSite' => 'strict']);

// Solución: Usar sameSite: 'none' con secure
$cookies->set('tracking', 'value', [
    'sameSite' => 'none',
    'secure' => true // Requerido con sameSite: none
]);
```

## 📚 Referencias

- [RFC 6265 - HTTP State Management Mechanism](https://tools.ietf.org/html/rfc6265)
- [MDN - Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie)
- [Next.js Cookies](https://nextjs.org/docs/app/api-reference/functions/cookies)
- [Cookie Store API](https://wicg.github.io/cookie-store/)
- [SameSite Cookies Explained](https://web.dev/samesite-cookies-explained/)
- [CHIPS (Partitioned Cookies)](https://github.com/privacycg/CHIPS)

## 📄 Licencia

Este sistema es parte del framework Mode-PHP.

---

**Versión:** 1.0.0  
**Última actualización:** 2024  
**Autor:** Mode-PHP Team
