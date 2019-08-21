<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Exception;
use MLocati\C5SinceTagger\Console\Command;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    /**
     * @var \MLocati\C5SinceTagger\ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var \Doctrine\ORM\EntityManager|null
     */
    protected $entityManager;

    public function __construct()
    {
        parent::__construct();
        $this->connectionManager = new ConnectionManager();
        $this->add(new Command\Update($this));
        $this->add(new Command\Parse($this));
        $this->add(new Command\Patch($this));
        $this->add(new Command\CollectPatches($this));
        foreach (ConsoleRunner::createHelperSet($this->getEntityManager()) as $key => $helper) {
            $this->getHelperSet()->set($helper, $key);
        }
        ConsoleRunner::addCommands($this);
    }

    public function getConnection(): Connection
    {
        return $this->connectionManager->getConnection();
    }

    public function getEntityManager(): EntityManager
    {
        if ($this->entityManager === null) {
            $configuration = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Reflected'], (int) \getenv('C5VT_DEV') !== 0);
            $this->entityManager = EntityManager::create($this->getConnection(), $configuration);
        }

        return $this->entityManager;
    }

    /**
     * Get the temporary directory (with '/' as directory separator, withour leading '/').
     *
     * @throws \Exception
     */
    public function getTemporaryDirectory(): string
    {
        $path = \getenv('C5VT_TEMPDIR') ?: (\dirname(__DIR__) . '/tmp');
        if (!\is_dir($path)) {
            @\mkdir($path);
            if (!\is_dir($path)) {
                throw new Exception("Failed to create the temporary directory {$path}");
            }
        }

        return \rtrim(\str_replace(\DIRECTORY_SEPARATOR, '/', \realpath($path)), '/');
    }
}
