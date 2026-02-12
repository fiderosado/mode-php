# Auth para PHP - Documentación Completa

## 📋 Instalación

### 1. Requisitos
- PHP 8.0+
- Firebase JWT (para tokens JWT)
- Composer

### 2. Instalar dependencia Firebase JWT

```bash
composer require firebase/php-jwt
```

### 3. Variables de entorno

Crea un archivo `.env`:

```env
AUTH_SECRET=tu-super-secret-key-de-minimo-32-caracteres
AUTH_GOOGLE_ID=tu-google-client-id
AUTH_GOOGLE_SECRET=tu-google-client-secret
APP_URL=http://localhost:3000
```

## 📁 Estructura

```
Auth/
├── Auth.php                # Clase principal
├── SessionManager.php         # Gestión de sesiones con JWT
├── TokenManager.php           # Gestión de tokens JWT
├── Callbacks.php              # Callbacks de autenticación
├── Helpers.php                # Helpers globales
├── AuthMiddleware.php      # Middleware para proteger rutas
└── Providers/
    ├── Provider.php           # Interface Provider
    ├── Google.php             # Proveedor Google OAuth
    └── Credentials.php        # Proveedor Email/Password

auth.config.php             # Configuración centralizada de Auth

app/api/auth/
├── signin/page.php            # POST /api/auth/signin
├── signout/page.php           # POST /api/auth/signout
├── session/page.php           # GET /api/auth/session
├── providers/page.php         # GET /api/auth/providers
└── callback/
    └── google/page.php        # GET /api/auth/callback/google
```

## 🚀 Uso

### 1. Inicializar Auth

En tu bootstrap o punto de entrada:

```php
require_once __DIR__ . '/vendor/autoload.php';

use Auth\Auth;
use Auth\Providers\Google;
use Auth\Providers\Credentials;

// Crear instancia de Auth
$Auth = new Auth([
    'secret' => $_ENV['AUTH_SECRET'],
    'providers' => [
        'google' => new Google([
            'clientId' => $_ENV['AUTH_GOOGLE_ID'],
            'clientSecret' => $_ENV['AUTH_GOOGLE_SECRET'],
            'redirectUri' => $_ENV['APP_URL'] . '/api/auth/callback/google'
        ]),
        'credentials' => new Credentials()
    ]
]);
```

### 2. Proteger rutas

```php
use Auth\AuthMiddleware;

// Obtener el session manager
$sessionManager = $Auth->getSessionManager();

// Crear middleware
$auth = new AuthMiddleware($sessionManager, ['isApi' => true]);

// Requerir autenticación
$auth->require();

// Ahora puedes procesar la solicitud autenticada
$user = $sessionManager->getUser();
```

### 3. Usar helpers en tus vistas/controladores

```php
use function Auth\useSession;
use function Auth\useUser;
use function Auth\isAuthenticated;

// Obtener la sesión actual
$session = useSession();

// Obtener el usuario actual
$user = useUser();

// Verificar si está autenticado
if (isAuthenticated()) {
    echo "Bienvenido " . $user['name'];
}

// Obtener el token
$token = useToken();
```

### 4. Iniciar sesión (Frontend/AJAX)

```javascript
// Llamar al endpoint de signin
const response = await fetch('/api/auth/signin', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        provider: 'credentials',
        credentials: {
            email: 'user@example.com',
            password: 'password123'
        }
    })
});

const data = await response.json();

if (data.status === 'success') {
    console.log('Sesión iniciada', data.session);
    // Redirigir a dashboard
    window.location.href = '/dashboard';
}
```

### 5. Obtener la sesión actual (Frontend)

```javascript
const response = await fetch('/api/auth/session');
const data = await response.json();

if (data.session) {
    console.log('Usuario:', data.session.user);
}
```

### 6. Cerrar sesión

```javascript
const response = await fetch('/api/auth/signout', {
    method: 'POST'
});

if (response.ok) {
    window.location.href = '/';
}
```

### 7. Obtener proveedores disponibles

```javascript
const response = await fetch('/api/auth/providers');
const data = await response.json();

console.log(data.providers);
// {
//   google: { id: 'google', name: 'Google', type: 'oauth' },
//   credentials: { id: 'credentials', name: 'Credentials', type: 'credentials' }
// }
```

## 🔐 Callbacks

Auth tiene callbacks personalizables para diferentes etapas de autenticación:

### signIn
Se ejecuta cuando un usuario intenta iniciar sesión.

```php
'callbacks' => [
    'signIn' => function(array $user, string $provider = '') {
        // Retornar false para denegar el acceso
        if ($user['email'] === 'spam@example.com') {
            return false;
        }
        return true;
    }
]
```

### jwt
Se ejecuta cuando se crea o actualiza un JWT token.

```php
'callbacks' => [
    'jwt' => function(array $token, array $user = [], string $provider = '') {
        // Personalizar el contenido del token
        $token['role'] = $user['role'] ?? 'user';
        return $token;
    }
]
```

### session
Se ejecuta cuando se obtiene la sesión.

```php
'callbacks' => [
    'session' => function(array $session, array $user = []) {
        // Añadir información a la sesión
        $session['user']['role'] = $user['role'] ?? 'user';
        return $session;
    }
]
```

## 📢 Eventos

Los eventos se ejecutan en momentos específicos del ciclo de autenticación:

```php
'events' => [
    'signin' => function(array $message = []) {
        error_log('Usuario inició sesión');
    },
    'signout' => function() {
        error_log('Usuario cerró sesión');
    },
    'signInError' => function(array $message = []) {
        error_log('Error al iniciar sesión: ' . $message['error']);
    }
]
```

