<?php

namespace MLocati\C5SinceTagger\Extractor\Filesystem;

class FileLoader
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
     * @var \MLocati\C5SinceTagger\Extractor\Filesystem\TempFileContentSetter|null
     */
    private $tempFileContentSetter;

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
     * @return \MLocati\C5SinceTagger\Extractor\Filesystem\TempFileContentSetter
     */
    private function getTempFileContentSetter()
    {
        if ($this->tempFileContentSetter === null) {
            $this->tempFileContentSetter = new TempFileContentSetter();
        }

        return $this->tempFileContentSetter;
    }

    /**
     * @return int
     */
    public function loadAllFiles()
    {
        switch ($this->version) {
            case '5.7.3':
            case '5.7.3.1':
                $this->loadFile('concrete/src/Page/Page.php');
                break;
            case '8.0.0':
            case '8.0.1':
            case '8.0.2':
                $this->loadFile('concrete/src/Permission/Registry/Entry/Access/Entity/EntityInterface.php');
                break;
        }
        $fileLister = new FileLister($this->webroot, $this->version);
        $count = 0;
        foreach ($fileLister->listFiles(true) as $file) {
            $this->loadFile($file);
            $count++;
        }

        return $count;
    }

    /**
     * @param string $relpath
     *
     * @return bool
     */
    public function loadFile($relpath)
    {
        $patches = $this->getFilePatches($relpath);
        if ($patches !== null) {
            $this->loadFileWithPatches($relpath, $patches);
        } else {
            require_once "{$this->webroot}/{$relpath}";
        }

        return true;
    }

    /**
     * @param string $relpath
     *
     * @return string[]|null
     */
    private function getFilePatches($relpath)
    {
        switch ($relpath) {
            case 'concrete/controllers/backend/user_attributes.php':
                if (\version_compare($this->version, '5.7.1') <= 0) {
                    return ['use' => ['User']];
                }
                break;
            case 'concrete/controllers/dialog/block/edit.php':
                if (\version_compare($this->version, '5.7.1') >= 0 && \version_compare($this->version, '5.7.5.13') <= 0) {
                    return ['use' => ['Concrete\Core\Cache\Cache']];
                }
                break;
            case 'concrete/controllers/panel/sitemap.php':
                if (\version_compare($this->version, '5.7.5.13') <= 0) {
                    return ['use' => ['Page']];
                }
                break;
            case 'concrete/controllers/single_page/dashboard/blocks/stacks.php':
                if (\version_compare($this->version, '8.5.1') <= 0) {
                    return ['use' => ['Permissions']];
                }
                break;
            case 'concrete/src/Attribute/ExpressSetManager.php':
                if (\version_compare($this->version, '8.0.1') === 0) {
                    return ['rx' => ['/^class ExpressSetManager\b/m' => 'abstract class ExpressSetManager']];
                }
                break;
            case 'concrete/src/Database/CharacterSetCollation/Manager.php':
                if (\version_compare($this->version, '8.5.0') >= 0 && \version_compare($this->version, '8.5.0') <= 0) {
                    return ['use' => ['Exception']];
                }
                break;
            case 'concrete/src/Import/Item/Express/Control/AttributeKeyControl.php':
                if (\version_compare($this->version, '8.0.0') >= 0 && \version_compare($this->version, '8.0.2') <= 0) {
                    return ['use' => ['Concrete\Core\Entity\Express\Control\AssociationControl']];
                }
                break;
            case 'concrete/src/Page/Page.php':
                if (\version_compare($this->version, '5.7.3') >= 0 && \version_compare($this->version, '5.7.3.1') <= 0) {
                    return ['use' => ['Concrete\Core\Multilingual\Page\Event']];
                }
                break;
            case 'concrete/src/Permission/Registry/Entry/Access/Entity/EntityInterface.php':
                if (\version_compare($this->version, '8.0.0') >= 0 && \version_compare($this->version, '8.0.2') <= 0) {
                    return ['use' => ['Concrete\Core\Permission\Access\Entity\Entity']];
                }
                break;
        }

        return null;
    }

    /**
     * @param string $relpath
     * @param string[] $patches
     */
    private function loadFileWithPatches($relpath, array $patches)
    {
        $abspath = "{$this->webroot}/{$relpath}";
        $originalContents = \file_get_contents($abspath);
        if (!$originalContents) {
            throw new \Exception("Failed to read file {$relpath}");
        }
        $contents = \str_replace("\r\n", "\n", $originalContents);
        if (isset($patches['rx'])) {
            foreach ($patches['rx'] as $search => $replace) {
                $contents = \preg_replace($search, $replace, $contents);
            }
        }
        if (isset($patches['use'])) {
            $start1 = \strpos($contents, "\nclass ");
            $start2 = \strpos($contents, "\ninterface ");
            if ($start1 === false && $start2 === false) {
                throw new \Exception("Failed to determine the beginning of a class/interface in {$relpath}");
            }
            if ($start1 !== false && $start2 !== false) {
                throw new \Exception("Failed to determine the beginning of a class/interface in {$relpath}");
            }
            $classStart = $start1 === false ? $start2 : $start1;
            $start1 = \strpos($contents, "\nclass ", $classStart + 1);
            $start2 = \strpos($contents, "\ninterface ", $classStart + 1);
            if ($start1 !== false || $start2 !== false) {
                throw new \Exception("Failed to determine the beginning of a class/interface in {$relpath}");
            }
            $before = \substr($contents, 0, $classStart);
            $after = \substr($contents, $classStart);
            $m = null;
            foreach ($patches['use'] as $aliasToFix) {
                if (\preg_match('/^.*\\\\(\w+)$/', $aliasToFix, $m)) {
                    $fqn = $aliasToFix;
                    $className = $m[1];
                } else {
                    $fqn = '';
                    $className = $aliasToFix;
                }
                if ($fqn === '') {
                    $before = \preg_replace('/(\nuse ' . \preg_quote($className, '/') . ');/', '\1 as ' . $className . 'C5VT_REMOVE_ME;', $before);
                } else {
                    $before = \preg_replace('/(\nuse ' . \preg_quote($fqn, '/') . ');/', '\1 as ' . $className . 'C5VT_REMOVE_ME;', $before);
                }
                $after = \preg_replace('/\b(' . \preg_quote($className, '/') . ')\b/', '\1C5VT_REMOVE_ME', $after);
            }
            $contents = $before . $after;
        }
        $this->getTempFileContentSetter()->setContent($abspath, $contents, $originalContents);
        try {
            require_once $abspath;
            $this->tempFileContentSetter->revert();
        } catch (\Exception $x) {
            $this->tempFileContentSetter->revert();
            throw $x;
        } catch (\Throwable $x) {
            $this->tempFileContentSetter->revert();
            throw $x;
        }
    }
}
