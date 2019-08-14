<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger;

use Exception;
use MLocati\C5SinceTagger\CoreVersion\VersionDetector;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class Parser
{
    /**
     * @var string
     */
    private $temporaryDirectory;

    /**
     * @var \MLocati\C5SinceTagger\Unserializer
     */
    private $unserializer;

    /**
     * @var \MLocati\C5SinceTagger\CoreVersion\VersionDetector
     */
    private $versionDetector;

    public function __construct(string $temporaryDirectory, Unserializer $unserializer = null, VersionDetector $versionDetector = null)
    {
        $this->temporaryDirectory = $temporaryDirectory;
        $this->unserializer = $unserializer ?: new Unserializer();
        $this->versionDetector = $versionDetector ?: new VersionDetector();
    }

    public function parse(string $webroot): ReflectedVersion
    {
        $jsonFile = \tempnam($this->temporaryDirectory, 'xjs');
        try {
            $cmd = $this->buildCmdLine($webroot, $jsonFile);
            $rc = -1;
            \passthru($cmd, $rc);
            if ($rc !== 0) {
                throw new Exception('Extraction failed!');
            }

            return $this->unserializer->unserializeJsonFile($jsonFile);
        } finally {
            @\unlink($jsonFile);
        }
    }

    private function buildCmdLine(string $webroot, string $jsonFile): string
    {
        $cmd = \escapeshellarg($this->getPhpBinary($webroot));
        $extractor = \escapeshellarg(\dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'extractor' . \DIRECTORY_SEPARATOR . 'extract.php');
        $entryPoint = "{$webroot}/concrete/bin/concrete5.php";
        if (!\is_file($entryPoint)) {
            $entryPoint = "{$webroot}/concrete/bin/concrete5";
        }
        $useC5Exec = \is_file($entryPoint) && \is_file("{$webroot}/concrete/src/Console/Command/ExecCommand.php");
        if ($useC5Exec) {
            $cmd .= ' ' . \escapeshellarg(\str_replace('/', \DIRECTORY_SEPARATOR, $entryPoint));
            $cmd .= ' c5:exec';
            $cmd .= ' --no-interaction';
            $cmd .= ' --no-ansi';
            $cmd .= ' --';
            $cmd .= ' ' . $extractor;
        } else {
            $cmd .= ' ' . $extractor;
        }
        $cmd .= ' ' . \escapeshellarg($jsonFile);
        if (!$useC5Exec) {
            $cmd .= ' ' . \escapeshellarg($webroot);
        }

        return $cmd;
    }

    private function getPhpBinary(string $webroot): string
    {
        $version = $this->versionDetector->detectVersion($webroot);
        if (\version_compare($version, '8.999999.99999') > 0) {
            return PHP_BINARY;
        }
        $phpBinary = \getenv('C5VT_PHP5BIN');
        if (!$phpBinary) {
            throw new Exception('Please specify the C5VT_PHP5BIN environment variable, pointing to a PHP 5.5 binary');
        }

        return \str_replace('/', \DIRECTORY_SEPARATOR, $phpBinary);
    }
}