## 🛡️ Seguridad

### JWT + Cookies + Sesiones

Este sistema usa:

1. **JWT Tokens**: Almacenados en cookies con flags de seguridad
   - `HttpOnly`: No accesible desde JavaScript
   - `Secure`: Solo se envía por HTTPS en producción
   - `SameSite=Lax`: Protección contra CSRF

2. **Sesiones PHP**: Sesiones del servidor para redundancia

3. **Verificación de estado**: Validación de tokens en cada solicitud

### Mejores prácticas

1. **Secret fuerte**: Usa un secret de al menos 32 caracteres
2. **HTTPS en producción**: Asegura que `secure` sea true
3. **Rotación de tokens**: Los tokens se renuevan regularmente
4. **Validación**: Siempre valida credenciales en el servidor

## ➕ Crear un proveedor personalizado

```php
namespace Auth\Providers;

class Custom implements Provider {
    public function authorize(array $credentials): ?array {
        // Tu lógica de autenticación
        return [
            'id' => '123',
            'email' => 'user@example.com',
            'name' => 'Usuario',
            'provider' => 'custom'
        ];
    }

    public function handleCallback(): void {
        // Manejar callback si es OAuth
    }

    public function getName(): string {
        return 'Custom Provider';
    }

    public function getType(): string {
        return 'oauth'; // o 'credentials'
    }

    public function getConfig(): array {
        return [];
    }
}
```

Luego registrarlo:

```php
$Auth->registerProvider('custom', new Custom());
```

## 🐛 Troubleshooting

### El token no se persiste en cookies
- Verifica que `session_start()` se llame antes de usar sesiones
- Comprueba que los headers se envíen antes de cualquier output

### Error: "No autenticado"
- Verifica que el token sea válido
- Comprueba que no haya expirado
- Revisa que el secret sea el mismo en todas partes

### Google OAuth no funciona
- Verifica `AUTH_GOOGLE_ID` y `AUTH_GOOGLE_SECRET`
- Comprueba que el `redirectUri` sea exacto
- Asegúrate de que esté configurado en Google Cloud Console

## 📚 API Reference

### Auth

```php
// Iniciar sesión
$session = $Auth->signIn('google', $credentials);

// Cerrar sesión
$Auth->signOut();

// Obtener sesión actual
$session = $Auth->getSession();

// Obtener usuario actual
$user = $Auth->getUser();

// Verificar si está autenticado
$isAuth = $Auth->isAuthenticated();

// Obtener proveedores
$providers = $Auth->getProviders();

// Obtener SessionManager
$sessionManager = $Auth->getSessionManager();

// Obtener TokenManager
$tokenManager = $Auth->getTokenManager();
```

### SessionManager

```php
// Crear sesión
$sessionManager->create($user);

// Obtener sesión
$session = $sessionManager->get();

// Obtener usuario
$user = $sessionManager->getUser();

// Obtener token
$token = $sessionManager->getToken();

// Actualizar sesión
$sessionManager->update($data);

// Destruir sesión
$sessionManager->destroy();

// Verificar si está activa
$isActive = $sessionManager->isActive();
```

### TokenManager

```php
// Generar token
$token = $tokenManager->generate($payload);

// Verificar token
$payload = $tokenManager->verify($token);

// Obtener token de request
$token = $tokenManager->getTokenFromRequest();

// Establecer cookie
$tokenManager->setTokenCookie($token);

// Eliminar cookie
$tokenManager->removeTokenCookie();

// Refrescar token
$newToken = $tokenManager->refresh($payload);
```

### AuthMiddleware

```php
// Requerir autenticación
$auth->require();

// Con permisos específicos
$auth->requireWithPermission('edit');

// Con cualquiera de varios permisos
$auth->requireWithAnyPermission(['edit', 'delete']);

// Con todos los permisos
$auth->requireWithAllPermissions(['read', 'write', 'delete']);

// Verificar autenticación
$isAuth = $auth->check();
```

## 💡 Ejemplos completos

### Endpoint protegido

```php
<?php

use Auth\Auth;
use Auth\AuthMiddleware;

$Auth = require __DIR__ . '/../auth.config.php';
$sessionManager = $Auth->getSessionManager();

// Crear middleware
$auth = new AuthMiddleware($sessionManager, ['isApi' => true]);

// Requerir autenticación
$auth->require();

// Obtener usuario
$user = $sessionManager->getUser();

// Procesar solicitud
header('Content-Type: application/json');
echo json_encode([
    'message' => 'Hola ' . $user['name'],
    'user' => $user
]);
```

### Validación de permisos

```php
<?php

use Auth\Auth;
use Auth\AuthMiddleware;

$Auth = require __DIR__ . '/../auth.config.php';
$sessionManager = $Auth->getSessionManager();

$auth = new AuthMiddleware($sessionManager, ['isApi' => true]);

// Requerir múltiples permisos
$auth->requireWithAllPermissions(['read', 'write']);

// Procesar solicitud...
```

## 🔑 Helpers globales

```php
use function Auth\useSession;       // Obtener sesión
use function Auth\useUser;          // Obtener usuario
use function Auth\useToken;         // Obtener token
use function Auth\isAuthenticated;  // Verificar autenticación
use function Auth\requireAuth;      // Requerir autenticación
use function Auth\redirectToSignIn; // Redirigir a login
use function Auth\getAuthStatus;    // Estado de auth como JSON
```

---

**¡Documentación de Auth completa! 📚**

Para más detalles sobre ejemplos prácticos, consulta `EJEMPLOS_PRACTICOS.md`
Para diagramas de arquitectura, consulta `ARQUITECTURA.md`
