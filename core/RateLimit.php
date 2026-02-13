<?php
namespace Core;

class RateLimit {
    public static function check(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool {
        $key = "rate_limit:{$key}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'reset_at' => time() + ($decayMinutes * 60)];
        }
        
        $data = $_SESSION[$key];
        
        // Reset si ya pasó el tiempo
        if (time() > $data['reset_at']) {
            $_SESSION[$key] = ['attempts' => 1, 'reset_at' => time() + ($decayMinutes * 60)];
            return true;
        }
        
        // Verificar límite
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$key]['attempts']++;
        return true;
    }
}
