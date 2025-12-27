<?php
session_start();

$clientId = $_ENV['AUTH_GOOGLE_ID']; // acceder a la variable de entorno de boogle
$redirectUri = $_ENV['APP_URL'] . '/api/auth/callback/google';
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

header("Location: $url");
exit;
