# PROBLEMAS CORREGIDOS EN EL SISTEMA DE AUTENTICACIÓN

## PROBLEMA 1: DOBLE LLAMADA A Auth->signIn()
**Ubicación:** `Google->handleCallback()` y `callback/google/page.php`

**Antes:**
- `callback/google/page.php` llamaba directamente a crear JWT y cookies
- `Google->handleCallback()` llamaba a `Auth->signIn()` desde el provider
- Proceso duplicado y sin ejecutar callbacks

**Corregido:**
- `callback/google/page.php` llama SOLO a `Auth->signIn()` UNA VEZ
- `Google->handleCallback()` marcado como DEPRECATED (no se debe usar)
- Todo el flujo pasa por Auth->signIn() que ejecuta callbacks y eventos

---

## PROBLEMA 2: VALIDACIÓN DE STATE DUPLICADA
**Ubicación:** `Google->authorize()` y `callback/google/page.php`

**Antes:**
- Se validaba CSRF state en callback/google/page.php
- Se validaba OTRA VEZ en Google->authorize()

**Corregido:**
- Validación de state SOLO en callback/google/page.php
- Google->authorize() recibe credentials ya validados
- No hay validación duplicada

---

## PROBLEMA 3: SessionManager esperaba $user['id']
**Ubicación:** `SessionManager->create()`

**Antes:**
```php
$tokenPayload = [
    'sub' => $user['id'],  // Google no tiene 'id', tiene 'sub'
    'email' => $user['email'],
    ...
];
```

**Corregido:**
```php
$userId = $user['id'] ?? $user['sub'] ?? $user['email'];
$tokenPayload = [
    'sub' => $userId,  // Funciona con cualquier provider
    ...
];
```

---

## PROBLEMA 4: FALTABAN LOGS COMPLETOS
**Ubicación:** Todo el sistema

**Antes:**
- Pocos error_log
- Difícil seguir el flujo

**Corregido:**
- Logs en CADA paso del proceso:
  - Auth->signIn() inicio y fin
  - Cada callback ejecutado
  - Provider->authorize() paso a paso
  - SessionManager operaciones
  - TokenManager operaciones
  - Callbacks de Google API (Connect)

---

## PROBLEMA 5: ESTRUCTURA DE RESPUESTA Connect NO USADA
**Ubicación:** `Google->exchangeCodeForToken()` y `Google->getUserInfo()`

**Antes:**
- No se validaba correctamente la estructura `['success' => ['data' => ...]]`

**Corregido:**
```php
$tokenData['success']['data']['access_token']  // Estructura correcta
$userInfo["success"]["data"]  // Estructura correcta
```

---

## PROBLEMA 6: CALLBACKS NO EJECUTADOS CORRECTAMENTE
**Ubicación:** `Auth->signIn()`

**Antes:**
```php
$user = $this->executeCallback('jwt', $user, $provider) ?? $user;
// Sobrescribía user completo
```

**Corregido:**
```php
$jwtPayload = $this->executeCallback('jwt', [], $user, $provider, [], false);
$finalUser = array_merge($user, $jwtPayload ?? []);
// Combina datos originales con modificaciones del callback
```

---

## PROBLEMA 7: COOKIES CON NOMBRES DIFERENTES
**Ubicación:** `TokenManager`

**Antes:**
- callback/google/page.php usaba un nombre
- TokenManager usaba otro nombre

**Corregido:**
- Nombre único: `'auth.session-token'`
- Configuración unificada en TokenManager
- Logs al establecer/remover cookies

---

## FLUJO CORRECTO AHORA:

1. **Usuario visita `/api/auth/google`**
   - Se genera state
   - Se guarda en sesión
   - Redirige a Google

2. **Google redirige a `/api/auth/callback/google`**
   - Valida CSRF con state
   - Llama `Auth->signIn('google', ['code' => $code])`

3. **Auth->signIn() ejecuta:**
   - Evento 'signin'
   - Provider->authorize() → obtiene user de Google
   - Callback 'signIn' → valida si permitir
   - Callback 'jwt' → personaliza payload del token
   - SessionManager->create() → crea sesión y JWT
   - Callback 'session' → personaliza sesión
   - Evento 'signInSuccess'

4. **Callback redirige al usuario**
   - Usa callback 'redirect' para validar URL
   - Usuario autenticado con cookie establecida

---

## ARCHIVOS MODIFICADOS:

1. `Auth/Auth.php` - Logs y ejecución correcta de callbacks
2. `Auth/SessionManager.php` - Normalización de user data + logs
3. `Auth/TokenManager.php` - Cookie name unificado + logs
4. `Auth/Providers/Google.php` - Logs completos + sin handleCallback
5. `app/api/auth/callback/google/page.php` - Solo Auth->signIn()
6. `app/api/auth/google/page.php` - Logs
7. `auth.config.php` - Callbacks corregidos + logs

---

## LOGS QUE VERÁS:

```
Auth: Inicializando sistema de autenticación
Auth: Provider registrado: google
Auth: 4 callbacks registrados
Auth: 4 eventos registrados
...
=== /api/auth/google INICIADO ===
/api/auth/google: State generado: abc123...
Google: URL de autorización generada: https://accounts.google.com...
=== /api/auth/google REDIRIGIENDO A GOOGLE ===
...
=== CALLBACK GOOGLE INICIADO ===
Callback: CSRF validado correctamente
Callback: Llamando a Auth->signIn('google', ...)
=== Auth->signIn() INICIADO ===
Auth->signIn: Llamando a google->authorize()
Google->authorize() iniciado con credentials: {"code":"...","state":"..."}
Google: Iniciando intercambio de código por token
Google: Respuesta de token completa: {"success":{"data":{"access_token":"..."}}}
Google: Access token obtenido exitosamente
Google: Obteniendo información del usuario
Google: Respuesta de userinfo completa: {"success":{"data":{"sub":"...","email":"..."}}}
Google: Datos del usuario obtenidos exitosamente
Auth->signIn: User data recibido de provider: {...}
Callback signIn ejecutado
Callback jwt ejecutado
SessionManager->create: Creando sesión para usuario
SessionManager->create: Token generado
SessionManager->create: Cookie de token establecida
Callback session ejecutado
Event signInSuccess disparado
=== Auth->signIn() COMPLETADO EXITOSAMENTE ===
Callback: Redirigiendo a: /
=== CALLBACK GOOGLE COMPLETADO ===
```
