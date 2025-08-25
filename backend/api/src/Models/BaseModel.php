<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOStatement;
use Exception;

/**
 * Class BaseModel
 * Minimal PDO wrapper with prepared statement helper
 */
class BaseModel
{
    protected PDO $db;
    protected string $table = '';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Execute a query and return the statement
     */
    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Failed to prepare statement');
        }
        foreach ($params as $key => $value) {
            // bind by name or position
            $paramType = $this->detectParamType($value);
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value, $paramType);
            } else {
                $stmt->bindValue(':' . $key, $value, $paramType);
            }
        }
        $stmt->execute();
        return $stmt;
    }

    private function detectParamType($value): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        if ($value === null) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    protected function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);
        return (int)$this->db->lastInsertId();
    }

    protected function fetchOne(string $sql, array $params = []): array|false
    {
        $stmt = $this->run($sql, $params);
        return $stmt->fetch();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->run($sql, $params);
        return $stmt->rowCount() > 0;
    }
}
