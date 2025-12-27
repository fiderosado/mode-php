<?php

use Core\Http\Connect;
use Core\Security\Jwt;
use Core\Utils\Console;

session_start();

if (
    !isset($_SESSION['oauth_state']) ||
    !isset($_GET['state']) ||
    $_GET['state'] !== $_SESSION['oauth_state']
) {
    http_response_code(401);
    exit('Invalid state');
}

$code = $_GET['code'];

$tokenResponse = Connect::post('https://oauth2.googleapis.com/token', [
    'client_id'     => $_ENV['AUTH_GOOGLE_ID'],
    'client_secret' => $_ENV['AUTH_GOOGLE_SECRET'],
    'code'          => $code,
    'redirect_uri'  => $_ENV['APP_URL'] . '/api/auth/callback/google',
    'grant_type'    => 'authorization_code'
]);
 
Console::log("token response->", $tokenResponse);

/* 
{
    "success": {
        "data": {
            "access_token": "ya290206",
            "expires_in": 3599,
            "refresh_token": "1c4VU",
            "scope": "https://www.googleapis.com/auth/userinfo.email openid https://www.googleapis.com/auth/userinfo.profile",
            "token_type": "Bearer",
            "id_token": "eyUA"
        }
    }
}
*/

if (!$tokenResponse['success']) {
    http_response_code(401);
    exit('Invalid token');
}

$token = $tokenResponse['success']['data']['access_token'] ?? false;

if (!$token) {
    http_response_code(401);
    exit('Invalid token');
}

$responseUserInfo = Connect::get('https://openidconnect.googleapis.com/v1/userinfo',  [
    'headers' => [
        'Authorization' => "Bearer {$token}",
    ]
]);

/* 
{
    "success": {
        "data": {
            "sub": "117204732513714275660",
            "name": "Fidel Remedios Rosado",
            "given_name": "Fidel",
            "family_name": "Remedios Rosado",
            "picture": "https://lh3.googleusercontent.com/a/ACg8ocJYLs9-zN_CUEGoZN72GzwmhoRYaJDpLsEIJ4U2iXOgtzniAhMc=s96-c",
            "email": "fiderosado@gmail.com",
            "email_verified": true
        }
    }
}
*/

Console::log("responseUserInfo->", $responseUserInfo);

if (!$responseUserInfo['success']) {
    http_response_code(401);
    exit('Invalid User Info');
}

$userInfo = $responseUserInfo["success"]["data"] ?? false;

if (!$userInfo) {
    http_response_code(401);
    exit('Invalid user info');
}


/**
 * userInfo contiene:
 * - sub (id google)
 * - email
 * - name
 * - picture
 */

/* $_SESSION['user'] = [
    'id' => $userInfo['sub'],
    'email' => $userInfo['email'],
    'name' => $userInfo['name'],
    'image' => $userInfo['picture'],
    'provider' => 'google'
]; */



$jwt = Jwt::in([
    'secret' => $_ENV['JWT_SECRET_SIGN'],
    'issuer' => 'dev.anfitrion.us',
])
    ->encode([
        'sub' => $userInfo['sub'],
        'email' => $userInfo['email'],
        'name' => $userInfo['name'],
        'provider' => 'google'
    ], 3600);


Console::log("jwt->", $jwt);


// Registrar cookie igual que NextAuth
$cookieName = 'php-auth.session-token';
//'__Secure-next-auth.session-token';
setcookie(
    $cookieName,
    $jwt,
    [
        'expires'  => time() + 3600,
        'path'     => '/',
        'domain'   => 'dev.anfitrion.us',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]
);

unset($_SESSION['oauth_state']);
header('Location: /');

// tipo lax
//  authjs.callback-url : http://dev.anfitrion.us:3000/
// authjs.session-token : token