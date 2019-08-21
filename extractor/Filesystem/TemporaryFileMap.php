<?php

namespace MLocati\C5SinceTagger\Extractor\Filesystem;

class TemporaryFileMap
{
    /**
     * @var string
     */
    private $temporaryDirectory;

    /**
     * @var array
     */
    private $map;

    /**
     * @var callable|null
     */
    private $previousErrorHandler;

    /**
     * @param string $temporaryDirectory
     */
    public function __construct($temporaryDirectory)
    {
        $this->temporaryDirectory = $temporaryDirectory;
        $this->map = [];
        $errnos = 0
            + (\defined('E_ERROR') ? E_ERROR : 0)
            + (\defined('E_PARSE') ? E_PARSE : 0)
            + (\defined('E_CORE_ERROR') ? E_CORE_ERROR : 0)
            + (\defined('E_COMPILE_ERROR') ? E_COMPILE_ERROR : 0)
            + (\defined('E_COMPILE_WARNING') ? E_COMPILE_WARNING : 0)
        ;
        $this->previousErrorHandler = \set_error_handler([$this, 'handleError'], $errnos);
        \register_shutdown_function([$this, 'reset']);
    }

    public function __destruct()
    {
        $this->reset();
    }

    /**
     * @param string $relativeFilePath
     *
     * @return string
     */
    public function add($relativeFilePath)
    {
        $absoluteFilePath = \tempnam($this->temporaryDirectory, 'tfm');
        if ($absoluteFilePath === false) {
            throw new \Exception('Failed to create a temporary file');
        }
        $absoluteFilePath = \str_replace(\DIRECTORY_SEPARATOR, '/', $absoluteFilePath);
        $this->map[$absoluteFilePath] = $relativeFilePath;

        return $absoluteFilePath;
    }

    /**
     * @param string $absoluteFilePath
     */
    public function getMapped($absoluteFilePath)
    {
        $absoluteFilePath = \str_replace(\DIRECTORY_SEPARATOR, '/', $absoluteFilePath);

        return isset($this->map[$absoluteFilePath]) ? $this->map[$absoluteFilePath] : '';
    }

    /**
     * @param string $relativeFilePath
     *
     * @return bool
     */
    public function isMapped($relativeFilePath)
    {
        return \in_array($relativeFilePath, $this->map, true);
    }

    public function reset()
    {
        $absFiles = \array_keys($this->map);
        $this->map = [];
        foreach ($absFiles as $absFile) {
            if (\is_file($absFile)) {
                @\unlink($absFile);
            }
        }
    }

    public function handleError($errno, $errstr, $errfile = null, $errline = null, $context = [])
    {
        $this->reset();
        if ($this->previousErrorHandler) {
            return \call_user_func($this->previousErrorHandler, $errno, $errstr, $errfile, $errline, $context);
        }

        return false;
    }
}
