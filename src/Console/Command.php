<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console;

use MLocati\C5SinceTagger\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \MLocati\C5SinceTagger\Application getApplication()
 */
abstract class Command extends SymfonyCommand
{
    protected $input;

    protected $output;

    public function __construct(Application $application)
    {
        parent::__construct();
        $this->setApplication($application);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->handle();
    }

    abstract protected function handle(): int;
}
