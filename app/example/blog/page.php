<?php

use Core\Render;
use Core\Html\Elements\Nav;
use Core\Http\Connect;

/* function NavBar(){
    $color = "red";

    return new Render(
        Nav::in("hola navigation esta de pp")->setStyle(['color' => $color])->class("nav-bar")
    );

}

NavBar(); */
/* 
// Las variables ya están cargadas automáticamente
$response = Connect::get('/api/anfitrions?');

var_dump($response);

if (isset($response['success'])) {
    print_r($response['success']['data']);
} else {
    echo "Error: " . $response['error']['message'];
} */

/* 
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

var_dump($response, curl_error($ch));
 */