<?php

namespace Core\Security;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;

class Jwt
{
    /**
     * Instancia singleton
     */
    private static ?self $instance = null;

    /**
     * Configuración interna
     */
    private string $secret;
    private string $algo = 'HS256';
    private ?string $issuer = null;

    /**
     * Constructor privado
     */
    private function __construct(array $config = [])
    {
        $this->applyConfig($config);
    }

    /**
     * Obtener instancia
     */
    public static function in(array $config = []): self
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        } elseif (!empty($config)) {
            self::$instance->applyConfig($config);
        }

        return self::$instance;
    }

    /**
     * Aplica configuración (sobrescribe si existe)
     */
    private function applyConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key) && $value !== null) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Setters fluent
     */
    public function setSecret(string $secret): self
    {
        $this->secret = $secret;
        return $this;
    }

    public function setIssuer(string $issuer): self
    {
        $this->issuer = $issuer;
        return $this;
    }

    public function setAlgo(string $algo): self
    {
        $this->algo = $algo;
        return $this;
    }

    /**
     * Codifica payload a JWT
     */
    public function encode(array $payload, int $expiresIn = 3600): string
    {
        if (empty($this->secret)) {
            throw new Exception('JWT secret not set');
        }

        $time = time();

        $tokenPayload = array_merge($payload, [
            'iat' => $time,
            'exp' => $time + $expiresIn,
        ]);

        if ($this->issuer) {
            $tokenPayload['iss'] = $this->issuer;
        }

        return FirebaseJWT::encode(
            $tokenPayload,
            $this->secret,
            $this->algo
        );
    }

    /**
     * Decodifica JWT
     */
    public function decode(string $jwt): object
    {
        if (empty($this->secret)) {
            throw new Exception('JWT secret not set');
        }

        return FirebaseJWT::decode(
            $jwt,
            new Key($this->secret, $this->algo)
        );
    }

    /**
     * Valida JWT
     */
    public function validate(string $jwt): bool
    {
        try {
            // 1️⃣ Validar estructura
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return false;
            }

            // 2️⃣ Validar base64 de header y payload
            foreach ([$parts[0], $parts[1]] as $part) {
                if (!preg_match('/^[A-Za-z0-9\-_]+$/', $part)) {
                    return false;
                }
            }

            // 3️⃣ Decodificar (valida firma y algoritmo)
            $decoded = $this->decode($jwt);
            $now = time();

            // 4️⃣ exp obligatorio
            if (!isset($decoded->exp) || $decoded->exp <= $now) {
                return false;
            }

            // 5️⃣ nbf (si existe)
            if (isset($decoded->nbf) && $decoded->nbf > $now) {
                return false;
            }

            // 6️⃣ iat (si existe, no futuro)
            if (isset($decoded->iat) && $decoded->iat > $now + 5) {
                return false;
            }

            // 7️⃣ issuer
            if ($this->issuer && ($decoded->iss ?? null) !== $this->issuer) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }


    /**
     * Payload como array
     */
    public function getPayload(string $jwt): ?array
    {
        try {
            return (array) $this->decode($jwt);
        } catch (Exception) {
            return null;
        }
    }
}
