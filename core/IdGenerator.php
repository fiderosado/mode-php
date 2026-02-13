<?php
namespace Core;

class IdGenerator {
    
    // Contadores y configuración estilo React
    protected static $globalClientIdCounter = 0;
    protected static $localIdCounter = 0;
    protected static $treeId = 0;
    protected static $identifierPrefix = '';
    protected static $usedIds = []; // Para verificación de unicidad
    
    /**
     * Configura el prefijo de identificador (como en React)
     */
    public static function setIdentifierPrefix($prefix) {
        self::$identifierPrefix = $prefix;
    }
    
    /**
     * Obtiene el prefijo de identificador
     */
    public static function getIdentifierPrefix() {
        return self::$identifierPrefix;
    }
    
    /**
     * Configura el tree ID (para SSR)
     */
    public static function setTreeId($treeId) {
        self::$treeId = $treeId;
    }
    
    /**
     * Obtiene el tree ID
     */
    public static function getTreeId() {
        return self::$treeId;
    }
    
    /**
     * Incrementa el tree ID
     */
    public static function incrementTreeId() {
        self::$treeId++;
        self::resetLocalIdCounter();
    }
    
    /**
     * Reinicia el contador local (se llama al inicio de cada componente/árbol)
     */
    public static function resetLocalIdCounter() {
        self::$localIdCounter = 0;
    }
    
    /**
     * Genera un ID único estilo React mountId()
     * Siempre en modo servidor (SSR) con prefijo 'R' mayúscula
     */
    public static function mountId() {
        $identifierPrefix = self::$identifierPrefix;
        $treeId = self::$treeId;
        
        // Modo servidor (SSR) - Usa prefijo 'R' mayúscula
        $id = ':' . $identifierPrefix . 'R' . $treeId;
        
        // A menos que sea el primer ID en este nivel, agregar un número
        // que representa la posición de este hook entre todos los hooks
        $localId = self::$localIdCounter++;
        
        if ($localId > 0) {
            // Convertir a base32 como hace React
            $id .= 'H' . base_convert($localId, 10, 32);
        }
        
        $id .= ':';
        
        return $id;
    }
    
    /**
     * Genera un ID único con un prefijo opcional
     * @param string $prefix Prefijo para el ID (ej: 'div', 'section')
     * @return string ID único generado
     */
    public static function generate($prefix = '') {
        $baseId = self::mountId();
        
        // Combinar con el prefijo para más contexto
        $candidateId = $prefix . $baseId;
        
        // Verificar unicidad (por seguridad adicional)
        $finalId = $candidateId;
        $counter = 1;
        
        while (in_array($finalId, self::$usedIds)) {
            $finalId = $candidateId . $counter;
            $counter++;
        }
        
        self::$usedIds[] = $finalId;
        
        return $finalId;
    }
    
    /**
     * Genera un ID único personalizado con prefijo específico
     */
    public static function generateWithPrefix($customPrefix) {
        $oldPrefix = self::$identifierPrefix;
        
        self::$identifierPrefix = $customPrefix;
        
        $identifierPrefix = self::$identifierPrefix;
        $treeId = self::$treeId;
        
        $id = ':' . $identifierPrefix . 'R' . $treeId;
        
        $localId = self::$localIdCounter++;
        
        if ($localId > 0) {
            $id .= 'H' . base_convert($localId, 10, 32);
        }
        
        $id .= ':';
        
        // Restaurar prefijo original
        self::$identifierPrefix = $oldPrefix;
        
        // Registrar como usado
        self::$usedIds[] = $id;
        
        return $id;
    }
    
    /**
     * Verifica si un ID ya está en uso
     */
    public static function isIdUsed($id) {
        return in_array($id, self::$usedIds);
    }
    
    /**
     * Registra un ID como usado (útil para IDs manuales)
     */
    public static function registerUsedId($id) {
        if (!in_array($id, self::$usedIds)) {
            self::$usedIds[] = $id;
        }
    }
    
    /**
     * Obtiene todos los IDs usados
     */
    public static function getUsedIds() {
        return self::$usedIds;
    }
    
    /**
     * Obtiene el conteo de IDs usados
     */
    public static function getUsedIdsCount() {
        return count(self::$usedIds);
    }
    
    /**
     * Reinicia todo el sistema de IDs
     */
    public static function reset() {
        self::$globalClientIdCounter = 0;
        self::$localIdCounter = 0;
        self::$treeId = 0;
        self::$identifierPrefix = '';
        self::$usedIds = [];
    }
    
    /**
     * Reinicia solo los contadores, mantiene el prefijo y los IDs usados
     */
    public static function resetCounters() {
        self::$globalClientIdCounter = 0;
        self::$localIdCounter = 0;
        self::$treeId = 0;
    }
}

?>
