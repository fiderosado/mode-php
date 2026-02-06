<?php
namespace Core\Http;

class Action {
    private static array $actions = [];
    private static ?string $currentPath = null;

     /**
     * Establecer la ruta actual donde se están registrando las acciones
     * Llamado desde Router.php antes de cargar actions.php
     */
    public static function setCurrentPath(string $path): void
    {
        self::$currentPath = $path;
    }
   /**
     * Obtener la ruta actual
     */
    public static function getCurrentPath(): ?string
    {
        return self::$currentPath;
    }

    /**
     * Definir una Server Action
     * Se llama desde actions.php
     * 
     * @param string $name Nombre de la acción
     * @param callable $handler Función que ejecuta la acción
     */
     public static function define(string $name, callable $handler): void
    {
        if (self::$currentPath === null) {
            throw new \Exception('Current path not set. Call setCurrentPath() first.');
        }

        self::$actions[$name] = [
            'handler' => $handler,
            'path' => self::$currentPath, // Guardar el scope/path permitido
        ];
    }

    /**
     * Verificar si una acción existe
     */
    public static function has(string $name): bool
    {
        return isset(self::$actions[$name]);
    }
    
    /**
     * Ejecutar una Server Action
     * 
     * @param string $name Nombre de la acción
     * @param array $data Datos del formulario/request
     * @param array $params Parámetros de la ruta (ej: ['slug' => '123'])
     * @param string $currentPath Ruta actual desde donde se llama
     * @throws \Exception Si la acción no existe o no se puede ejecutar desde esta ruta
     */
    public static function execute(string $name, array $data, array $params, string $currentPath)
    {
        if (!self::has($name)) {
            throw new \Exception("Action '{$name}' not found");
        }

        $action = self::$actions[$name];

        // VERIFICAR SCOPE: La acción solo se puede ejecutar desde su ruta registrada
        if ($action['path'] !== $currentPath) {
            throw new \Exception(
                "Action '{$name}' not allowed from this path. " .
                "Expected: {$action['path']}, Got: {$currentPath}"
            );
        }

        // Ejecutar el handler
        return $action['handler']($data, $params);
    }

    public static function url(string $name): string {
        return '?__action=' . urlencode($name);
    }

     /**
     * Limpiar todas las acciones registradas
     * Útil para testing
     */
    public static function clear(): void
    {
        self::$actions = [];
        self::$currentPath = null;
    }

    /**
     * Obtener todas las acciones registradas
     * Útil para debugging
     */
    public static function all(): array
    {
        return self::$actions;
    }
}