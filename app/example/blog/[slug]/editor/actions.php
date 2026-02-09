<?php

use Core\Html\Elements\Div;
use Core\Http\HttpResponse;
use Core\Http\ServerAction;

ServerAction::define("editor-suspense",function ($data, $params) {
    return HttpResponse::html(
        Div::in("El edito form ->", $data["id"])->class("p-4 bg-green-500 text-white")
    );
});

ServerAction::define("hola-suspense",function ($data, $params) {
    return HttpResponse::html(
        Div::in("Hola Mundo ->", $data["id"] )->class("p-4 bg-indigo-500 text-white")
    );
});