<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console\Command;

use MLocati\C5SinceTagger\Console\Command;
use MLocati\C5SinceTagger\CoreVersion\VersionList;
use MLocati\C5SinceTagger\Diff\Differ;
use MLocati\C5SinceTagger\Diff\Patcher;
use MLocati\C5SinceTagger\Diff\Patches;
use MLocati\C5SinceTagger\Filesystem;
use MLocati\C5SinceTagger\Parser;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Patch extends Command
{
    /**
     * @var \MLocati\C5SinceTagger\Filesystem
     */
    private $fs;

    protected function configure(): void
    {
        $this
            ->setName('patch')
            ->setDescription('Patch a local concrete5 directory whose phpdocs should be patched.')
            ->addArgument('path', InputArgument::REQUIRED, 'The location of the local concrete5 directory to be patched')
            ->addOption('raw-name', 'r', InputOption::VALUE_NONE, "Keep the detected version (otherwise we'll strip out alpha/beta/rc/...)")
            ->addOption('fast', 'f', InputOption::VALUE_NONE, 'Speedup the process, using a huge amount of memory')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\C5SinceTagger\Console\Command::handle()
     */
    protected function handle(): int
    {
        $this->fs = new Filesystem();
        $webroot = $this->getWebroot();
        $baseVersion = $this->getBaseVersion($webroot);
        $this->checkRequiredVersionsParset($baseVersion);
        $this->output->writeln('Collecting patches');
        if ($this->input->getOption('fast')) {
            $patches = $this->collectPatchesInOneRun($baseVersion);
        } else {
            $patches = $this->collectPatchesInSteps($baseVersion);
        }

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
    }

    private function collectPatchesInOneRun(ReflectedVersion $baseVersion): Patches
    {
        $previousVersions = $this->getPreviousVersions($baseVersion);

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
        \ini_set('memory_limit', '-1');

        return $differ->getPatches();
    }

    private function collectPatchesInSteps(ReflectedVersion $baseVersion): Patches
    {
        $patches = new Patches();
        $baseVersionFile = \tempnam($this->getApplication()->getTemporaryDirectory(), 'bv');
        try {
            \file_put_contents($baseVersionFile, \base64_encode(\serialize($baseVersion)));

            $this->output->write(' - analyzing global constants... ');
            $patches->merge($this->collectPatchesInStep($baseVersionFile, Differ::FLAG_GLOBALCONSTANTS));
            $this->output->writeln('done.');
            $this->output->write(' - analyzing global functions... ');
            $patches->merge($this->collectPatchesInStep($baseVersionFile, Differ::FLAG_GLOBALFUNCTIONS));
            $this->output->writeln('done.');
            $this->output->write(' - analyzing interfaces... ');
            $patches->merge($this->collectPatchesInStep($baseVersionFile, Differ::FLAG_INTERFACES));
            $this->output->writeln('done.');
            foreach ($this->getClassSteps() as [$start, $end]) {
                if ($start === '') {
                    $msg = 'from beginning to ' . \strtoupper($end);
                } elseif ($end === '') {
                    $msg = 'from ' . \strtoupper($start) . ' to the end';
                } else {
                    $msg = 'from ' . \strtoupper($start) . ' to ' . \strtoupper($end);
                }
                $this->output->write(" - analyzing classes {$msg}... ");
                $patches->merge($this->collectPatchesInStep($baseVersionFile, Differ::FLAG_CLASSES, $start, $end));
                $this->output->writeln('done.');
            }
            $this->output->write(' - analyzing traits... ');
            $patches->merge($this->collectPatchesInStep($baseVersionFile, Differ::FLAG_TRAITS));
            $this->output->writeln('done.');
        } finally {
            try {
                $this->fs->deleteFile($baseVersionFile);
            } catch (\Throwable $x) {
            }
        }

        return $patches;
    }

    private function collectPatchesInStep(string $baseVersionSerializedFile, int $differFlag, string $start = '', string $end = ''): Patches
    {
        $patchesVersionFile = \tempnam($this->getApplication()->getTemporaryDirectory(), 'dif');
        try {
            $commandChunks = [
                \escapeshellarg(PHP_BINARY),
                \escapeshellarg(\dirname(__DIR__, 3) . \DIRECTORY_SEPARATOR . 'bin' . \DIRECTORY_SEPARATOR . 'concrete5-since-tagger'),
                'collect-patches',
            ];
            if ($start !== '') {
                $commandChunks[] = \escapeshellarg("--start={$start}");
            }
            if ($end !== '') {
                $commandChunks[] = \escapeshellarg("--end={$end}");
            }
            $commandChunks = \array_merge($commandChunks, [
                '--',
                \escapeshellarg($baseVersionSerializedFile),
                (string) $differFlag,
                \escapeshellarg($patchesVersionFile),
            ]);
            $command = \implode(' ', $commandChunks) . ' 2>&1';
            $output = [];
            $rc = -1;
            \exec($command, $output, $rc);
            if ($rc !== 0) {
                throw new \Exception(\sprintf('Failed to get diff chunks: %s', \trim(\implode("\n", $output))));
            }
            $patches = \unserialize(\base64_decode(\file_get_contents($patchesVersionFile)));
            if (!$patches instanceof Patches) {
                throw new \Exception(\sprintf('Failed to unserialize diff chunks: %s', \trim(\implode("\n", $output))));
            }

            return $patches;
        } finally {
            try {
                $this->fs->deleteFile($patchesVersionFile);
            } catch (\Throwable $x) {
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

    private function getClassSteps(): array
    {
        /*
         * a: 10.27%
         * b: 2.70%
         * c: 12.06%
         * d: 5.59%
         * e: 6.82%
         * f: 5.95%
         * g: 2.54%
         * h: 0.63%
         * i: 5.24%
         * j: 0.75%
         * k: 0.24%
         * l: 2.46%
         * m: 4.05%
         * n: 2.02%
         * o: 1.23%
         * p: 7.22%
         * q: 0.20%
         * r: 3.45%
         * s: 9.40%
         * t: 5.00%
         * u: 4.09%
         * v: 6.90%
         * w: 0.83%
         * x: 0.20%
         * y: 0.04%
         * z: 0.12%
         */
        return [
            ['', 'a'],
            ['b', 'b'],
            ['c', 'c'],
            ['d', 'e'],
            ['f', 'h'],
            ['j', 'l'],
            ['m', 'o'],
            ['p', 'p'],
            ['q', 'r'],
            ['s', 's'],
            ['t', ''],
        ];
    }
}
