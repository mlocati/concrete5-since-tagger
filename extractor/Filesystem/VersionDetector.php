<?php

namespace MLocati\C5SinceTagger\Extractor\Filesystem;

class VersionDetector
{
    /**
     * @param string $webroot
     *
     * @return string|null
     */
    public function detectVersion($webroot)
    {
        if (\is_file("{$webroot}/concrete/config/concrete.php")) {
            $data = require "{$webroot}/concrete/config/concrete.php";
            $data = \is_array($data) && isset($data['version']) ? $data['version'] : null;
            if (\is_string($data)) {
                return $data;
            }
        }

        return null;
    }
}
