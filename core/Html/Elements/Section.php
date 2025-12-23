<?php
namespace Core\Html\Elements;
use Core\Html\HtmlElement;

class Section extends HtmlElement
 {
    protected function getTagName() {
        return 'section';
    }
}
