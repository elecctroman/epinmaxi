<?php
namespace System\Core;

use PDO;
use PDOException;

class DB
{
    protected static ?PDO $pdo = null;

    public static function init(array $config): void
    {
        if (self::$pdo) {
            return;
        }

        if (($config['driver'] ?? 'mysql') === 'sqlite') {
            $dsn = 'sqlite:' . ($config['database'] ?? ':memory:');
        } else {
            $host = $config['host'] ?? 'localhost';
            $port = (int) ($config['port'] ?? 3306);
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'] ?? 'mysql',
                $host,
                $port,
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );
        }

        try {
            self::$pdo = new PDO(
                $dsn,
                $config['username'] ?? '',
                $config['password'] ?? '',
                $config['options'] ?? []
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException('Database not initialised');
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
