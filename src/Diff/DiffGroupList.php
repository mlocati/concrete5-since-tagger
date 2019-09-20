<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

use MLocati\C5SinceTagger\Interfaces\VisibilityInterface;
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
    public function add(int $type, ReflectedVersion $version, string $vendorName = '', string $visibility = ''): self
    {
        if ($this->groups === []) {
            if ($type !== DiffGroup::TYPE_MISSING) {
                $this->groups[] = DiffGroup::create($type, $vendorName, $visibility)->addVersion($version);
            }
        } else {
            $index = \count($this->groups) - 1;
            if ($this->groups[$index]->getType() !== $type || $this->groups[$index]->getVendorName() !== $vendorName || $this->groups[$index]->getVisibility() !== $visibility) {
                $this->groups[++$index] = DiffGroup::create($type, $vendorName, $visibility);
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
        $visibilities = $this->getDistinctVisibilities();
        if ($visibilities === [VisibilityInterface::PRIVATE]) {
            return '';
        }
        $showVisibilities = \count($visibilities) > 1;
        $lastIndex = \count($this->groups) - 1;
        if ($this->groups[$lastIndex]->getType() !== DiffGroup::TYPE_CORE) {
            throw new \Exception('The last group should be of type "core"');
        }
        $types = [];
        foreach ($this->groups as $group) {
            $types[] = $group->getType();
        }

        if (\in_array(DiffGroup::TYPE_MISSING, $types, true) === false && $showVisibilities === false) {
            return $this->groups[0]->getVersions()[0]->getName();
        }

        if ($types === [DiffGroup::TYPE_CORE, DiffGroup::TYPE_MISSING, DiffGroup::TYPE_CORE] && \count($this->groups[1]->getVersions()) < 4 && $showVisibilities === false) {
            $notIn = [];
            foreach ($this->groups[1]->getVersions() as $version) {
                $notIn[] = $version->getName();
            }

            return $this->groups[0]->getVersions()[0]->getName() . ' (but not in ' . \implode(', ', $notIn) . ')';
        }

        $previousVisibility = '';
        $previousType = null;
        foreach ($this->groups as $group) {
            $versions = $group->getVersions();
            $flags = [];
            if ($group->getType() === DiffGroup::TYPE_MISSING) {
                $flags[] = 'removed';
            } else {
                $flags = [];
                switch ($group->getType()) {
                    case DiffGroup::TYPE_VENDOR:
                        $flags[] = 'defined by ' . $group->getVendorName();
                        break;
                    case DiffGroup::TYPE_CORE:
                        if ($previousType !== null && $previousType !== DiffGroup::TYPE_CORE) {
                            $flags[] = 're-implemented';
                        }
                        break;
                    default:
                        throw new \Exception('Unsupported diff group type');
                }
                if ($showVisibilities) {
                    $visibility = $group->getVisibility();
                    if ($visibility !== $previousVisibility) {
                        $flags[] = 'visibility: ' . $visibility;
                    }
                }
            }
            if ($flags === []) {
                $list[] = $versions[0]->getName();
            } else {
                $list[] = $versions[0]->getName() . ' ' . \implode(', ', $flags);
            }
            $previousType = $group->getType();
        }

        return \implode("\n", $list);
    }

    private function getDistinctVisibilities(): array
    {
        $allVisibilities = [];
        foreach ($this->groups as $group) {
            $visibility = $group->getVisibility();
            if ($visibility !== '') {
                $allVisibilities[] = $visibility;
            }
        }

        return \array_values(\array_unique($allVisibilities));
    }
}
