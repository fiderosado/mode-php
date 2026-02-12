# 🚀 Auth - Quick Start Guide

## ⚡ Inicio Rápido

### 1. Instalar dependencias

```bash
composer require firebase/php-jwt
```

### 2. Variables de entorno (.env)

```env
AUTH_SECRET=tu-secret-muy-seguro-de-32-caracteres-o-mas
AUTH_GOOGLE_ID=123456.apps.googleusercontent.com
AUTH_GOOGLE_SECRET=GOCSPX-xxxxxxxxx
APP_URL=http://localhost:3000
```

### 3. Cargar la configuración

En tu `bootstrap.php` o archivo principal:

```php
require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Inicializar Auth
$Auth = require __DIR__ . '/auth.config.php';
```

### 4. Usar en tus páginas

#### En una página protegida:

```php
<?php
use Auth\AuthMiddleware;

$Auth = require __DIR__ . '/../auth.config.php';
$sessionManager = $Auth->getSessionManager();
$auth = new AuthMiddleware($sessionManager);

// Esto redirige a /auth/signin si no está autenticado
$auth->require();

$user = $sessionManager->getUser();
?>

<h1>Bienvenido, <?php echo $user['name']; ?></h1>
```

#### En una API protegida:

```php
<?php
use Auth\AuthMiddleware;

$Auth = require __DIR__ . '/../auth.config.php';
$sessionManager = $Auth->getSessionManager();
$auth = new AuthMiddleware($sessionManager, ['isApi' => true]);

// Retorna JSON con error 401 si no está autenticado
$auth->require();

$user = $sessionManager->getUser();

header('Content-Type: application/json');
echo json_encode(['user' => $user]);
```

## 🔗 Endpoints disponibles

```
POST   /api/auth/signin      - Iniciar sesión
POST   /api/auth/signout     - Cerrar sesión
GET    /api/auth/session     - Obtener sesión actual
GET    /api/auth/providers   - Listar proveedores
GET    /api/auth/callback/google - Callback de Google OAuth
```

## 💻 Frontend - Ejemplos

### Iniciar sesión con email/password

```javascript
async function signIn(email, password) {
    const response = await fetch('/api/auth/signin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            provider: 'credentials',
            credentials: { email, password }
        })
    });

    const data = await response.json();
    if (data.status === 'success') {
        window.location.href = '/dashboard';
    } else {
        alert('Error: ' + data.error);
    }
}
```

### Obtener usuario actual

```javascript
async function getCurrentUser() {
    const response = await fetch('/api/auth/session');
    const data = await response.json();
    
    if (data.session) {
        console.log('Usuario:', data.session.user);
        return data.session.user;
    }
    return null;
}
```

### Cerrar sesión

```javascript
async function signOut() {
    await fetch('/api/auth/signout', { method: 'POST' });
    window.location.href = '/';
}
```

### Google OAuth (Redireccionamiento)

```javascript
async function signInWithGoogle() {
    // Obtener URL de autorización desde el proveedor
    const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' + 
        new URLSearchParams({
            client_id: 'tu-google-client-id',
            redirect_uri: window.location.origin + '/api/auth/callback/google',
            response_type: 'code',
            scope: 'openid email profile'
        }).toString();
    
    window.location.href = authUrl;
}
```

## 🛡️ Proteger rutas

### HTML/PHP

```php
<?php
use function Auth\isAuthenticated;

if (!isAuthenticated()) {
    header('Location: /auth/signin');
    exit;
}
?>

<!-- Tu contenido aquí -->
```

### API/JSON

```php
<?php
header('Content-Type: application/json');

use function Auth\isAuthenticated;

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Tu endpoint aquí
```

## 📋 Estructura de sesión

```php
$session = useSession();

// Estructura:
// $session['user'] => Datos del usuario
// $session['token'] => JWT Token
// $session['created_at'] => Timestamp de creación
// $session['expires_at'] => Timestamp de expiración
```

## 🔑 Principales clases

### Auth
```php
$Auth->signIn(string $provider, array $credentials): array
$Auth->signOut(): void
$Auth->getSession(): ?array
$Auth->getUser(): ?array
$Auth->isAuthenticated(): bool
$Auth->getProviders(): array
```

### SessionManager
```php
$sessionManager->create(array $user): array
$sessionManager->get(): ?array
$sessionManager->getUser(): ?array
$sessionManager->getToken(): ?string
$sessionManager->update(array $data): array
$sessionManager->destroy(): void
$sessionManager->isActive(): bool
```

### TokenManager
```php
$tokenManager->generate(array $payload): string
$tokenManager->verify(string $token): ?object
$tokenManager->getTokenFromRequest(): ?string
$tokenManager->setTokenCookie(string $token): void
$tokenManager->removeTokenCookie(): void
$tokenManager->refresh(object $payload): string
```

### AuthMiddleware
```php
$auth->require(): void
$auth->check(): bool
$auth->requireWithPermission(string $permission): void
$auth->requireWithAnyPermission(array $permissions): void
$auth->requireWithAllPermissions(array $permissions): void
```

## 🎯 Helpers globales

```php
// Obtener sesión
useSession(): ?array

// Obtener usuario
useUser(): ?array

// Obtener token
useToken(): ?string

// Verificar autenticación
isAuthenticated(): bool

// Requerir autenticación (lanza excepción si falla)
requireAuth(): array

// Redirigir a login si no está autenticado
redirectToSignIn(): void

// Obtener estado como JSON
getAuthStatus(): array
```

## 🔗 Callbacks personalizados

Puedes personalizar el comportamiento en `auth.config.php`:

```php
'callbacks' => [
    'signIn' => function(array $user, string $provider = '') {
        // Valida si se permite el login
        // return false para denegar
        return true;
    },
    
    'jwt' => function(array $token, array $user = []) {
        // Personaliza el contenido del JWT
        return $token;
    },
    
    'session' => function(array $session, array $user = []) {
        // Personaliza lo que se retorna en la sesión
        return $session;
    }
]
```

## 📚 Ver documentación completa

```bash
cat AUTH_DOCUMENTATION.md
```

---

**¡Listo para usar! 🚀**

Para más detalles, consulta la documentación completa en `AUTH_DOCUMENTATION.md`.
