<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\CoreVersion;

use Exception;
use GuzzleHttp\Client;
use MLocati\C5SinceTagger\Filesystem;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class VersionStorage
{
    /**
     * @var string
     */
    private $temporaryDirectory;

    /**
     * @var string
     */
    private $versionsDirectory;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \MLocati\C5SinceTagger\Filesystem
     */
    private $fs;

    /**
     * @param string $temporaryDirectory the parent temporary directory, with '/' as directory separator, without leading '/'
     */
    public function __construct(string $temporaryDirectory, OutputInterface $output = null)
    {
        $this->fs = new Filesystem();
        $this->output = $output ?: new NullOutput();
        $this->temporaryDirectory = $temporaryDirectory;
        $this->versionsDirectory = "{$this->temporaryDirectory}/versions";
        $this->fs->ensureDirectory($this->versionsDirectory);
    }

    public function ensure(string $version, string $remoteUrl, bool $force = false): string
    {
        $dir = $this->getVersionDirectory($version);
        if ($force === true || !\is_dir($dir)) {
            $this->download($version, $remoteUrl, $dir);
        }

        return $dir;
    }

    private function download(string $version, string $remoteUrl, string $dir): void
    {
        $tempDir = $this->fetchAndExtractVersion($remoteUrl);
        try {
            $this->finalizeDirectory($tempDir);
            if (\is_dir($dir)) {
                $this->fs->deleteDirectory($dir);
            }
            $this->fs->rename($tempDir, $dir);
            $tempDir = null;
        } finally {
            if ($tempDir !== null) {
                try {
                    $this->fs->deleteDirectory($tempDir);
                } catch (Throwable $x) {
                }
            }
        }
    }

    private function getVersionDirectory(string $version): string
    {
        return "{$this->versionsDirectory}/{$version}";
    }

    private function fetchAndExtractVersion(string $remoteUrl): string
    {
        $zipFile = $this->fetchVersion($remoteUrl);
        try {
            return $this->extractVersion($zipFile);
        } finally {
            try {
                $this->fs->deleteFile($zipFile);
            } catch (Throwable $x) {
            }
        }
    }

    private function fetchVersion(string $remoteUrl): string
    {
        $this->output->write("Downloading {$remoteUrl}... ");
        $tempFile = \tempnam($this->temporaryDirectory, 'vsn');
        if ($tempFile === false) {
            throw new Exception('Failed to create a temporary directory');
        }
        try {
            $tempFileHandle = \fopen($tempFile, 'w');
            if ($tempFileHandle === false) {
                throw new Exception('Failed to open a temporary file');
            }
            try {
                $client = new Client();
                $response = $client->get($remoteUrl, ['save_to' => $tempFileHandle, 'decode_content' => false]);
                \fclose($tempFileHandle);
                $tempFileHandle = null;
                /* @var \GuzzleHttp\Psr7\Response $response */
                if ($response->getStatusCode() !== 200) {
                    throw new Exception($response->getReasonPhrase());
                }
                $result = $tempFile;
                $tempFile = null;
                $this->output->writeln('ok.');

                return $result;
            } finally {
                if ($tempFileHandle !== null) {
                    \fclose($tempFileHandle);
                }
            }
        } finally {
            if ($tempFile !== null) {
                try {
                    $this->fs->deleteFile($tempFile);
                } catch (Throwable $x) {
                }
            }
        }
    }

    private function extractVersion(string $zipFile): string
    {
        for ($i = 0;; ++$i) {
            $dir = "{$this->temporaryDirectory}/unzipped{$i}";
            if (!\file_exists($dir) && @\mkdir($dir)) {
                break;
            }
            if ($i > 1000) {
                throw new Exception('Failed to create a temporary directory');
            }
        }
        try {
            $this->output->write('Decompressing archive... ');
            $cmd = 'unzip';
            $cmd .= ' -o'; // overwrite files WITHOUT prompting
            $cmd .= ' -q'; // quiet mode, to avoid overflow of stdout
            $cmd .= ' ' . \escapeshellarg(\str_replace('/', \DIRECTORY_SEPARATOR, $zipFile)); // file to extract
            $cmd .= ' -d ' . \escapeshellarg(\str_replace('/', \DIRECTORY_SEPARATOR, $dir)); // destination directory
            $rc = 1;
            $output = [];
            @\exec($cmd . ' 2>&1', $output, $rc);
            if ($rc !== 0) {
                $error = \trim(\implode("\n", $output)) ?: t('Unknown error decompressing a ZIP archive');
                throw new Exception($error);
            }
            $this->output->writeln('ok.');
            $dir = $this->ensureRootDirectory($dir);
            $result = $dir;
            $dir = null;

            return $result;
        } finally {
            if ($dir !== null) {
                try {
                    $this->fs->deleteDirectory($dir);
                } catch (Throwable $x) {
                }
            }
        }
    }

    private function ensureRootDirectory(string $dir): string
    {
        $singleDir = null;
        $hdir = \opendir($dir);
        if (!$hdir) {
            throw new Exception('Failed to open a temporary directory');
        }
        try {
            while (($item = \readdir($hdir)) !== false) {
                if ($item !== '.' && $item !== '..') {
                    $itemPath = "{$dir}/{$item}";
                    if (!\is_dir($itemPath) || $singleDir !== null) {
                        $singleDir = false;
                        break;
                    }
                    $singleDir = $itemPath;
                }
            }
        } finally {
            \closedir($hdir);
        }
        if ($itemPath === null) {
            throw new Exception('The temporary directory is empty');
        }
        if ($itemPath === false) {
            return $dir;
        }
        for ($i = 0;; ++$i) {
            $newDir = "{$this->temporaryDirectory}/unzipped_renamed_{$i}";
            if (!\file_exists($newDir) && @\rename($singleDir, $newDir)) {
                $this->fs->deleteDirectory($dir);

                return $newDir;
            }
            if ($i > 1000) {
                throw new Exception('Failed to create a temporary directory');
            }
        }
    }

    private function finalizeDirectory(string $dir): void
    {
        if (\is_dir("{$dir}/concrete/vendor")) {
            return;
        }
        $this->output->write('Installing composer dependencies... ');
        $cmd = 'composer';
        $cmd .= ' --no-interaction'; // Do not ask any interactive question
        $cmd .= ' --no-ansi'; // Disable ANSI output
        $cmd .= ' ' . \escapeshellarg('--working-dir=' . \str_replace('/', \DIRECTORY_SEPARATOR, $dir));
        $cmd .= ' install'; // Installs the project dependencies from the composer.lock file if present, or falls back on the composer.json
        $cmd .= ' --optimize-autoloader'; // Optimize autoloader during autoloader dump
        $rc = 1;
        $output = [];
        @\exec($cmd . ' 2>&1', $output, $rc);
        if ($rc !== 0) {
            $error = \trim(\implode("\n", $output)) ?: t('Unknown error decompressing a ZIP archive');
            throw new Exception($error);
        }
        $this->output->writeln('ok.');
    }
}
