<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

class Patches
{
    /**
     * @var array
     */
    private $byFile = [];

    public function isEmpty(): bool
    {
        return $this->byFile === [];
    }

    /**
     * @param \MLocati\C5SinceTagger\Diff\Patch $patch
     *
     * @return $this
     */
    public function add(Patch $patch): self
    {
        $file = $patch->getFile();
        $line = $patch->getLine();
        if (isset($this->byFile[$file])) {
            if (isset($this->byFile[$file][$line])) {
                throw new \Exception("Multiple patches for line {$line} of file {$file}");
            }
            $this->byFile[$file][$line] = $patch;
        } else {
            $this->byFile[$file] = [$line => $patch];
        }

        return $this;
    }

    /**
     * @param \MLocati\C5SinceTagger\Diff\Patches $patches
     *
     * @return $this
     */
    public function merge(self $patches): self
    {
        foreach ($patches->getFiles() as $file) {
            foreach ($patches->getFilePatches($file) as $patch) {
                $this->add($patch);
            }
        }

        return $this;
    }

    /**
     * Get the list of files to be patched.
     *
     * @return string[]
     */
    public function getFiles(): array
    {
        return \array_keys($this->byFile);
    }

    /**
     * Get the patches for a specific file.
     *
     * @param string $file
     *
     * @return \MLocati\C5SinceTagger\Diff\Patch[] Empty array if $file doesn't need any patch
     */
    public function getFilePatches(string $file): array
    {
        return isset($this->byFile[$file]) ? \array_values($this->byFile[$file]) : [];
    }
}
