<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class DiffGroupList
{
    /**
     * @var \MLocati\C5SinceTagger\Diff\DiffGroup[]
     */
    private $groups = [];

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $version
     *
     * @return $this
     */
    public function add(int $type, ReflectedVersion $version, string $vendorName = ''): self
    {
        if ($this->groups === []) {
            if ($type !== DiffGroup::TYPE_MISSING) {
                $this->groups[] = DiffGroup::create($type, $vendorName)->addVersion($version);
            }
        } else {
            $index = \count($this->groups) - 1;
            if ($this->groups[$index]->getType() !== $type || $this->groups[$index]->getVendorName() !== $vendorName) {
                $this->groups[++$index] = DiffGroup::create($type, $vendorName);
            }
            $this->groups[$index]->addVersion($version);
        }

        return $this;
    }

    public function getSince(): string
    {
        if ($this->groups === []) {
            throw new \Exception('No groups');
        }
        $lastIndex = \count($this->groups) - 1;
        if ($this->groups[$lastIndex]->getType() !== DiffGroup::TYPE_CORE) {
            throw new \Exception('The last group should be of type "core"');
        }
        $types = [];
        foreach ($this->groups as $group) {
            $types[] = $group->getType();
        }

        if (\in_array(DiffGroup::TYPE_MISSING, $types, true) === false) {
            return $this->groups[0]->getVersions()[0]->getName();
        }

        if ($types === [DiffGroup::TYPE_CORE, DiffGroup::TYPE_MISSING, DiffGroup::TYPE_CORE] && \count($this->groups[1]->getVersions()) < 4) {
            $notIn = [];
            foreach ($this->groups[1]->getVersions() as $version) {
                $notIn[] = $version->getName();
            }

            return $this->groups[0]->getVersions()[0]->getName() . ' (but not in ' . \implode(', ', $notIn) . ')';
        }

        foreach ($this->groups as $groupIndex => $group) {
            $versions = $group->getVersions();
            switch ($group->getType()) {
                case DiffGroup::TYPE_MISSING:
                    $list[] = $versions[0]->getName() . ' removed';
                    break;
                case DiffGroup::TYPE_VENDOR:
                    $list[] = $versions[0]->getName() . ' defined by ' . $group->getVendorName();
                    break;
                case DiffGroup::TYPE_CORE:
                    $list[] = $versions[0]->getName() . ($groupIndex === 0 ? '' : ' re-implemented');
                    break;
                default:
                    throw new \Exception('Unsupported diff group type');
            }
        }

        return \implode("\n", $list);
    }
}
