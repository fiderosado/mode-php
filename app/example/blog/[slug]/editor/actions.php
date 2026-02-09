<?php

use Core\Html\Elements\Div;
use Core\Http\HttpResponse;
use Core\Http\ServerAction;

ServerAction::define("editor-suspense",function(){
    return HttpResponse::html(
        Div::in("El edito form")->class("p-4 bg-green-500 text-white")
    );
});

ServerAction::define("hola-suspense",function(){
    return HttpResponse::html(
        Div::in("Hola Mundo")->class("p-4 bg-indigo-500 text-white")
    );
});