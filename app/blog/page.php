<?php

use Core\Render;
use Core\Html\Elements\Nav;

function NavBar(){
    $color = "red";

    return new Render(
        Nav::in("hola navigation esta de pp")->setStyle(['color' => $color])->setClass("nav-bar")
    );

}

NavBar();