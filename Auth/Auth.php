<?php

namespace Auth;

use Auth\Callbacks;
use Auth\Providers\Provider;
use Auth\SessionManager;
use Auth\TokenManager;
use Exception;

class Auth
{
    protected array $providers = [];
    protected array $callbacks = [];
    protected SessionManager $sessionManager;
    protected TokenManager $tokenManager;
    protected array $config;
    protected array $events = [];

    public function __construct(array $config = [])
    {
        error_log("Auth: Inicializando sistema de autenticación");
        
        $this->validateConfig($config);

        $this->config = array_merge([
            'secret' => $_ENV['AUTH_SECRET'] ?? null,
            'session' => [
                'maxAge' => 86400,
                'updateAge' => 3600,
            ],
            'jwt' => [
                'secret' => $_ENV['AUTH_SECRET'] ?? null,
                'maxAge' => 86400,
            ],
            'pages' => [
                'signIn' => '/auth/signin',
                'signOut' => '/auth/signout',
                'error' => '/auth/error',
                'verifyRequest' => '/auth/verify-request',
                'newUser' => '/auth/new-user'
            ],
            'providers' => [],
            'callbacks' => [],
            'events' => [],
        ], $config);

        $this->tokenManager = new TokenManager(
            $this->config['secret'] ?? 'your-secret-key-min-32-chars',
            $this->config['jwt']['maxAge']
        );

        $this->sessionManager = new SessionManager(
            $this->tokenManager,
            $this->config['session']
        );

        $this->registerProviders($this->config['providers']);
        $this->registerCallbacks($this->config['callbacks']);
        $this->registerEvents($this->config['events']);

        global $Auth, $sessionManager;
        $Auth = $this;
        $sessionManager = $this->sessionManager;

        error_log("Auth: Sistema inicializado correctamente");
    }

    private function validateConfig(array $config): void
    {
        if (empty($config['secret'])) {
            throw new Exception("AUTH_SECRET debe ser definido");
        }
    }

    public function registerProviders(array $providers): void
    {
        foreach ($providers as $name => $provider) {
            $this->providers[$name] = $provider;
            error_log("Auth: Provider registrado: {$name}");
        }
    }

    public function registerProvider(string $name, Provider $provider): self
    {
        $this->providers[$name] = $provider;
        error_log("Auth: Provider registrado: {$name}");
        return $this;
    }

    public function registerCallbacks(array $callbacks): void
    {
        $this->callbacks = array_merge($this->callbacks, $callbacks);
        error_log("Auth: " . count($callbacks) . " callbacks registrados");
    }

    public function registerCallback(string $name, callable $callback): self
    {
        $this->callbacks[$name] = $callback;
        error_log("Auth: Callback registrado: {$name}");
        return $this;
    }

    public function registerEvents(array $events): void
    {
        $this->events = array_merge($this->events, $events);
        error_log("Auth: " . count($events) . " eventos registrados");
    }

    public function registerEvent(string $name, callable $event): self
    {
        $this->events[$name] = $event;
        error_log("Auth: Evento registrado: {$name}");
        return $this;
    }

    protected function executeCallback(string $name, ...$args): mixed
    {
        if (isset($this->callbacks[$name])) {
            error_log("Auth: Ejecutando callback '{$name}'");
            $result = call_user_func($this->callbacks[$name], ...$args);
            error_log("Auth: Callback '{$name}' ejecutado - Resultado: " . json_encode($result));
            return $result;
        }
        error_log("Auth: Callback '{$name}' no existe");
        return null;
    }

