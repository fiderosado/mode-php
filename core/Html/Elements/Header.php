<?php

namespace Core\Html\Elements;
use Core\Html\HtmlElement;

class Header extends HtmlElement {
    protected function getTagName() {
        return 'header';
    }
}