<?php
namespace Core;

use Core\Html\HtmlElement;

class App extends HtmlElement {

    protected function getTagName() {
        return 'html';
    }
    
    public function __construct() {
        $args = func_get_args()[0] ?? [];
        $this->work($args);
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
        print '<html' . $this->getAttributes() . '>' . $this->resp . '</html>';
    }
    
}
