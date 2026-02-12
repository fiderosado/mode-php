# Implementación de la Clase Cookie en el Sistema de Autenticación

## Resumen de Cambios

Se ha implementado la clase `Cookie` del namespace `Core\Cookies` en todo el sistema de autenticación para reemplazar el uso directo de `setcookie()` y `$_COOKIE`. Esto proporciona una API consistente y más mantenible para el manejo de cookies.

## Archivos Modificados

### 1. **Auth/TokenManager.php**
**Cambios realizados:**
- Se importó la clase `Core\Cookies\Cookie`
- Se reemplazó el uso directo de `$_COOKIE[$this->cookieName]` por `Cookie::request()->get($this->cookieName)` en el método `getTokenFromRequest()`
- Se reemplazó `setcookie()` por `Cookie::response()->set()` en el método `setTokenCookie()`
- Se reemplazó `setcookie()` y `unset($_COOKIE[$this->cookieName])` por `Cookie::response()->delete()` en el método `removeTokenCookie()`

**Beneficios:**
- **Cookie de sesión principal (`auth.session-token`)**: Ahora se establece correctamente usando la clase Cookie
- Uso consistente de la API de cookies en todo el sistema
- Mejor manejo de errores y configuración de cookies
- Logs mejorados para debugging

### 2. **Auth/Providers/Google.php**
**Cambios realizados:**
- Se importó la clase `Core\Cookies\Cookie`
- Se modificó el método `getAuthorizationUrl()` para usar `Cookie::response()->set()` al guardar el `oauth_state_backup`
- Se agregaron logs informativos sobre el uso de la clase Cookie

**Beneficios:**
- **Cookie de backup del state OAuth (`oauth_state_backup`)**: Se establece de forma más robusta
- Consistencia con el resto del sistema
- Mejor configuración de opciones de cookie (httpOnly, sameSite, etc.)

### 3. **app/api/auth/callback/google/page.php**
**Cambios realizados:**
- Se importó la clase `Core\Cookies\Cookie`
- Se reemplazó el acceso directo a `$_COOKIE['oauth_state_backup']` por `Cookie::request()->get('oauth_state_backup')`
- Se reemplazó `setcookie()` por `Cookie::response()->delete()` para eliminar la cookie de backup
- Se agregó `$requestCookies->has()` para verificar la existencia de cookies
- Se actualizaron los logs para mostrar todas las cookies usando `$requestCookies->getAll()`

**Beneficios:**
- Lectura y eliminación consistente de cookies
- Mejor manejo del estado OAuth durante el callback
- Logs más informativos para debugging

### 4. **app/api/auth/google/page.php**
**Cambios realizados:**
- Se importó la clase `Core\Cookies\Cookie`
- Se reemplazó `setcookie()` por `Cookie::response()->set()` para establecer la cookie de backup del state
- Se agregaron logs informativos

**Beneficios:**
- Establecimiento consistente de la cookie de backup del state OAuth
- Mejor configuración de opciones de cookie

## Problema Resuelto

### Cookie `auth.session-token` no se estaba creando

**Causa:** 
El sistema usaba `setcookie()` nativo de PHP de forma inconsistente, lo que podía causar problemas con headers ya enviados o configuración incorrecta.

**Solución:**
Al implementar la clase `Cookie`, específicamente `Cookie::response()->set()`, se garantiza que:
1. Las cookies se establecen con la configuración correcta (httpOnly, sameSite, secure, etc.)
2. Se manejan mejor los casos de headers ya enviados
3. Se tiene una API consistente en todo el sistema
4. Los logs son más claros y útiles para debugging

## Cookies Gestionadas

El sistema ahora gestiona correctamente las siguientes cookies usando la clase Cookie:

1. **`auth` (sesión PHP)**: Gestionada automáticamente por `session_start()` con configuración en `SessionManager`
2. **`auth.session-token`**: Token JWT de sesión - Gestionada por `TokenManager`
3. **`oauth_state_backup`**: Backup del state OAuth para validación CSRF - Gestionada por `Google Provider` y endpoints de auth

## API de la Clase Cookie

### Lectura de Cookies (Request)
```php
use Core\Cookies\Cookie;

$cookies = Cookie::request();
$value = $cookies->get('nombre_cookie');  // Obtener una cookie
$exists = $cookies->has('nombre_cookie'); // Verificar si existe
$all = $cookies->getAll();                // Obtener todas las cookies
```

### Escritura de Cookies (Response)
```php
use Core\Cookies\Cookie;

$cookies = Cookie::response();

// Establecer una cookie
$cookies->set('nombre_cookie', 'valor', [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httpOnly' => true,
    'sameSite' => 'Lax'
]);

// Eliminar una cookie
$cookies->delete('nombre_cookie');

// Eliminar múltiples cookies
$cookies->delete(['cookie1', 'cookie2']);

// Limpiar todas las cookies
$cookies->clear();
```

## Verificación del Funcionamiento

Para verificar que las cookies se están creando correctamente:

1. **Inspeccionar las cookies en el navegador:**
   - Chrome DevTools → Application → Cookies
   - Debe aparecer `auth.session-token` después del login
   - Debe aparecer `oauth_state_backup` durante el flujo OAuth (temporalmente)

2. **Revisar los logs:**
   - Buscar mensajes como "Cookie establecida usando Cookie::response()"
   - Verificar que no hay errores de "headers already sent"

3. **Probar el flujo completo:**
   ```
   1. Ir a /api/auth/google
   2. Autenticarse con Google
   3. Verificar que se crea auth.session-token
   4. Verificar que la sesión persiste
   ```

## Notas Importantes

- La clase `Cookie` maneja automáticamente el envío de headers HTTP para las cookies
- `Cookie::request()` lee las cookies recibidas del cliente
- `Cookie::response()` establece/modifica las cookies que se enviarán al cliente
- Las opciones de configuración siguen el estándar de PHP `setcookie()`
- Los nombres de las opciones usan camelCase: `httpOnly`, `sameSite` (no snake_case)

## Próximos Pasos (Opcional)

1. Considerar migrar otras cookies del sistema a usar la clase Cookie
2. Implementar manejo de cookies cifradas si se necesita almacenar información sensible
3. Agregar validación de cookies en el middleware de autenticación
4. Implementar rotación automática de tokens
