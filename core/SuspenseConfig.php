<?php

namespace Core;

/**
 * Configuración para Suspense
 * 
 * Archivo opcional para configurar el comportamiento de Suspense
 */
class SuspenseConfig
{
    /**
     * Modo de compatibilidad con compresión
     * 
     * - 'auto': Detecta y desactiva compresión automáticamente (recomendado)
     * - 'force_streaming': Fuerza el streaming sin importar la configuración
     * - 'async_only': Solo permite el modo asíncrono (AJAX)
     */
    public static string $compressionMode = 'auto';
    
    /**
     * Habilitar modo debug
     * Muestra información adicional en la consola del navegador
     */
    public static bool $debug = false;
    
    /**
     * Tamaño mínimo del buffer antes de hacer flush (bytes)
     * Ajustar según la configuración del servidor
     */
    public static int $minBufferSize = 256;
    
    /**
     * Agregar padding para forzar el flush en algunos servidores
     */
    public static bool $addPadding = false;
    
    /**
     * Caracteres de padding (espacios en comentario HTML)
     */
   // public static string $paddingContent = '<!-- ' . str_repeat(' ', 1024) . ' -->';
}
