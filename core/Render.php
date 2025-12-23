<?php
namespace Core;

use Core\Html\HtmlElement;

class Render {

    const off = 0;
    protected $resp;    
    public static $instance;

    protected function getTagName() {
        return 'html';
    }
    
    public static function in() {
        $className = get_called_class();
        self::$instance = new $className(func_get_args());
        return self::$instance;
    }

    public function __construct() {
        $args = func_get_args() ?? [];
        $this->work($args);
        $this->build();
    }
    
    private function work($args) {
        $aa = array();
        
        for($i = self::off; $i < count($args); ++$i) {
            if (is_object($args[$i])) {
                array_push($aa, $args[$i]->build());
            }
        }
        
        $this->resp = implode('', $aa);
    }
    
    public function build() {
        print $this->resp;
    }
    
}