    protected function fireEvent(string $name, ...$args): void
    {
        if (isset($this->events[$name])) {
            error_log("Auth: Disparando evento '{$name}'");
            call_user_func($this->events[$name], ...$args);
        }
    }

    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->providers as $name => $provider) {
            $providers[$name] = [
                'id' => $name,
                'name' => $provider->getName() ?? ucfirst($name),
                'type' => $provider->getType() ?? 'oauth'
            ];
        }
        return $providers;
    }

    public function getProvider(string $name): ?Provider
    {
        return $this->providers[$name] ?? null;
    }

    public function signIn(string $provider, array $credentials = []): array
    {
        error_log("=== Auth->signIn() INICIADO ===");
        error_log("Auth->signIn: Provider: {$provider}");
        error_log("Auth->signIn: Credentials: " . json_encode($credentials));
        
        $this->fireEvent('signin', ['provider' => $provider]);

        if (!isset($this->providers[$provider])) {
            error_log("Auth->signIn: ERROR - Proveedor '{$provider}' no registrado");
            throw new Exception("Proveedor '{$provider}' no registrado");
        }

        try {
            $providerInstance = $this->providers[$provider];
            error_log("Auth->signIn: Llamando a {$provider}->authorize()");
            
            $user = $providerInstance->authorize($credentials);

            if (!$user) {
                error_log("Auth->signIn: ERROR - authorize() retornó null");
                $this->fireEvent('signInError', ['provider' => $provider, 'error' => 'Authorization failed']);
                throw new Exception("No se pudo autenticar con el proveedor: $provider");
            }

            error_log("Auth->signIn: User data recibido de provider: " . json_encode($user));

            // Callback signIn
            error_log("Auth->signIn: Ejecutando callback 'signIn'");
            $signInResult = $this->executeCallback('signIn', $user, $provider);
            if ($signInResult === false) {
                error_log("Auth->signIn: ERROR - Callback signIn rechazó el inicio de sesión");
                throw new Exception("Callback signIn rechazó el inicio de sesión");
            }

            // Callback jwt
            error_log("Auth->signIn: Ejecutando callback 'jwt'");
            $jwtPayload = $this->executeCallback('jwt', [], $user, $provider, [], false);
            
            // Combinar datos originales del user con el payload del jwt
            $finalUser = array_merge($user, $jwtPayload ?? []);
            
            error_log("Auth->signIn: Final user data (después de jwt callback): " . json_encode($finalUser));

            // Crear sesión
            error_log("Auth->signIn: Creando sesión");
            $session = $this->sessionManager->create($finalUser);
            
            error_log("Auth->signIn: Sesión creada: " . json_encode($session));

            // Callback session
            error_log("Auth->signIn: Ejecutando callback 'session'");
            $session = $this->executeCallback('session', $session, $finalUser) ?? $session;

            error_log("Auth->signIn: Sesión final (después de session callback): " . json_encode($session));

            $this->fireEvent('signInSuccess', ['user' => $finalUser, 'provider' => $provider]);

            error_log("=== Auth->signIn() COMPLETADO EXITOSAMENTE ===");
            
            return $session;
            
        } catch (Exception $e) {
            error_log("=== Auth->signIn() ERROR ===");
            error_log("Auth->signIn: Exception: " . $e->getMessage());
            error_log("Auth->signIn: Stack trace: " . $e->getTraceAsString());
            
            $this->fireEvent('signInError', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function signOut(): void
    {
        error_log("Auth->signOut: Cerrando sesión");
        $this->fireEvent('signout');
        $this->sessionManager->destroy();
        error_log("Auth->signOut: Sesión cerrada");
    }

    public function getSession(): ?array
    {
        $session = $this->sessionManager->get();

        if ($session) {
            error_log("Auth->getSession: Sesión encontrada");
            return $this->executeCallback('session', $session, $session['user']) ?? $session;
        }

        error_log("Auth->getSession: No hay sesión activa");
        return null;
    }

    public function getUser(): ?array
    {
        return $this->sessionManager->getUser();
    }

    public function isAuthenticated(): bool
    {
        return $this->sessionManager->isActive();
    }

    public function updateSession(array $data): array
    {
        error_log("Auth->updateSession: Actualizando sesión con: " . json_encode($data));
        return $this->sessionManager->update($data);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    public function handleRoute(string $action, array $params = []): void
    {
        header('Content-Type: application/json');

        try {
            match ($action) {
                'signin' => $this->handleSignIn(),
                'signout' => $this->handleSignOut(),
                'callback' => $this->handleCallback($params['provider'] ?? ''),
                'session' => $this->handleSession(),
                'providers' => $this->handleProviders(),
                'csrf' => $this->handleCsrf(),
                'error' => $this->handleError(),
                default => throw new Exception("Acción no válida: $action")
            };
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'status' => 'error'
            ]);
        }
    }

    private function handleSignIn(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            throw new Exception("Método no permitido");
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $provider = $data['provider'] ?? null;
        $credentials = $data['credentials'] ?? [];

        if (!$provider) {
            throw new Exception("Provider requerido");
        }

        $session = $this->signIn($provider, $credentials);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'session' => $session
        ]);
    }

    private function handleSignOut(): void
    {
        $this->signOut();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Sesión cerrada'
        ]);
    }

    private function handleCallback(string $provider): void
    {
        if (!$provider) {
            throw new Exception("Provider requerido en callback");
        }

        $providerInstance = $this->getProvider($provider);
        if (!$providerInstance) {
            throw new Exception("Provider no encontrado: $provider");
        }

        $providerInstance->handleCallback();
    }

    private function handleSession(): void
    {
        $session = $this->getSession();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'session' => $session
        ]);
    }

    private function handleProviders(): void
    {
        $providers = $this->getProviders();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'providers' => $providers
        ]);
    }

    private function handleCsrf(): void
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'csrfToken' => $token
        ]);
    }

    private function handleError(): void
    {
        $error = $_GET['error'] ?? 'unknown';

        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'error' => Callbacks::error($error)
        ]);
    }
}
