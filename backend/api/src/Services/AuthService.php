<?php
declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Class AuthService
 * Helper for JWT creation and verification
 */
class AuthService
{
    private string $secret;
    private string $algo;
    private int $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? '';
        $this->algo = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->expiry = (int)($_ENV['JWT_EXPIRY'] ?? 86400);
        if (empty($this->secret)) {
            throw new Exception('JWT_SECRET is not set in environment.');
        }
    }

    /**
     * Create a signed token for a user array with keys: id, role
     */
    public function createToken(array $user): string
    {
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->expiry,
            'sub' => $user['id'],
            'role' => $user['role'] ?? 'student',
        ];
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify token and return payload
     * throws Exception on invalid token
     */
    public function verifyToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, $this->algo));
    }
}
