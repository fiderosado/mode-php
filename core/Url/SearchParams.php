<?php

namespace Core\Url;

function searchParams(): object {

    // Params de la ruta
    $rparams = (object) ($GLOBALS['params'] ?? []);

    // Query string
    $query = $_SERVER['QUERY_STRING'] ?? '';

    $qparams = new \stdClass();
    if ($query !== '') {
        parse_str($query, $arr);

        // Convertir strings "true"/"false" y números
        $arr = array_map(function($value) {
            if ($value === 'true') return true;
            if ($value === 'false') return false;
            if (is_numeric($value)) return $value + 0;
            return $value;
        }, $arr);

        $qparams = (object) $arr;
    }

    $result = new \stdClass();
    $result->params = $rparams;
    $result->query  = $qparams;

    return $result;
}
