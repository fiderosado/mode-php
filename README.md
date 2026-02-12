# 🔐 Auth para PHP - Sistema de Autenticación Profesional

> Un sistema de autenticación moderno para PHP con JWT, Cookies y Sesiones.

## 🎯 Características

- ✅ **JWT + Cookies + Sesiones** - Triple seguridad
- ✅ **Multiple Providers** - Google OAuth, Credentials, Extensible
- ✅ **Callbacks Personalizables** - signIn, jwt, session, redirect
- ✅ **Sistema de Eventos** - Logging y tracking
- ✅ **Middleware de Rutas** - Protección simple
- ✅ **API REST** - 5 endpoints listos para usar
- ✅ **Helpers Globales** - useSession(), useUser(), etc.
- ✅ **Documentación Completa** - 2000+ líneas

## 📦 Instalación

### 1. Requisitos
- PHP 8.0+
- Composer

### 2. Instalar dependencias

```bash
composer require firebase/php-jwt
```

### 3. Variables de entorno

Crea archivo `.env` en la raíz:

```env
AUTH_SECRET=tu-secret-seguro-minimo-32-caracteres
AUTH_GOOGLE_ID=your-google-client-id
AUTH_GOOGLE_SECRET=your-google-client-secret
APP_URL=http://localhost:3000
```

### 4. Copiar configuración

El archivo `auth.config.php` ya está en la raíz del proyecto.

## 🚀 Uso rápido

### Proteger una página

```php
<?php
$Auth = require __DIR__ . '/auth.config.php';
$auth = new AuthMiddleware($Auth->getSessionManager());
$auth->require(); // Redirige a login si no autenticado

$user = $Auth->getUser();
echo "Bienvenido " . $user['name'];
?>
```

### Proteger una API

```php
<?php
$auth = new AuthMiddleware(
    $Auth->getSessionManager(), 
    ['isApi' => true]
);
$auth->require(); // Retorna JSON 401 si no autenticado

header('Content-Type: application/json');
echo json_encode(['user' => $Auth->getUser()]);
?>
```

### Usar helpers

```php
<?php
use function Auth\isAuthenticated;
use function Auth\useUser;

if (isAuthenticated()) {
    echo "Usuario: " . useUser()['email'];
}
?>
```

## 📚 Documentación

### Guías principales

- **[AUTH_DOCUMENTATION.md](./AUTH_DOCUMENTATION.md)** - Documentación técnica completa
- **[QUICKSTART.md](./QUICKSTART.md)** - Inicio rápido con ejemplos
- **[ARQUITECTURA.md](./ARQUITECTURA.md)** - Diagramas y flujos
- **[EJEMPLOS_PRACTICOS.md](./EJEMPLOS_PRACTICOS.md)** - 10 ejemplos listos para usar
- **[CHECKLIST.md](./CHECKLIST.md)** - Lista de implementación

## 🔗 API Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/auth/signin` | Iniciar sesión |
| `POST` | `/api/auth/signout` | Cerrar sesión |
| `GET` | `/api/auth/session` | Obtener sesión |
| `GET` | `/api/auth/providers` | Listar proveedores |
| `GET` | `/api/auth/callback/google` | Google OAuth callback |

## 💻 Ejemplos

### Signin con email/password

```javascript
const response = await fetch('/api/auth/signin', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        provider: 'credentials',
        credentials: { 
            email: 'user@example.com',
            password: 'secret'
        }
    })
});
```

### Obtener sesión

```javascript
const response = await fetch('/api/auth/session');
const { session } = await response.json();
```

### Cerrar sesión

```javascript
await fetch('/api/auth/signout', { method: 'POST' });
```

## 🏗️ Estructura

```
Auth/
├── Auth.php              # Clase principal
├── SessionManager.php       # Gestión de sesiones
├── TokenManager.php         # Gestión de JWT
├── Callbacks.php            # Sistema de callbacks
├── Helpers.php              # Funciones auxiliares
├── AuthMiddleware.php    # Middleware
└── Providers/
    ├── Provider.php         # Interface
    ├── Google.php           # Google OAuth
    └── Credentials.php      # Email + Password

auth.config.php           # Configuración centralizada
```

## 🔐 Seguridad

- JWT firmados con HS256
- Cookies con HttpOnly, Secure, SameSite=Lax
- Validación de estado (CSRF protection)
- Hashing de contraseñas con PASSWORD_DEFAULT
- Expiración automática de tokens

## 🛠️ Configuración

En `auth.config.php` puedes personalizar:

```php
'callbacks' => [
    'signIn' => function(array $user) {
        // Validación personalizada
        return true; // o false para denegar
    },
    'jwt' => function(array $token) {
        // Personalizar JWT
        return $token;
    }
],
'events' => [
    'signin' => function($message) {
        // Logging
    }
]
```

## 📋 Helpers disponibles

```php
useSession()              // Obtener sesión actual
useUser()                 // Obtener usuario
useToken()                // Obtener JWT token
isAuthenticated()         // ¿Está autenticado?
requireAuth()             // Requerir autenticación
redirectToSignIn()        // Redirigir a login
getAuthStatus()           // Estado como JSON
```

## 🎯 Crear proveedor personalizado

```php
class CustomProvider implements Provider {
    public function authorize(array $credentials): ?array {
        // Tu lógica
        return ['id' => '123', 'email' => 'user@example.com'];
    }
    
    public function handleCallback(): void {}
    public function getName(): string { return 'Custom'; }
    public function getType(): string { return 'oauth'; }
    public function getConfig(): array { return []; }
}

// Registrarlo:
$Auth->registerProvider('custom', new CustomProvider());
```

## 📊 Flujo de autenticación

```
1. Usuario envía credenciales a /api/auth/signin
2. Proveedor valida credenciales
3. Callback 'signIn' valida el login
4. Se genera JWT token
5. Se crea sesión en $_SESSION
6. Token se almacena en cookie HttpOnly
7. Se retorna sesión al cliente
```

## 🐛 Troubleshooting

### Token no persiste
Verificar que `session_start()` se llama antes

### Google OAuth falla
Verificar CLIENT_ID, CLIENT_SECRET y redirect URI

### Token expirado rápido
Aumentar `maxAge` en configuración

Ver [CHECKLIST.md](./CHECKLIST.md) para más tips.

## 📈 Próximos pasos

1. ✅ Instalar dependencias: `composer require firebase/php-jwt`
2. ✅ Crear `.env` con variables
3. ✅ Integrar con tu BD
4. ✅ Configurar Google OAuth
5. ✅ Personalizar callbacks
6. ✅ Proteger tus rutas

## 🎓 Aprende más

- Ver [AUTH_DOCUMENTATION.md](./AUTH_DOCUMENTATION.md) para documentación técnica
- Ver [EJEMPLOS_PRACTICOS.md](./EJEMPLOS_PRACTICOS.md) para 10 ejemplos completos
- Ver [ARQUITECTURA.md](./ARQUITECTURA.md) para diagramas detallados

## 📝 Licencia

MIT - Úsalo libremente en tus proyectos

## 🤝 Soporte

Si necesitas ayuda:
1. Revisa la documentación en `AUTH_DOCUMENTATION.md`
2. Consulta los ejemplos en `EJEMPLOS_PRACTICOS.md`
3. Revisa el checklist en `CHECKLIST.md`

---

**¡Tu sistema Auth para PHP está 100% operacional!** 🚀

Instalación: `composer require firebase/php-jwt`

Documentación: Ver archivos `.md` en la raíz del proyecto
