<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Console\Command;

use MLocati\C5SinceTagger\Console\Command;
use MLocati\C5SinceTagger\Diff\Differ;
use MLocati\C5SinceTagger\Diff\Patches;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CollectPatches extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('collect-patches')
            ->setDescription('Internal command called by the patch command')
            ->addArgument('serialized-base-version', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('output-file', InputArgument::REQUIRED)
            ->addOption('start', 's', InputOption::VALUE_REQUIRED)
            ->addOption('end', 'e', InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\C5SinceTagger\Console\Command::handle()
     */
    protected function handle(): int
    {
        $flags = $this->getDiffFlags();
        [$start, $end] = $this->getRange();
        $baseVersion = $this->getBaseVersion();
        $previousVersions = $this->getPreviousVersions($baseVersion);
        \ini_set('memory_limit', '-1');
        $differ = new Differ($baseVersion, $previousVersions);
        $patches = $differ->getPatches($flags, $start, $end);
        $this->savePatches($patches);

        return 0;
    }

    private function getDiffFlags(): int
    {
        $flags = [
            Differ::FLAG_GLOBALCONSTANTS,
            Differ::FLAG_GLOBALFUNCTIONS,
            Differ::FLAG_INTERFACES,
            Differ::FLAG_CLASSES,
            Differ::FLAG_TRAITS,
        ];
        $map = \array_combine(\array_map('strval', $flags), $flags);
        $wanted = $this->input->getArgument('type');
        if (!isset($map[$wanted])) {
            throw new \Exception(\sprintf("Invalid type: {$wanted} (accepted values are:\n%s", ' - ', \implode("\n - ", $flags)));
        }

        return $map[$wanted];
    }

    private function getBaseVersion(): ReflectedVersion
    {
        $file = $this->input->getArgument('serialized-base-version');
        if (!\is_file($file)) {
            throw new \Exception("Failed to find the file {$file}");
        }
        $contents = \is_readable($file) ? \file_get_contents($file) : false;
        if ($contents === false) {
            throw new \Exception("Failed to read the file {$file}");
        }
        $baseVersion = \unserialize(\base64_decode($contents));
        if (!$baseVersion instanceof ReflectedVersion) {
            throw new \Exception("Bad contents of the file {$file}");
        }

        return $baseVersion;
    }

    private function savePatches(Patches $patches): void
    {
        $outputFile = $this->input->getArgument('output-file');
        if (\file_put_contents($outputFile, \base64_encode(\serialize($patches))) === false) {
            throw new \Exception("Failed to write to file {$outputFile}");
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

    private function getRange(): array
    {
        $start = (string) $this->input->getOption('start');
        if ($start !== '') {
            $start = \strtolower($start);
            if (\strlen($start) !== 1 || $start < 'a' || $start > 'z') {
                throw new \Exception('The start option must be a letter between A and Z');
            }
        }
        $end = (string) $this->input->getOption('end');
        if ($end !== '') {
            $end = \strtolower($end);
            if (\strlen($end) !== 1 || $end < 'a' || $end > 'z') {
                throw new \Exception('The end option must be a letter between A and Z');
            }
        }
        if ($start !== '' && $end !== '' && $start > $end) {
            throw new \Exception('The start option must be lower than the end option');
        }

        return [$start, $end];
    }
}
