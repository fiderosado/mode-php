<?php

namespace Auth\Providers;

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
            $_SESSION['oauth_state'] = $state;
        }

        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);
    }

    private function exchangeCodeForToken(string $code): ?array
    {
        try {
            $response = Connect::post(
                'https://oauth2.googleapis.com/token',
                [
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code'
                ]
            );
            return $response;
        } catch (Exception $e) {
            error_log("Google: Error intercambiando código: " . $e->getMessage());
            return null;
        }
    }

    private function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = Connect::get('https://openidconnect.googleapis.com/v1/userinfo',  [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                ]
            ]);
            return $response;
        } catch (Exception $e) {
            error_log("Google: Error obteniendo info del usuario: " . $e->getMessage());
            return null;
        }
    }

    private function makeRequest(string $method, string $url, array $data = [], array $headers = []): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Google API error: HTTP $httpCode");
        }

        return json_decode($response, true);
    }

    public function authorize(array $credentials): ?array
    {
        $code = $credentials['code'] ?? null;

        if (!$code) {
            return null;
        }

        $state = $credentials['state'] ?? null;

        if ($state && ($_SESSION['oauth_state'] ?? null) !== $state) {
            throw new Exception("Estado de OAuth inválido");
        }

        $tokenData = $this->exchangeCodeForToken($code);
        error_log("Google: Respuesta de token: " . json_encode($tokenData));

        if (
            !is_array($tokenData) ||
            !isset($tokenData['success']['data']['access_token'])
        ) {
            error_log("Google: Error en intercambio de token: " . json_encode($tokenData));
            return null;
        }

        $accessToken = $tokenData['success']['data']['access_token'] ?? null;

        if (!$accessToken) {
            error_log("Google: Access token no válido: " . json_encode($tokenData));
            return null;
        }

        $userInfo = $this->getUserInfo($accessToken);
        error_log("Google: Respuesta de info del usuario: " . json_encode($userInfo));

        /*
         {
            "success":{
                "data":{
                    "sub":"117204732513714275660",
                    "name":"Fidel Remedios Rosado",
                    "given_name":"Fidel",
                    "family_name":"Remedios Rosado",
                    "picture":"https:\/\/lh3.googleusercontent.com\/a\/ACg8ocJYLs9-zN_CUEGoZN72GzwmhoRYaJDpLsEIJ4U2iXOgtzniAhMc=s96-c","email":"fiderosado@gmail.com",
                    "email_verified":true
                }
            }
        }

         */

        if (isset($userInfo['error'])) {
            error_log("Google: Error en info del usuario: " . json_encode($userInfo));
            return null;
        }

        $userData = $userInfo["success"]["data"] ?? false;

        if (!$userData) {
            error_log("Google: Error en info del usuario: " . json_encode($userInfo));
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
            header('Location: /api/auth/error?error=' . urlencode($error));
            exit;
        }

        if (!$code) {
            header('Location: /api/auth/error?error=missing_code');
            exit;
        }

        try {
            $user = $this->authorize([
                'code' => $code,
                'state' => $state
            ]);

            if (!$user) {
                header('Location: /api/auth/error?error=auth_failed');
                exit;
            }

            global $Auth;
            if ($Auth) {
                $session = $Auth->signIn('google', ['code' => $code, 'state' => $state]);

                $callbackUrl = $_SESSION['callbackUrl'] ?? '/dashboard';
                unset($_SESSION['callbackUrl']);

                header('Location: ' . $callbackUrl);
                exit;
            }
        } catch (Exception $e) {
            header('Location: /api/auth/error?error=' . urlencode($e->getMessage()));
            exit;
        }
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
