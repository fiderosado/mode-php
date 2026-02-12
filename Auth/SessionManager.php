<?php

namespace Auth;

class SessionManager
{
    private TokenManager $tokenManager;
    private array $config;

    public function __construct(TokenManager $tokenManager, array $config = [])
    {
        $this->tokenManager = $tokenManager;
        $this->config = array_merge([
            'session_name' => 'auth',
            'maxAge' => 86400,
            'updateAge' => 3600,
            'secure' => false,
            'httpOnly' => true,
            'sameSite' => 'Lax'
        ], $config);

        // Solo iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {

            // Configurar cookies de sesión ANTES de session_start()
            session_set_cookie_params([
                'lifetime' => $this->config['maxAge'],
                'path' => '/',
                'domain' => '',  // Dominio actual
                'secure' => $this->config['secure'],
                'httponly' => $this->config['httpOnly'],
                'samesite' => $this->config['sameSite']
            ]);

            // Establecer nombre de sesión
            session_name($this->config['session_name']);

            // Iniciar sesión
            session_start();

            error_log("SessionManager: Nueva sesión PHP iniciada");
            error_log("SessionManager: Session ID: " . session_id());
            error_log("SessionManager: Session name: " . session_name());
            error_log("SessionManager: Cookie params: " . json_encode(session_get_cookie_params()));
        } else {
            error_log("SessionManager: Sesión PHP ya iniciada");
            error_log("SessionManager: Session ID: " . session_id());
            error_log("SessionManager: Session name: " . session_name());
        }
    }

    public function create(array $user): array
    {
        error_log("SessionManager->create: Creando sesión para usuario");
        error_log("SessionManager->create: User data: " . json_encode($user));
        error_log("SessionManager->create: Current Session ID: " . session_id());

        // Normalizar user data para compatibilidad con diferentes providers
        $userId = $user['id'] ?? $user['sub'] ?? $user['email'];
        $userEmail = $user['email'] ?? '';
        $userName = $user['name'] ?? $user['given_name'] ?? '';

        error_log("SessionManager->create: userId: {$userId}, email: {$userEmail}, name: {$userName}");

        $tokenPayload = [
            'sub' => $userId,
            'email' => $userEmail,
            'name' => $userName,
        ];

        // Incluir campos adicionales del user
        foreach ($user as $key => $value) {
            if (!isset($tokenPayload[$key]) && !in_array($key, ['iat', 'exp', 'nbf'])) {
                $tokenPayload[$key] = $value;
            }
        }

        error_log("SessionManager->create: Token payload: " . json_encode($tokenPayload));

        $token = $this->tokenManager->generate($tokenPayload);
        error_log("SessionManager->create: Token generado");

        $session = [
            'user' => $user,
            'token' => $token,
            'created_at' => time(),
            'expires_at' => time() + $this->config['maxAge']
        ];

        $_SESSION[$this->config['session_name']] = $session;
        error_log("SessionManager->create: Sesión guardada en \$_SESSION['{$this->config['session_name']}']");
        error_log("SessionManager->create: Contenido de \$_SESSION: " . json_encode($_SESSION));

        $this->tokenManager->setTokenCookie($token);
        error_log("SessionManager->create: Cookie de token establecida");
        /* 
        $cookie_creada_name = $this->tokenManager->getCookieName();
        $cookie_creada = $this->tokenManager->getCreatedCookie();
        error_log("Cookies actuales [ name:" . $cookie_creada_name . " , value: " . $cookie_creada . "]"); */
        error_log("SessionManager->create: Sesión creada exitosamente");

        return $session;
    }

    public function get(): ?array
    {
        error_log("SessionManager->get: Obteniendo sesión");
        error_log("SessionManager->get: Session ID: " . session_id());
        error_log("SessionManager->get: Session name: " . session_name());
        error_log("SessionManager->get: \$_SESSION keys: " . json_encode(array_keys($_SESSION)));

        $session = $_SESSION[$this->config['session_name']] ?? null;

        if (!$session) {
            error_log("SessionManager->get: No hay sesión en \$_SESSION['{$this->config['session_name']}'], buscando token");

            $token = $this->tokenManager->getTokenFromRequest();
            if ($token) {
                error_log("SessionManager->get: Token encontrado en request/cookie");

                $payload = $this->tokenManager->verify($token);
                if ($payload) {
                    error_log("SessionManager->get: Token verificado exitosamente");

                    return [
                        'user' => (array)$payload,
                        'token' => $token,
                        'expires_at' => $payload->exp ?? null
                    ];
                } else {
                    error_log("SessionManager->get: Token inválido o expirado");
                }
            } else {
                error_log("SessionManager->get: No se encontró token");
            }

            return null;
        }

        error_log("SessionManager->get: Sesión encontrada en \$_SESSION");

        if (isset($session['token'])) {
            $payload = $this->tokenManager->verify($session['token']);
            if (!$payload) {
                error_log("SessionManager->get: Token de sesión inválido, destruyendo sesión");
                $this->destroy();
                return null;
            }
        }

        if ($session['expires_at'] < time()) {
            error_log("SessionManager->get: Sesión expirada, destruyendo");
            $this->destroy();
            return null;
        }

        error_log("SessionManager->get: Sesión válida retornada");

        return $session;
    }

    public function update(array $data): array
    {
        error_log("SessionManager->update: Actualizando sesión");

        $session = $this->get();

        if (!$session) {
            error_log("SessionManager->update: ERROR - No hay sesión activa");
            throw new \Exception("No hay sesión activa");
        }

        $session['user'] = array_merge($session['user'], $data);
        $session['token'] = $this->tokenManager->refresh((object)$session['user']);
        $session['updated_at'] = time();

        $_SESSION[$this->config['session_name']] = $session;

        $this->tokenManager->setTokenCookie($session['token']);

        error_log("SessionManager->update: Sesión actualizada");

        return $session;
    }

    public function destroy(): void
    {
        error_log("SessionManager->destroy: Destruyendo sesión");

        unset($_SESSION[$this->config['session_name']]);
        $this->tokenManager->removeTokenCookie();

        error_log("SessionManager->destroy: Sesión destruida");
    }

    public function isActive(): bool
    {
        $active = $this->get() !== null;
        error_log("SessionManager->isActive: " . ($active ? 'true' : 'false'));
        return $active;
    }

    public function getUser(): ?array
    {
        $session = $this->get();
        $user = $session['user'] ?? null;

        error_log("SessionManager->getUser: " . ($user ? json_encode($user) : 'null'));

        return $user;
    }

    public function getToken(): ?string
    {
        $session = $this->get();
        return $session['token'] ?? null;
    }
}
