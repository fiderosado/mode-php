<?php

namespace Auth\Providers;

use Core\Cookies\Cookie;
use Core\Http\Connect;
use Exception;

class Google implements Provider
{
    private array $config = [];
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $scope = 'openid email profile';

    public function __construct(array $config)
    {
        $this->clientId = $config['clientId'] ?? $_ENV['AUTH_GOOGLE_ID'] ?? '';
        $this->clientSecret = $config['clientSecret'] ?? $_ENV['AUTH_GOOGLE_SECRET'] ?? '';
        $this->redirectUri = $config['redirectUri'] ?? ($_ENV['APP_URL'] ?? '') . '/api/auth/callback/google';
        $this->config = $config;

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception("Google: clientId y clientSecret son requeridos");
        }

        error_log("Google Provider inicializado - ClientId: {$this->clientId}, RedirectUri: {$this->redirectUri}");
    }

    public function getAuthorizationUrl(string $state = ''): string
    {
        if (empty($state)) {
            $state = bin2hex(random_bytes(16));
            
            // Guardar state en sesión Y en cookie como backup
            $_SESSION['oauth_state'] = $state;
            
            // Usar la clase Cookie para establecer una cookie de backup del state
            $cookies = Cookie::response();
            $cookies->set('oauth_state_backup', $state, [
                'expires' => time() + 600, // 10 minutos
                'path' => '/',
                'httpOnly' => true,
                'sameSite' => 'Lax'
            ]);
            
            error_log("Google: State generado y guardado en sesión y cookie: {$state}");
        } else {
            error_log("Google: Usando state proporcionado: {$state}");
        }

        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);

        error_log("Google: URL de autorización generada: {$authUrl}");
        
        return $authUrl;
    }

    private function exchangeCodeForToken(string $code): ?array
    {
        error_log("Google: Iniciando intercambio de código por token");
        error_log("Google: Code: {$code}");
        
        try {
            $payload = [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code'
            ];

            error_log("Google: Payload para intercambio: " . json_encode($payload));

            $response = Connect::post('https://oauth2.googleapis.com/token', $payload);
            
            error_log("Google: Respuesta de token completa: " . json_encode($response));

            return $response;
        } catch (Exception $e) {
            error_log("Google: Error intercambiando código: " . $e->getMessage());
            error_log("Google: Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    private function getUserInfo(string $accessToken): ?array
    {
        error_log("Google: Obteniendo información del usuario con access token");
        
        try {
            $response = Connect::get('https://openidconnect.googleapis.com/v1/userinfo', [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ]
            ]);
            
            error_log("Google: Respuesta de userinfo completa: " . json_encode($response));
            
            return $response;
        } catch (Exception $e) {
            error_log("Google: Error obteniendo info del usuario: " . $e->getMessage());
            error_log("Google: Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    public function authorize(array $credentials): ?array
    {
        error_log("Google->authorize() iniciado con credentials: " . json_encode($credentials));
        
        $code = $credentials['code'] ?? null;

        if (!$code) {
            error_log("Google: No se proporcionó código de autorización");
            return null;
        }

        // Intercambiar código por token
        $tokenData = $this->exchangeCodeForToken($code);

        if (!is_array($tokenData) || !isset($tokenData['success']['data']['access_token'])) {
            error_log("Google: Error en intercambio de token - respuesta inválida");
            return null;
        }

        $accessToken = $tokenData['success']['data']['access_token'];
        error_log("Google: Access token obtenido exitosamente");

        // Obtener información del usuario
        $userInfo = $this->getUserInfo($accessToken);

        if (isset($userInfo['error'])) {
            error_log("Google: Error en info del usuario: " . json_encode($userInfo['error']));
            return null;
        }

        $userData = $userInfo["success"]["data"] ?? null;

        if (!$userData) {
            error_log("Google: No se pudo obtener datos del usuario");
            return null;
        }

        error_log("Google: Datos del usuario obtenidos exitosamente: " . json_encode($userData));

        return $userData;
    }

    public function handleCallback(): void
    {
        error_log("Google->handleCallback() NO DEBERÍA SER LLAMADO - usar Auth->signIn() desde el callback route");
        
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            error_log("Google: Error en callback de Google: {$error}");
            header('Location: /auth/error?error=' . urlencode($error));
            exit;
        }

        if (!$code) {
            error_log("Google: Callback sin código");
            header('Location: /auth/error?error=missing_code');
            exit;
        }

        error_log("Google: handleCallback() debería delegar a Auth->signIn() en lugar de procesar directamente");
        header('Location: /auth/error?error=deprecated_handler');
        exit;
    }

    public function getName(): string
    {
        return 'Google';
    }

    public function getType(): string
    {
        return 'oauth';
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
