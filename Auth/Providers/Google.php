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
                'maxAge' => 600, // 10 minutos
                'path' => '/',
                'httpOnly' => true,
                'sameSite' => 'lax'
            ]);


        } else {

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



        return $authUrl;
    }

    private function exchangeCodeForToken(string $code): ?array
    {



        try {
            $payload = [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code'
            ];



            $response = Connect::post('https://oauth2.googleapis.com/token', $payload);



            return $response;
        } catch (Exception $e) {


            return null;
        }
    }

    private function getUserInfo(string $accessToken): ?array
    {


        try {
            $response = Connect::get('https://openidconnect.googleapis.com/v1/userinfo', [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ]
            ]);



            return $response;
        } catch (Exception $e) {


            return null;
        }
    }

    public function authorize(array $credentials): ?array
    {


        $code = $credentials['code'] ?? null;

        if (!$code) {

            return null;
        }

        // Intercambiar código por token
        $tokenData = $this->exchangeCodeForToken($code);

        if (!is_array($tokenData) || !isset($tokenData['success']['data']['access_token'])) {

            return null;
        }

        $accessToken = $tokenData['success']['data']['access_token'];


        // Obtener información del usuario
        $userInfo = $this->getUserInfo($accessToken);

        if (isset($userInfo['error'])) {

            return null;
        }

        $userData = $userInfo["success"]["data"] ?? null;

        if (!$userData) {

            return null;
        }



        return $userData;
    }

    public function handleCallback(): void
    {


        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {

            header('Location: /auth/error?error=' . urlencode($error));
            exit;
        }

        if (!$code) {

            header('Location: /auth/error?error=missing_code');
            exit;
        }


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
