# SOLUCIÓN AL PROBLEMA DE SESSION ID

## PROBLEMA DETECTADO EN LOGS:

```
Session ID: gn1ipo0nqbvb6amqa15bf90r07  ← /api/auth/google
Session ID:                              ← vacío en callback
SessionManager: Sesión PHP iniciada - ID: v7oi9h1n04puhf354f9j4e0eka  ← NUEVO ID diferente
```

**La sesión NO persiste entre el request inicial y el callback de Google.**

---

## CAUSAS IDENTIFICADAS:

### 1. Cookie de sesión no llega al callback
- Google redirige al callback pero la cookie de sesión PHP no se envía
- Posible problema de dominio/path en configuración de cookies
- `session_get_cookie_params()` podría tener configuración incorrecta

### 2. Session ID vacío ANTES de cargar Auth
- En el callback, `session_id()` está vacío antes de cargar auth.config.php
- Esto indica que NO hay cookie de sesión válida en el request

### 3. SessionManager crea NUEVA sesión
- Como no detecta sesión existente, PHP crea una nueva con ID diferente
- `$_SESSION['oauth_state']` está en la sesión VIEJA (gn1ipo0...)
- El callback busca en la sesión NUEVA (v7oi9h...) → no encuentra nada

---

## SOLUCIONES IMPLEMENTADAS:

### Solución 1: Cookie Backup para State (WORKAROUND)

**En `/api/auth/google/page.php`:**
```php
// Guardar state en cookie separada además de sesión
setcookie('oauth_state_backup', $state, [
    'expires' => time() + 600,  // 10 minutos
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

**En `/api/auth/callback/google/page.php`:**
```php
// Intentar obtener de sesión O de cookie backup
$savedState = $_SESSION['oauth_state'] ?? $_COOKIE['oauth_state_backup'] ?? null;

// Validar CSRF con cualquiera que exista
if ($_GET['state'] !== $savedState) {
    // error
}

// Limpiar cookie backup después de usar
setcookie('oauth_state_backup', '', ['expires' => time() - 3600, ...]);
```

---

### Solución 2: Logs Detallados para Diagnosticar

**Agregados logs en callback:**
```php
error_log("COOKIE recibidas: " . json_encode($_COOKIE));
error_log("Session ID ANTES de cargar Auth: " . session_id());
error_log("Session ID DESPUÉS de cargar Auth: " . session_id());
error_log("\$_SESSION completo: " . json_encode($_SESSION));
```

**Agregados logs en /api/auth/google:**
```php
error_log("Session ID: " . session_id());
error_log("Session name: " . session_name());
error_log("Cookie params: " . json_encode(session_get_cookie_params()));
error_log("State guardado en \$_SESSION: " . $_SESSION['oauth_state']);
```

---

## QUÉ ESPERAR EN PRÓXIMOS LOGS:

### Si la cookie de sesión funciona:
```
=== /api/auth/google ===
Session ID: abc123...
State guardado: f30cb6...

=== CALLBACK ===
COOKIE recibidas: {"auth": "abc123..."}  ← cookie presente
Session ID ANTES: abc123...  ← mismo ID
Session ID DESPUÉS: abc123...  ← mismo ID
State en $_SESSION: f30cb6...  ← state encontrado
CSRF validado correctamente usando: SESSION
```

### Si la cookie NO funciona (usar backup):
```
=== CALLBACK ===
COOKIE recibidas: {"oauth_state_backup": "f30cb6..."}  ← solo backup
Session ID ANTES:   ← vacío
Session ID DESPUÉS: xyz789...  ← nuevo ID
State en $_SESSION: null  ← no encontrado
State en cookie backup: f30cb6...  ← encontrado en backup
CSRF validado correctamente usando: COOKIE_BACKUP
```

---

## PRÓXIMOS PASOS SI EL PROBLEMA PERSISTE:

1. **Verificar configuración del servidor web**
   - Apache/Nginx debe permitir cookies
   - Headers `Set-Cookie` deben enviarse correctamente

2. **Revisar dominio en .env**
   ```
   APP_URL=http://dev.anfitrion.us:8011
   ```
   - El dominio debe coincidir exactamente
   - Puerto debe incluirse si no es estándar

3. **Verificar SessionManager cookie params**
   ```php
   session_set_cookie_params([
       'domain' => '',  // '' = dominio actual
       'path' => '/',   // todo el sitio
       'secure' => false,  // cambiar a true en HTTPS
       'samesite' => 'Lax'  // permitir en redirects
   ]);
   ```

4. **Considerar solución alternativa con Database**
   - Guardar state en Redis/DB en lugar de sesión
   - Usar token en URL como referencia

---

## ARCHIVOS MODIFICADOS:

1. `app/api/auth/google/page.php` - Cookie backup añadida
2. `app/api/auth/callback/google/page.php` - Lee de sesión O backup
3. `Auth/SessionManager.php` - Logs detallados agregados

---

## TESTING:

Ejecutar flujo completo y revisar logs para:
- ✓ Cookie `auth` presente en callback
- ✓ Session ID se mantiene igual
- ✓ `$_SESSION['oauth_state']` accesible en callback
- ✓ O usar `oauth_state_backup` como fallback
