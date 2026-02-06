<?php

use Core\App;
//use Core\IdGenerator;

use Core\Html\Elements\Div;
use Core\Html\Elements\Header;
use Core\Html\Elements\Section;

//IdGenerator::setIdentifierPrefix('myapp-');

App::in(
    Header::in("Bienvenido")->setId("main-header")->setClass("header"),
    Div::in(
        Section::in("Contenido de sección 1")->setClass("sec1"),
        Section::in("Contenido de sección 2")->setClass("sec2")
    )->setClass("wrapper")
)->build();
