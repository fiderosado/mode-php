<?php

namespace Core\Html\Elements;
use Core\Html\HtmlElement;

class Div extends HtmlElement {
    protected function getTagName(): string
    {
        return 'div';
    }
}