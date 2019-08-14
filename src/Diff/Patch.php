<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

class Patch
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var int
     */
    private $line;

    /**
     * @var string
     */
    private $oldSince;

    /**
     * @var string
     */
    private $newSince;

    public function __construct(string $file, int $line, string $oldSince, string $newSince)
    {
        $this->file = $file;
        $this->line = $line;
        $this->oldSince = $oldSince;
        $this->newSince = $newSince;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getOldSince(): string
    {
        return $this->oldSince;
    }

    /**
     * @return string
     */
    public function getNewSince(): string
    {
        return $this->newSince;
    }
}
