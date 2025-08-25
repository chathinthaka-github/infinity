<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

/**
 * Class User
 * Basic user model for auth
 */
class User extends BaseModel
{
    protected string $table = 'users';

    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }

    /**
     * Create a new user. Expects already-hashed password.
     * Returns inserted ID or 0 on failure.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO users 
            (email, password_hash, full_name, whatsapp_number, role, is_active, created_at)
            VALUES (:email, :password_hash, :full_name, :whatsapp_number, :role, :is_active, :created_at)";

        $params = [
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'full_name' => $data['full_name'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'role' => $data['role'] ?? 'student',
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        return $this->insert($sql, $params);
    }

    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT id, email, password_hash, full_name, whatsapp_number, role, is_active, created_at
                FROM users WHERE email = :email LIMIT 1";
        return $this->fetchOne($sql, ['email' => $email]);
    }

    public function findById(int $id): array|false
    {
        $sql = "SELECT id, email, full_name, whatsapp_number, role, is_active, created_at
                FROM users WHERE id = :id LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }
}
