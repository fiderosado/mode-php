<?php

namespace Core\Html;

use Core\IdGenerator;
use Core\File;

abstract class HtmlElement {
    
    const off = 0;
    protected $style=null;
    protected $id;
    protected $class;
    protected $resp;
    protected $dataId;
    protected static $counter = 0;
    protected $cssContent =null; 
    public static $instance;
    
    /**
     * Devuelve el nombre de la etiqueta HTML (debe ser implementado por las clases hijas)
     */
    abstract protected function getTagName();
    
    /**
     * Método estático para crear instancias de forma fluida
     */
    public static function in() {
        $className = get_called_class();
        self::$instance = new $className(func_get_args());
        return self::$instance;
    }
    
    public function __construct() {
          // Generar ID único automáticamente
          $this->generateDataId();

        $args = func_get_args()[0] ?? [];
        $c = array();
        
        for($i = self::off; $i < count($args); ++$i) {
            if (is_object($args[$i])) {
                array_push($c, $args[$i]->build());
            } elseif (is_string($args[$i])) {
                array_push($c, $args[$i]);
            }
        }
        
        $this->resp = implode('', $c);
    }

       /**
     * Obtiene la ruta del archivo de la clase actual
     */
    protected function getClassPath() {
        $reflection = new \ReflectionClass(get_class($this));
        return $reflection->getFileName();
    }
    
    /**
     * Obtiene el directorio donde está ubicada la clase actual
     */
    protected function getClassDirectory() {
        $classPath = $this->getClassPath();
        return dirname($classPath);
    }
    
    /**
     * Obtiene el nombre de la clase sin namespace
     */
    protected function getClassName() {
        $reflection = new \ReflectionClass(get_class($this));
        return $reflection->getShortName();
    }
    
    /**
     * Obtiene el namespace completo de la clase
     */
    protected function getClassNamespace() {
        return get_class($this);
    }
    
     /**
     * Genera un ID único para el elemento
     */
    protected function generateDataId() {
        $tagName = $this->getTagName();
        $this->dataId = IdGenerator::generate($tagName);
    }

    /**
     * Permite establecer un data-id personalizado
     */
    public function setDataId($dataId) {
        if (IdGenerator::isIdUsed($dataId)) {
            $counter = 1;
            $newDataId = $dataId . '-' . $counter;
            
            while (IdGenerator::isIdUsed($newDataId)) {
                $counter++;
                $newDataId = $dataId . '-' . $counter;
            }
            
            $dataId = $newDataId;
        }
        
        IdGenerator::registerUsedId($dataId);
        $this->dataId = $dataId;
        
        return $this;
    }
    
    /**
     * Obtiene el data-id del elemento
     */
    public function getDataId() {
        return $this->dataId;
    }

    /**
     * Obtiene el atributo data-id
     */
    protected function getDataIdAttribute() {
        return ' data-id="' . $this->dataId . '"';
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function setStyle($s) {
        if (is_array($s)) {
            foreach ($s as $k => $v) {
                $this->style .= $k . ':' . $v . ';';
            }
        }
        // si es un objeto
        if (is_object($s)) {
            foreach ($s as $k => $v) {
                $this->style .= $k . ':' . $v . ';';
            }
        }
        return $this;
    }
    
    public function class($class) {
        if (is_array($class)) {
            $this->class = implode(' ', $class);
        } elseif (is_string($class)) {
            $this->class = $class;
        }
        return $this;
    }
    
    protected function getId() {
        return (empty($this->id)) ? '' : ' id="' . $this->id . '"';
    }
    
    protected function getClass() {
        return (empty($this->class)) ? '' : ' class="' . $this->class . '"';
    }
    
    protected function getStyle() {
        return (empty($this->style)) ? '' : ' style="' . $this->style . '"';
    }
    
    /**
     * Obtiene todos los atributos concatenados
     */
    protected function getAttributes() {
        return $this->getDataIdAttribute() . $this->getId() . $this->getClass() . $this->getStyle();
    }

    /**
     * Construye la ruta del archivo CSS basado en la ubicación de la clase
     */
    protected function buildCssPath() {
        // Obtener el directorio de la clase
        $classDir = $this->getClassDirectory();
        
        // Obtener el nombre de la clase (sin namespace)
        $className = $this->getClassName();
        
        // Construir la ruta del archivo CSS
        // Asume que el CSS está en el mismo directorio con el mismo nombre
        $cssPath = $classDir . DIRECTORY_SEPARATOR . $className . '.css';
        
        return $cssPath;
    }

    /**
     * Carga el archivo CSS asociado a la clase del elemento
     */
    protected function loadCss() {
        // Solo intentar cargar una vez
        if ($this->cssContent !== null) {
            return;
        }
        
        // Construir la ruta del CSS
        $cssPath = $this->buildCssPath();
        
        // Intentar cargar el archivo CSS directamente
        $cssContent = File::loadFile($cssPath, 'css');
        
        // Guardar el resultado (puede ser string con CSS o false)
        $this->cssContent = $cssContent;
    }
    /**
     * Obtiene el tag <style> con el CSS cargado
     */
    protected function getCssTag() {
        // Cargar CSS si no se ha intentado antes
        if ($this->cssContent === null) {
            $this->loadCss();
        }
        
        // Si hay contenido CSS válido, retornar el tag <style>
        if ($this->cssContent !== false && !empty($this->cssContent)) {
            return '<style>' . $this->cssContent . '</style>';
        }
        
        return '';
    }
    /**
     * Construye el elemento HTML completo
     */
    public function build() {
        $tag = $this->getTagName();
        $cssTag = $this->getCssTag();
        return  $cssTag . '<' . $tag . $this->getAttributes() . '>' . $this->resp . '</' . $tag . '>';
    }
}

?>