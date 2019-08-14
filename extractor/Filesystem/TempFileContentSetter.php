<?php

namespace MLocati\C5SinceTagger\Extractor\Filesystem;

class TempFileContentSetter
{
    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var string|null
     */
    private $originalContent;

    public function __construct()
    {
        \register_shutdown_function([$this, 'revert']);
    }

    /**
     * @param string $filename
     * @param string $newContent
     * @param string|null $originalContent
     */
    public function setContent($filename, $newContent, $originalContent = null)
    {
        if ($originalContent === null) {
            $originalContent = \file_get_contents($filename);
            if ($originalContent === false) {
                throw new \Exception("Failed to read the content of {$filename}");
            }
        }
        $this->revert();
        if (!\file_put_contents($filename, $newContent)) {
            throw new \Exception('Failed to write to a temporary file');
        }
        $this->filename = $filename;
        $this->originalContent = $originalContent;
    }

    public function revert()
    {
        $filename = $this->filename;
        $originalContent = $this->originalContent;
        $this->filename = null;
        $this->originalContent = null;
        if ($filename !== null && $originalContent !== null) {
            try {
                @\file_put_contents($filename, $originalContent);
            } catch (\Exception $x) {
            } catch (\Throwable $x) {
            }
        }
    }
}
