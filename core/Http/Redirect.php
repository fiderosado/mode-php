<?php
namespace Core\Http;
class Redirect
{
    private string $url;
    private int $statusCode;
    private array $withData = [];
    /**
     * Constructor privado para forzar uso de métodos estáticos
     */
    private function __construct(string $url, int $statusCode = 302)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
    }
    /**
     * Crear redirección a una URL
     */
    public static function to(string $url, int $statusCode = 302): self
    {
        return new self($url, $statusCode);
    }
    /**
     * Redirigir a la página anterior (referer)
     */
    public static function back(int $statusCode = 302): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new self($referer, $statusCode);
    }
    /**
     * Redirigir a la ruta actual (reload)
     */
    public static function reload(int $statusCode = 302): self
    {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        return new self($currentUrl, $statusCode);
    }
    /**
     * Agregar datos flash a la sesión
     * Útil para mensajes de éxito/error
     * 
     * @param string $key Clave del mensaje flash
     * @param mixed $value Valor del mensaje
     */
    public function with(string $key, $value): self
    {
        $this->withData[$key] = $value;
        return $this;
    }
    /**
     * Agregar múltiples datos flash
     */
    public function withData(array $data): self
    {
        $this->withData = array_merge($this->withData, $data);
        return $this;
    }
    /**
     * Agregar mensaje de éxito
     */
    public function Success(string $message): self
    {
        return $this->with('success', $message);
    }
    /**
     * Agregar mensaje de error
     */
    public function Error(string $message): self
    {
        return $this->with('error', $message);
    }
    /**
     * Agregar errores de validación
     */
    public function withErrors(array $errors): self
    {
        return $this->with('errors', $errors);
    }
    /**
     * Ejecutar la redirección
     */
    public function execute(): void
    {
        // Guardar datos flash en la sesión si existen
        if (!empty($this->withData)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            foreach ($this->withData as $key => $value) {
                $_SESSION['_flash'][$key] = $value;
            }
        }
        // Realizar la redirección
        http_response_code($this->statusCode);
        header("Location: {$this->url}");
        exit;
    }
    /**
     * Obtener la URL de redirección (sin ejecutar)
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    /**
     * Obtener el código de estado
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
