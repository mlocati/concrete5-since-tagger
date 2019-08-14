<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionManager
{
    /**
     * @var \Doctrine\DBAL\Connection|null
     */
    private $connection;

    private function getConnectionParameters(): array
    {
        return [
            'driver' => 'pdo_mysql',
            'host' => \getenv('C5VT_DB_HOSTNAME'),
            'port' => (int) \getenv('C5VT_DB_PORT') ?: null,
            'dbname' => \getenv('C5VT_DB_DATABASE'),
            'user' => \getenv('C5VT_DB_USERNAME'),
            'password' => \getenv('C5VT_DB_PASSWORD'),
            'charset' => \getenv('C5VT_DB_CHARSET') ?: null,
        ];
    }

    public function getConnection(): Connection
    {
        if (null === $this->connection) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    private function createConnection(): Connection
    {
        return DriverManager::getConnection($this->getConnectionParameters());
    }
}
