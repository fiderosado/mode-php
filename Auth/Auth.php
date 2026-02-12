<?php

namespace Auth;

use Auth\Callbacks;
use Auth\Providers\Provider;
use Auth\SessionManager;
use Auth\TokenManager;
use Exception;

/**
 * Auth - Sistema de autenticación profesional para PHP
 * Maneja múltiples proveedores, JWT, cookies y sesiones
 */
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
        }
    }

    public function registerProvider(string $name, Provider $provider): self
    {
        $this->providers[$name] = $provider;
        return $this;
    }

    public function registerCallbacks(array $callbacks): void
    {
        $this->callbacks = array_merge($this->callbacks, $callbacks);
    }

    public function registerCallback(string $name, callable $callback): self
    {
        $this->callbacks[$name] = $callback;
        return $this;
    }

    public function registerEvents(array $events): void
    {
        $this->events = array_merge($this->events, $events);
    }

    public function registerEvent(string $name, callable $event): self
    {
        $this->events[$name] = $event;
        return $this;
    }

    protected function executeCallback(string $name, ...$args): mixed
    {
        if (isset($this->callbacks[$name])) {
            return call_user_func($this->callbacks[$name], ...$args);
        }
        return null;
    }

    protected function fireEvent(string $name, ...$args): void
    {
        if (isset($this->events[$name])) {
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
        $this->fireEvent('signin');

        if (!isset($this->providers[$provider])) {
            throw new Exception("Proveedor '$provider' no registrado");
        }

        try {

            $user = $this->providers[$provider]->authorize($credentials);

            if (!$user) {
                $this->fireEvent('signinError', ['provider' => $provider]);
                throw new Exception("No se pudo autenticar con el proveedor : $provider");
            }

            $signInResult = $this->executeCallback('signIn', $user, $provider);
            if ($signInResult === false) {
                throw new Exception("Callback signIn rechazó el inicio de sesión");
            }

            $user = $this->executeCallback('jwt', $user, $provider) ?? $user;
            $session = $this->sessionManager->create($user);
            $session = $this->executeCallback('session', $session, $user) ?? $session;

            $this->fireEvent('signInSuccess', ['user' => $user, 'provider' => $provider]);

            return $session;
        } catch (Exception $e) {
            $this->fireEvent('signInError', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function signOut(): void
    {
        $this->fireEvent('signout');
        $this->sessionManager->destroy();
    }

    public function getSession(): ?array
    {
        $session = $this->sessionManager->get();

        if ($session) {
            return $this->executeCallback('session', $session, $session['user']) ?? $session;
        }

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
