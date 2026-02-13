<?php

namespace Auth\Providers;

use PDO;

class Credentials implements Provider
{
    private ?PDO $db;
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->db = $config['db'] ?? null;
        $this->config = $config;
    }

    public function findUserByEmail(string $email): ?array
    {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$email]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (\Exception $e) {

                return null;
            }
        }

        $users = [
            [
                'id' => '1',
                'email' => 'user@example.com',
                'name' => 'Ejemplo Usuario',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'verified' => true
            ]
        ];

        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }

        return null;
    }

    public function createUser(array $data): ?array
    {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare(
                    'INSERT INTO users (email, name, password, created_at) VALUES (?, ?, ?, NOW())'
                );

                $password = $data['password'] ?? null;
                $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

                $stmt->execute([
                    $data['email'],
                    $data['name'] ?? null,
                    $hashedPassword
                ]);

                $userId = $this->db->lastInsertId();

                return $this->findUserById($userId);
            } catch (\Exception $e) {

                return null;
            }
        }

        return null;
    }

    public function findUserById(string $id): ?array
    {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (\Exception $e) {

                return null;
            }
        }

        return null;
    }

    public function authorize(array $credentials): ?array
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return null;
        }

        $user = $this->findUserByEmail($email);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'] ?? '')) {
            return null;
        }

        unset($user['password']);

        $user['provider'] = 'credentials';
        $user['verified'] = $user['verified'] ?? false;

        return $user;
    }

    public function handleCallback(): void
    {
        // No aplica para proveedor de credenciales
    }

    public function getName(): string
    {
        return 'Credentials';
    }

    public function getType(): string
    {
        return 'credentials';
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
