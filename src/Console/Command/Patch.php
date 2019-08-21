<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console\Command;

use MLocati\C5SinceTagger\Console\Command;
use MLocati\C5SinceTagger\CoreVersion\VersionList;
use MLocati\C5SinceTagger\Diff\Differ;
use MLocati\C5SinceTagger\Diff\Patcher;
use MLocati\C5SinceTagger\Parser;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Patch extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('patch')
            ->setDescription('Patch a local concrete5 directory whose phpdocs should be patched.')
            ->addArgument('path', InputArgument::REQUIRED, 'The location of the local concrete5 directory to be patched')
            ->addOption('raw-name', 'r', InputOption::VALUE_NONE, "Keep the detected version (otherwise we'll strip out alpha/beta/rc/...)")
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\C5SinceTagger\Console\Command::handle()
     */
    protected function handle(): int
    {
        $webroot = $this->getWebroot();
        $baseVersion = $this->getBaseVersion($webroot);
        $this->checkRequiredVersionsParset($baseVersion);
        $previousVersions = $this->getPreviousVersions($baseVersion);

        $this->output->writeln('Collecting patches');
        $differ = new Differ($baseVersion, $previousVersions);
        $progressBar = null;
        $differ
            ->setProgressInitHandler(function (int $count) use (&$progressBar): void {
                $progressBar = new ProgressBar($this->output, $count);
            })
            ->setProgressProcessHandler(function () use (&$progressBar): void {
                $progressBar->advance();
            })
            ->setProgressCompletedHandler(function () use (&$progressBar): void {
                $progressBar->finish();
                $progressBar = null;
            });
        $previousMemoryLimit = \ini_set('memory_limit', '-1');
        try {
            $patches = $differ->getPatches();

            if ($patches->isEmpty()) {
                $this->output->writeln('No patches found.');

                return 0;
            }

            $patcher = new Patcher($webroot);
            foreach ($patches->getFiles() as $file) {
                $this->output->write("Patching file {$file}... ");
                $patcher->apply($patches->getFilePatches($file), $file);
                $this->output->writeln('done.');
            }

            return 0;
        } finally {
            if ($previousMemoryLimit !== false) {
                \ini_set('memory_limit', $previousMemoryLimit);
            }
        }
    }

    private function getWebroot(): string
    {
        $rawPath = $this->input->getArgument('path');
        $webroot = \realpath($rawPath);
        if ($webroot && \is_dir($webroot)) {
            $webroot = \rtrim(\str_replace(\DIRECTORY_SEPARATOR, '/', $webroot), '/');
        } else {
            $webroot = '';
        }
        if ($webroot === '') {
            throw new \Exception("Unable to find the directory {$rawPath}");
        }

        return $webroot;
    }

    private function getBaseVersion(string $webroot): ReflectedVersion
    {
        $parser = new Parser($this->getApplication()->getTemporaryDirectory());
        $baseVersion = $parser->parse($webroot);

        if (!$this->input->getOption('raw-name')) {
            $m = null;
            if (!\preg_match('/^(\d+(?:\.\d+)*)*/', $baseVersion->getName(), $m)) {
                throw new \Exception("Failed to extract the version from {$baseVersion->getName()}");
            }
            $baseVersion->setName($m[1]);
        }

        return $baseVersion;
    }

    private function checkRequiredVersionsParset(ReflectedVersion $baseVersion): void
    {
        $em = $this->getApplication()->getEntityManager();
        $versionRepo = $em->getRepository(ReflectedVersion::class);
        $missingVersions = [];
        $versionList = new VersionList();
        $em = $this->getApplication()->getEntityManager();
        foreach ($versionList->getAvailableVersions() as $availableVersion) {
            if (\preg_match('/^\d+(\.\d+)*$/', $availableVersion)) {
                if (\version_compare($availableVersion, $baseVersion->getName()) < 0) {
                    if ($versionRepo->findOneBy(['name' => $availableVersion]) === null) {
                        $missingVersions[] = $availableVersion;
                    }
                }
            }
        }
        if ($missingVersions !== []) {
            throw new \Exception(\sprintf("In order to patch version %s, the following versions must have been analyzed:\n%s", $baseVersion->getName(), ' - ' . \implode("\n - ", $missingVersions)));
        }
    }

    /**
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedVersion[]
     */
    private function getPreviousVersions(ReflectedVersion $baseVersion): array
    {
        $em = $this->getApplication()->getEntityManager();
        $versionRepo = $em->getRepository(ReflectedVersion::class);
        $previousVersions = [];
        foreach ($versionRepo->findAll() as $otherVersion) {
            if (\version_compare($otherVersion->getName(), $baseVersion->getName()) < 0) {
                $previousVersions[] = $otherVersion;
            }
        }
        if ($previousVersions === []) {
            throw new \Exception("No parsed versions found that are older than {$baseVersion->getName()}");
        }

        return $previousVersions;
    }
}
