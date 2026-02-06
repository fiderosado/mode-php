<?php
namespace Core\Http;

class CSRF {
    public static function token(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verify(string $token): bool {
        return isset($_SESSION['csrf_token']) 
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}