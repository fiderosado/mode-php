<?php

use Core\Html\Elements\Div;
use Core\Http\ServerAction;
use Core\Http\HttpResponse;

use function TailwindPHP\cn;

ServerAction::define('hola-mundo', function ($data, $params) {
    // sleep(2);
    return HttpResponse::html(
        Div::in("Hola mundo")->class("p-4 bg-green-500 text-white")
    );
});
