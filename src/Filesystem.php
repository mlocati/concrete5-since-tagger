<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger;

class Filesystem
{
    public function rename(string $oldName, string $newName): void
    {
        if (@\rename($oldName, $newName) === false) {
            throw new \Exception("Failed to rename {$oldName} to {$newName}");
        }
    }

    public function ensureDirectory(string $dir): void
    {
        if (!@\is_dir($dir)) {
            if (!@\mkdir($dir)) {
                throw new \Exception("Failed to create directory {$dir}");
            }
        }
    }

    public function deleteFile(string $file): void
    {
        if (!\is_file($file) && !\is_link($file)) {
            throw new \Exception("File not found: {$file}");
        }
        for ($tries = 0; $tries < 3; $tries++) {
            if (@\unlink($file)) {
                return;
            }
        }
        throw new \Exception("Failed to delete file {$file}");
    }

    public function emptyDirectory(string $dir): void
    {
        $normalizedDir = \rtrim(\str_replace(\DIRECTORY_SEPARATOR, '/', $dir), '/');
        if ($normalizedDir === '') {
            throw new \Exception("Directory not found: {$dir}");
        }
        $this->doDeleteDirectory($normalizedDir, true);
    }

    public function deleteDirectory(string $dir): void
    {
        $normalizedDir = \rtrim(\str_replace(\DIRECTORY_SEPARATOR, '/', $dir), '/');
        if ($normalizedDir === '') {
            throw new \Exception("Directory not found: {$dir}");
        }
        $this->doDeleteDirectory($normalizedDir, false);
    }

    private function doDeleteDirectory(string $normalizedDir, bool $keep): void
    {
        if (!\is_dir($normalizedDir)) {
            throw new \Exception("Directory not found: {$normalizedDir}");
        }
        $subDirs = [];
        @$dirHandle = @\opendir($normalizedDir);
        if (!\is_resource($dirHandle)) {
            throw new \Exception("Failed to open directory {$normalizedDir}");
        }
        try {
            while (($item = \readdir($dirHandle)) !== false) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $normalizedDir . '/' . $item;
                if (\is_dir($path)) {
                    $subDirs[] = $path;
                } else {
                    $this->deleteFile($path);
                }
            }
        } finally {
            @\closedir($dirHandle);
        }
        foreach ($subDirs as $subDir) {
            $this->doDeleteDirectory($subDir, false);
        }
        if (!$keep) {
            for ($tries = 0; $tries < 3; $tries++) {
                if (@\rmdir($normalizedDir)) {
                    return;
                }
            }
            throw new \Exception("Failed to delete directory {$normalizedDir}");
        }
    }
}
