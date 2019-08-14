<?php

namespace MLocati\C5SinceTagger\Extractor\Filesystem;

class FileLister
{
    /**
     * @var string
     */
    private $webroot;

    /**
     * @var string
     */
    private $version;

    /**
     * @param string $webroot normalized path to the webroot
     * @param string $version the actual core version
     */
    public function __construct($webroot, $version)
    {
        $this->webroot = $webroot;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param bool $forInclude
     *
     * @return \Generator|string[]
     */
    public function listFiles($forInclude)
    {
        foreach ($this->listFilesIn($forInclude, '') as $fileRelativePath) {
            yield $fileRelativePath;
        }
    }

    /**
     * @param bool $forInclude
     * @param string $directoryRelPath
     *
     * @return \Generator|string[]
     */
    public function listFilesIn($forInclude, $directoryRelativePath)
    {
        if ($this->shouldParseDirectory($forInclude, $directoryRelativePath) === false) {
            return;
        }
        $prefix = $directoryRelativePath === '' ? '' : "{$directoryRelativePath}/";
        $directoryHandle = \opendir($this->webroot . '/' . $directoryRelativePath);
        if (!$directoryHandle) {
            throw new \Exception("Failed to open the directory {$directoryRelativePath}");
        }
        try {
            $subDirectoriesRelativePath = [];
            while (($itemName = \readdir($directoryHandle)) !== false) {
                if ($itemName !== '.' && $itemName !== '..') {
                    $itemRelativePath = $prefix . $itemName;
                    if (\is_dir($this->webroot . '/' . $itemRelativePath)) {
                        $subDirectoriesRelativePath[] = $itemRelativePath;
                    } elseif ($this->shouldParseFile($forInclude, $itemRelativePath)) {
                        yield $itemRelativePath;
                    }
                }
            }
        } finally {
            \closedir($directoryHandle);
        }
        foreach ($subDirectoriesRelativePath as $subDirectoryRelativePath) {
            foreach ($this->listFilesIn($forInclude, $subDirectoryRelativePath) as $fileRelativePath) {
                yield $fileRelativePath;
            }
        }
    }

    /**
     * @param bool $forInclude
     * @param string $relativePath
     *
     * @return bool
     */
    private function shouldParseDirectory($forInclude, $relativePath)
    {
        if (\strpbrk($relativePath, '.') === 0) {
            return false;
        }
        if (\in_array($relativePath, [
            '',
            'concrete',
        ], true)) {
            return true;
        }
        if (\strpos($relativePath, 'concrete/') !== 0) {
            return false;
        }
        if (\in_array($relativePath, [
            'concrete/bin',
            'concrete/config',
            'concrete/css',
            'concrete/elements',
            'concrete/images',
            'concrete/js',
            'concrete/mail',
            'concrete/routes',
            'concrete/single_pages',
            'concrete/tools',
            'concrete/vendor',
            'concrete/views',
        ], true)) {
            return false;
        }
        if ($forInclude) {
            if (\in_array($relativePath, [
                'concrete/bootstrap',
            ], true)) {
                return false;
            }
        }
        if (\preg_match('%^concrete/blocks/\w+/(form|templates|src|tools)$%', $relativePath)) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $forInclude
     * @param string $relativePath
     *
     * @return bool
     */
    private function shouldParseFile($forInclude, $relativePath)
    {
        $m = null;
        if (!\preg_match('%^(?:(.+)/)?([^/]+\.php)$%i', $relativePath, $m)) {
            return false;
        }
        if (!$this->shouldParseDirectory($forInclude, $m[1])) {
            return false;
        }
        if (\strpos($m[2], '.') === 0) {
            return false;
        }
        if (\preg_match('%^concrete/(attributes|authentication|blocks|geolocation)/\w+/%', $relativePath)) {
            return (bool) \preg_match('%^concrete/(attributes|authentication|blocks|geolocation)/\w+/controller\.php%', $relativePath);
        }
        if (\preg_match('%^concrete/themes/\w+/%', $relativePath)) {
            return (bool) \preg_match('%^concrete/themes/\w+/page_theme\.php%', $relativePath);
        }
        switch ($relativePath) {
            case 'index.php':
            case 'concrete/dispatcher.php':
            case 'concrete/src/Support/.phpstorm.meta.php':
            case 'concrete/src/Support/__IDE_SYMBOLS__.php':
                return false;
            case 'concrete/src/Attribute/ExpressSetManager.php':
                switch ($this->version) {
                    case '8.0.1':
                        // Class Concrete\Core\Attribute\ExpressSetManager contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Concrete\Core\Attribute\SetManagerInterface::updateAttributeSetDisplayOrder)
                        return false;
                }
                break;
            case 'concrete/controllers/element/site/tree_selector.php':
                switch ($this->version) {
                    case '8.2.0':
                        // Cannot redeclare class Concrete\Controller\Element\Search\CustomizeResults
                        return false;
                }
                break;
        }

        return true;
    }
}
