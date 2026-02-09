<?php

use Core\App;
//use Core\IdGenerator;

use Core\Html\Elements\Div;
use Core\Html\Elements\Header;
use Core\Html\Elements\Section;

//IdGenerator::setIdentifierPrefix('myapp-');

App::in(
    Header::in("Bienvenido")->setId("main-header")->class("header"),
    Div::in(
        Section::in("Contenido de sección 1")->class("sec1"),
        Section::in("Contenido de sección 2")->class("sec2")
    )->class("wrapper")
)->build();
