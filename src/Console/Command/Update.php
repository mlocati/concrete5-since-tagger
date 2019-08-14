<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console\Command;

use Doctrine\ORM\EntityManager;
use MLocati\C5SinceTagger\Console\Command;
use MLocati\C5SinceTagger\CoreVersion\VersionDetector;
use MLocati\C5SinceTagger\CoreVersion\VersionList;
use MLocati\C5SinceTagger\CoreVersion\VersionStorage;
use MLocati\C5SinceTagger\Parser;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;
use Symfony\Component\Console\Input\InputOption;

class Update extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('update')
            ->setDescription('Update the missing concrete5 versions.')
            ->addOption('redownload', 'd', InputOption::VALUE_NONE, 'Force the download of the version even if it has already been downloaded')
            ->setHelp('This command downloads and analyze all the missing concrete5 versions.')
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
        $versionDetector = new VersionDetector();
        foreach ($versionList->getAvailableVersions() as $version) {
            if (\preg_match('/^\d+(\.\d+)*$/', $version)) {
                $this->processVersion($version, $versionList->getVersionUrl($version), $versionDetector);
            }
        }

        return 0;
    }

    private function processVersion(string $version, string $versionUrl, VersionDetector $versionDetector): void
    {
        $this->output->writeln("### PARSING VERSION {$version}");

        $em = $this->getApplication()->getEntityManager();
        $em->clear();

        try {
            $repo = $em->getRepository(ReflectedVersion::class);

            if ($repo->findOneBy(['name' => $version]) !== null) {
                $this->output->writeln('Already parsed: skipping.');

                return;
            }

            $storage = new VersionStorage($this->getApplication()->getTemporaryDirectory(), $this->output);
            $webroot = $storage->ensure($version, $versionUrl, $this->input->getOption('redownload'));

            $actualVersion = $versionDetector->detectVersion($webroot);
            if ($repo->findOneBy(['name' => $actualVersion]) !== null) {
                $this->output->writeln('Already parsed: skipping.');

                return;
            }

            $parser = new Parser($this->getApplication()->getTemporaryDirectory(), null, $versionDetector);
            $newVersion = $parser->parse($webroot);

            $em->transactional(function (EntityManager $em) use ($repo, $newVersion): void {
                $this->output->write('Persisting parsed version... ');
                $em->persist($newVersion);
                $em->flush($newVersion);
                $this->output->writeln('done.');
                $this->output->write('Committing changes... ');
            });
            $this->output->writeln('done.');
        } finally {
            try {
                $em->clear();
            } catch (\Throwable $x) {
            }
        }
    }
}
