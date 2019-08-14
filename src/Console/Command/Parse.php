<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console\Command;

use Doctrine\ORM\EntityManager;
use Exception;
use MLocati\C5SinceTagger\Console\Command;
use MLocati\C5SinceTagger\CoreVersion\VersionList;
use MLocati\C5SinceTagger\CoreVersion\VersionStorage;
use MLocati\C5SinceTagger\Parser;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Parse extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('parse')
            ->setDescription('Parse a specific concrete5 version.')
            ->setHelp('This command downloads and analyzes a specific concrete5 version.')
            ->addOption('redownload', 'd', InputOption::VALUE_NONE, 'Force the download of the version even if it has already been downloaded')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to be parsed')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\C5SinceTagger\Console\Command::handle()
     */
    protected function handle(): int
    {
        $versionList = new VersionList();
        $version = $this->input->getArgument('version');
        $versionUrl = $versionList->getVersionUrl($version);
        if ($versionUrl === null) {
            throw new Exception("Unable to find version {$version}.\nAvailable versions are:\n- " . \implode("\n- ", $versionList->getAvailableVersions()));
        }

        $storage = new VersionStorage($this->getApplication()->getTemporaryDirectory(), $this->output);
        $webroot = $storage->ensure($version, $versionUrl, $this->input->getOption('redownload'));

        $parser = new Parser($this->getApplication()->getTemporaryDirectory());
        $newVersion = $parser->parse($webroot);

        $this->getApplication()->getEntityManager()->transactional(function (EntityManager $em) use ($newVersion): void {
            $repo = $em->getRepository(ReflectedVersion::class);
            $oldVersion = $repo->findOneBy(['name' => $newVersion->getName()]);
            if ($oldVersion !== null) {
                $this->output->write('Removing previously parsed version... ');
                $em->remove($oldVersion);
                $em->flush($oldVersion);
                $this->output->writeln('done.');
            }
            $this->output->write('Persisting parsed version... ');
            $em->persist($newVersion);
            $em->flush($newVersion);
            $this->output->writeln('done.');
            $this->output->write('Committing changes... ');
        });
        $this->output->writeln('done.');

        return 0;
    }
}
