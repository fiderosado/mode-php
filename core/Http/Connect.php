<?php
namespace Core\Http;
use Exception;
final class Connect
{
    private static ?self $instance = null;
    private array $config = [
        'base_url' => '',
        'token' => '',
        'timeout' => 30,
        'max_redirects' => 5,
        'follow' => false,
    ];
    private array $defaultHeaders = [];
    private function __construct()
    {
        // $this->config['base_url'] = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
        //$this->config['token'] = $_ENV['API_FULL_ACCESS_TOKEN'] ?? getenv('API_FULL_ACCESS_TOKEN') ?? '';
        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($this->config['token']) {
            $this->defaultHeaders['Authorization'] = 'Bearer ' . $this->config['token'];
        }
    }
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }
    public static function request(
        string $url,
        string $method = 'GET',
        array $payload = [],
        array $config = []
    ): array {
        $self = self::getInstance();
        $method = strtoupper($method);
        if (!empty($config['base_url'])) {
            $self->config['base_url'] = $config['base_url'];
        }
        if (!$url || !isset(self::methods()[$method])) {
            return self::error('Unsupported endpoint or method', $method, $url);
        }
        try {
            $response = $self->execute(
                $self->resolveUrl($url),
                $method,
                $payload,
                array_merge($self->defaultHeaders, $config['headers'] ?? []),
                $config
            );
            return $self->handleResponse($response, $method, $config);
        } catch (Exception $e) {
            return [
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }
    public static function get(string $url, array $config = []): array
    {
        return self::request($url, 'GET', [], $config);
    }
    public static function post(string $url, array $payload = [], array $config = []): array
    {
        return self::request($url, 'POST', $payload, $config);
    }
    public static function put(string $url, array $payload = [], array $config = []): array
    {
        return self::request($url, 'PUT', $payload, $config);
    }
    public static function patch(string $url, array $payload = [], array $config = []): array
    {
        return self::request($url, 'PATCH', $payload, $config);
    }
    public static function delete(string $url, array $config = []): array
    {
        return self::request($url, 'DELETE', [], $config);
    }
    private static function methods(): array
    {
        return ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'PATCH' => 1, 'DELETE' => 1];
    }
    private static function error(string $message, ?string $method = null, ?string $url = null): array
    {
        return [
            'error' => array_filter([
                'message' => $message,
                'method' => $method,
                'url' => $url
            ])
        ];
    }
    private function resolveUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        return rtrim($this->config['base_url'], '/') . '/' . ltrim($url, '/');
    }
    private function execute(
        string $url,
        string $method,
        array $payload,
        array $headers,
        array $config
    ): array {
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => $this->config['follow'],
            CURLOPT_MAXREDIRS => $this->config['max_redirects'],
            CURLOPT_TIMEOUT => $config['timeout'] ?? $this->config['timeout'],
            CURLOPT_HTTPHEADER => $this->headersToArray($headers),
            //CURLOPT_HEADER => true,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $length = strlen($header);
                $header = trim($header);
                if ($header === '' || str_starts_with($header, 'HTTP/')) {
                    return $length;
                }
                if (!str_contains($header, ':')) {
                    return $length;
                }
                [$key, $value] = explode(':', $header, 2);
                $responseHeaders[strtolower(trim($key))] = trim($value);
                return $length;
            }
        ]);
        if ($payload && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $body = curl_exec($ch);
        if ($body === false) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // capturar redirect detectado por cURL
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($redirectUrl) {
            $responseHeaders['location'] = $redirectUrl;
        }
        return [
            'body' => $body,
            'status' => $status,
            'headers' => $responseHeaders
        ];
        //return compact('body', 'status');
    }
    private function headersToArray(array $headers): array
    {
        return array_map(
            fn($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            $headers
        );
    }
    private function handleResponse(array $response, string $method, array $config): array
    {
        if ($response['status'] >= 300 && $response['status'] < 400) {
            $location = $response['headers']['location'] ?? null;
            return [
                'redirect' => [
                    'status' => $response['status'],
                    'location' => $location
                ]
            ];
        }
        if ($response['status'] === 204 && $method === 'DELETE') {
            return ['success' => ['message' => 'Eliminado...']];
        }
        $data = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::error('Invalid JSON response');
        }
        if ($response['status'] >= 200 && $response['status'] < 300) {
            return $this->success($data, $config);
        }
        return $this->fail($data, $response['status']);
    }
    private function success(array $data, array $config): array
    {
        $result = $data['data'] ?? $data;
        if ($config['firstValue'] ?? false) {
            $result = $result[0] ?? [];
        }
        if ($result === [] || $result === null) {
            return ['error' => ['message' => 'Empty response', 'status' => 200]];
        }
        return ['success' => ['data' => $result]];
    }
    private function fail(array $data, int $status): array
    {
        return [
            'error' => [
                'message' => $data['error']['message']
                    ?? $data['error']
                    ?? $data['message']
                    ?? 'Request error',
                'status' => $status,
                'data' => $data
            ]
        ];
    }
    public static function setToken(string $token): void
    {
        $self = self::getInstance();
        $self->config['token'] = $token;
        $self->defaultHeaders['Authorization'] = "Bearer {$token}";
    }
    public static function setBaseUrl(string $url): void
    {
        self::getInstance()->config['base_url'] = $url;
    }
}
