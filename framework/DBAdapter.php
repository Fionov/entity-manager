<?php

declare(strict_types=1);

namespace Framework;

use Exception;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DBAdapter
{
    /** @var \Framework\DBAdapter|null */
    private static ?self $instance = null;

    /** @var \PDO */
    private PDO $pdo;

    /** @var \Psr\Log\LoggerInterface */
    private LoggerInterface $logger;

    /**
     * Disable constructor
     */
    protected function __construct(
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->initConnection();
    }

    /**
     * Disable clone
     */
    protected function __clone()
    {
    }

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton instance.');
    }

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return \PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * @return void
     */
    private function initConnection(): void
    {
        $config = require __DIR__ . '/../config/db.php';
        $dsn = "mysql:host={$config['host']}:{$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        try {
            $this->pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }
}