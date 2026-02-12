<?php

namespace Core\Http;

class Http
{
    public static function in(callable $callback)
    {
        $request  = new HttpRequest();
        $response = new HttpResponse(); // ya la tienes hecha

        // Ejecutar callback
        $callback($request, $response);

        // Devolver instancia de respuesta
        return $response;
    }
}
