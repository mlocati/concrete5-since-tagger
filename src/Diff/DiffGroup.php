<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class DiffGroup
{
    /**
     * @var int
     */
    public const TYPE_MISSING = 1;

    /**
     * @var int
     */
    public const TYPE_CORE = 2;

    /**
     * @var int
     */
    public const TYPE_VENDOR = 3;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $vendorName;

    /**
     * @var string
     */
    private $visibility;

    /**
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion[]
     */
    private $versions = [];

    private function __construct()
    {
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public static function create(int $type, string $vendorName = '', string $visibility = ''): self
    {
        if ($type === static::TYPE_VENDOR) {
            if ($vendorName === '') {
                throw new \Exception('Missing vendor name');
            }
        } else {
            if ($vendorName !== '') {
                throw new \Exception('Not vendor, but with vendor name?');
            }
        }
        $result = new static();
        $result->type = $type;
        $result->vendorName = $vendorName;
        $result->visibility = $visibility;

        return $result;
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $version
     *
     * @return $this
     */
    public function addVersion(ReflectedVersion $version): self
    {
        $this->versions[] = $version;

        return $this;
    }

    /**
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedVersion[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }
}
