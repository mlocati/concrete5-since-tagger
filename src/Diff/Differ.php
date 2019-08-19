<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

use MLocati\C5SinceTagger\Reflected\ReflectedClass;
use MLocati\C5SinceTagger\Reflected\ReflectedInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedTrait;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class Differ
{
    /**
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    private $baseVersion;

    /**
     * Sorted from the newer to the older.
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion[]
     */
    private $previousVersions;

    /**
     * @var callable|null
     */
    private $progressInitHandler;

    /**
     * @var callable|null
     */
    private $progressProcessHandler;

    /**
     * @var callable|null
     */
    private $progressCompletedHandler;

    /**
     * @var array
     */
    private $interfaceMaps = [];

    /**
     * @var array
     */
    private $classMaps = [];

    /**
     * @var array
     */
    private $traitMaps = [];

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $baseVersion
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion[] $previousVersions
     */
    public function __construct(ReflectedVersion $baseVersion, array $previousVersions)
    {
        if ($previousVersions === []) {
            throw new \Exception('No previous versions specified');
        }
        \usort($previousVersions, function (ReflectedVersion $a, ReflectedVersion $b): int {
            return \version_compare($b->getName(), $a->getName());
        });
        if (\version_compare($baseVersion->getName(), $previousVersions[0]->getName()) <= 0) {
            throw new \Exception('The base versions is not newer that the previous versions');
        }
        $this->baseVersion = $baseVersion;
        $this->previousVersions = $previousVersions;
    }

    /**
     * @param callable|null $value
     *
     * @return $this
     */
    public function setProgressInitHandler(callable $value = null): self
    {
        $this->progressInitHandler = $value;

        return $this;
    }

    /**
     * @param callable|null $value
     *
     * @return $this
     */
    public function setProgressProcessHandler(callable $value = null): self
    {
        $this->progressProcessHandler = $value;

        return $this;
    }

    /**
     * @param callable|null $value
     *
     * @return $this
     */
    public function setProgressCompletedHandler(callable $value = null): self
    {
        $this->progressCompletedHandler = $value;

        return $this;
    }

    public function getPatches(): Patches
    {
        $result = new Patches();
        if ($this->progressInitHandler !== null) {
            \call_user_func(
                $this->progressInitHandler,
                $this->baseVersion->getGlobalConstants()->count()
                + $this->baseVersion->getGlobalFunctions()->count()
                + $this->baseVersion->getInterfaces()->count()
                + $this->baseVersion->getClasses()->count()
                + $this->baseVersion->getTraits()->count()
            );
        }
        $this->analyzeGlobalConstants($result);
        $this->analyzeGlobalFunctions($result);
        $this->analyzeInterfaces($result);
        $this->analyzeClasses($result);
        $this->analyzeTraits($result);

        if ($this->progressCompletedHandler !== null) {
            \call_user_func($this->progressCompletedHandler);
        }

        return $result;
    }

    private function analyzeGlobalConstants(Patches $patches): void
    {
        $baseMap = [];
        foreach ($this->baseVersion->getGlobalConstants() as $item) {
            $baseMap[$item->getName()] = $item;
        }
        $previousMaps = [];
        foreach ($this->previousVersions as $index => $previousVersion) {
            $previousMaps[$index] = [];
            foreach ($previousVersion->getGlobalConstants() as $item) {
                $previousMaps[$index][$item->getName()] = $item;
            }
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeGlobalFunctions(Patches $patches): void
    {
        $baseMap = [];
        foreach ($this->baseVersion->getGlobalFunctions() as $item) {
            $baseMap[\strtolower($item->getName())] = $item;
        }
        $previousMaps = [];
        foreach ($this->previousVersions as $index => $previousVersion) {
            $previousMaps[$index] = [];
            foreach ($previousVersion->getGlobalFunctions() as $item) {
                $previousMaps[$index][\strtolower($item->getName())] = $item;
            }
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeInterfaces(Patches $patches): void
    {
        $baseMap = $this->getInterfaceMap(null);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getInterfaceMap($index);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeClasses(Patches $patches): void
    {
        $baseMap = $this->getClassMap(null);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getClassMap($index);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeTraits(Patches $patches): void
    {
        $baseMap = $this->getTraitMap(null);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getTraitMap($index);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeMaps(Patches $patches, array $baseMap, array $previousMaps, ?string $parentSince = null): void
    {
        foreach ($baseMap as $normalizedName => $item) {
            if ($parentSince === null && $this->progressProcessHandler !== null) {
                \call_user_func($this->progressProcessHandler);
            }
            if ($item->isVendor()) {
                continue;
            }
            $prevItems = [];
            foreach (\array_keys($this->previousVersions) as $index) {
                if (isset($previousMaps[$index][$normalizedName])) {
                    $prevItems[$index] = $previousMaps[$index][$normalizedName];
                } else {
                    $prevItems[$index] = null;
                }
            }
            if (\in_array(null, $prevItems, true)) {
                $since = $this->getDiffGroups($prevItems)->getSince();
            } else {
                $since = '';
            }
            if ($since === $parentSince) {
                $since = '';
            }
            if ($item->getSincePhpDoc() !== $since) {
                $patches->add(new Patch($item->getDefinedAtFile(), $item->getDefinedAtLine(), $item->getSincePhpDoc(), $since));
            }
            if ($item instanceof ReflectedInterface || $item instanceof ReflectedClass) {
                $this->analyzeICConstants($patches, $item, $prevItems, $since);
            }
            if ($item instanceof ReflectedClass || $item instanceof ReflectedTrait) {
                $this->analyzeCTProperties($patches, $item, $prevItems, $since);
            }
            if ($item instanceof ReflectedInterface || $item instanceof ReflectedClass || $item instanceof ReflectedTrait) {
                $this->analyzeICTMethods($patches, $item, $prevItems, $since);
            }
        }
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface|\MLocati\C5SinceTagger\Reflected\ReflectedClass $item
     */
    private function analyzeICConstants(Patches $patches, object $item, array $prevItems, string $parentSince): void
    {
        $baseMap = [];
        foreach ($item->getConstants() as $item) {
            $baseMap[$item->getName()] = $item;
        }
        if ($baseMap === []) {
            return;
        }
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $prevItems[$index] === null ? [] : $this->expandICConstants($prevItems[$index]);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps, $parentSince);
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface|\MLocati\C5SinceTagger\Reflected\ReflectedClass $item
     */
    private function expandICConstants(object $item): array
    {
        if ($item instanceof ReflectedInterface) {
            return $this->expandInterfaceConstants($item);
        }
        if ($item instanceof ReflectedClass) {
            return $this->expandClassConstants($item);
        }
        throw new \Exception(\get_class($item) . " doesn't have constants.");
    }

    private function expandInterfaceConstants(ReflectedInterface $item): array
    {
        $result = [];
        foreach ($item->getConstants() as $child) {
            $result[$child->getName()] = $child;
        }
        $map = $this->getInterfaceMap(\array_search($item->getVersion(), $this->previousVersions, true));
        foreach ($item->getParentInterfaces() as $parentConnection) {
            $lowerCaseName = \strtolower($parentConnection->getName());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandInterfaceConstants($map[$lowerCaseName]);
            }
        }

        return $result;
    }

    private function expandClassConstants(ReflectedClass $item): array
    {
        $result = [];
        foreach ($item->getConstants() as $child) {
            $result[$child->getName()] = $child;
        }
        $map = $this->getInterfaceMap(\array_search($item->getVersion(), $this->previousVersions, true));
        foreach ($item->getInterfaces() as $parentConnection) {
            $lowerCaseName = \strtolower($parentConnection->getInterface());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandInterfaceConstants($map[$lowerCaseName]);
            }
        }
        if ($item->getParentClassName() !== '') {
            $map = $this->getClassMap(\array_search($item->getVersion(), $this->previousVersions, true));
            $lowerCaseName = \strtolower($item->getParentClassName());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandClassConstants($map[$lowerCaseName]);
            }
        }

        return $result;
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass|\MLocati\C5SinceTagger\Reflected\ReflectedTrait $item
     */
    private function analyzeCTProperties(Patches $patches, object $item, array $prevItems, string $parentSince): void
    {
        $baseMap = [];
        foreach ($item->getProperties() as $item) {
            $baseMap[$item->getName()] = $item;
        }
        if ($baseMap === []) {
            return;
        }
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $prevItems[$index] === null ? [] : $this->expandCTProperties($prevItems[$index]);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps, $parentSince);
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass|\MLocati\C5SinceTagger\Reflected\ReflectedTrait $item
     */
    private function expandCTProperties(object $item): array
    {
        if ($item instanceof ReflectedClass) {
            return $this->expandClassProperties($item);
        }
        if ($item instanceof ReflectedTrait) {
            return $this->expandTraitProperties($item);
        }
        throw new \Exception(\get_class($item) . " doesn't have properties.");
    }

    private function expandClassProperties(ReflectedClass $item): array
    {
        $result = [];
        foreach ($item->getProperties() as $child) {
            $result[$child->getName()] = $child;
        }
        $map = $this->getTraitMap(\array_search($item->getVersion(), $this->previousVersions, true));
        foreach ($item->getTraits() as $child) {
            $lowerCaseName = \strtolower($child->getTrait());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandTraitProperties($map[$lowerCaseName]);
            }
        }
        if ($item->getParentClassName() !== '') {
            $map = $this->getClassMap(\array_search($item->getVersion(), $this->previousVersions, true));
            $lowerCaseName = \strtolower($item->getParentClassName());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandClassProperties($map[$lowerCaseName]);
            }
        }

        return $result;
    }

    private function expandTraitProperties(ReflectedTrait $item): array
    {
        $result = [];
        foreach ($item->getProperties() as $child) {
            $result[$child->getName()] = $child;
        }

        return $result;
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface|\MLocati\C5SinceTagger\Reflected\ReflectedClass|\MLocati\C5SinceTagger\Reflected\ReflectedTrait $item
     */
    private function analyzeICTMethods(Patches $patches, object $item, array $prevItems, string $parentSince): void
    {
        $baseMap = [];
        foreach ($item->getMethods() as $item) {
            $baseMap[\strtolower($item->getName())] = $item;
        }
        if ($baseMap === []) {
            return;
        }
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $prevItems[$index] === null ? [] : $this->expandICTMethods($prevItems[$index]);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps, $parentSince);
    }

    /**
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface|\MLocati\C5SinceTagger\Reflected\ReflectedClass|\MLocati\C5SinceTagger\Reflected\ReflectedTrait $item
     */
    private function expandICTMethods(object $item): array
    {
        if ($item instanceof ReflectedInterface) {
            return $this->expandInterfaceMethods($item);
        }
        if ($item instanceof ReflectedClass) {
            return $this->expandClassMethods($item);
        }
        if ($item instanceof ReflectedTrait) {
            return $this->expandTraitMethods($item);
        }
        throw new \Exception(\get_class($item) . " doesn't have properties.");
    }

    private function expandInterfaceMethods(ReflectedInterface $item): array
    {
        $result = [];
        foreach ($item->getMethods() as $child) {
            $result[\strtolower($child->getName())] = $child;
        }
        $map = $this->getInterfaceMap(\array_search($item->getVersion(), $this->previousVersions, true));
        foreach ($item->getParentInterfaces() as $parentConnection) {
            $lowerCaseName = \strtolower($parentConnection->getName());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandInterfaceMethods($map[$lowerCaseName]);
            }
        }

        return $result;
    }

    private function expandClassMethods(ReflectedClass $item): array
    {
        $result = [];
        foreach ($item->getMethods() as $child) {
            $result[\strtolower($child->getName())] = $child;
        }
        $map = $this->getTraitMap(\array_search($item->getVersion(), $this->previousVersions, true));
        foreach ($item->getTraits() as $child) {
            $lowerCaseName = \strtolower($child->getTrait());
            if (isset($map[$lowerCaseName])) {
                $traitResult = $this->expandTraitMethods($map[$lowerCaseName]);
                foreach ($child->getAliases() as $alias) {
                    $key = \strtolower($alias->getOriginalName());
                    if (isset($traitResult[$key])) {
                        $value = $traitResult[$key];
                        unset($traitResult[$key]);
                        $traitResult[\strtolower($alias->getAlias())] = $value;
                    }
                }
                $result += $traitResult;
            }
        }
        if ($item->getParentClassName() !== '') {
            $map = $this->getClassMap(\array_search($item->getVersion(), $this->previousVersions, true));
            $lowerCaseName = \strtolower($item->getParentClassName());
            if (isset($map[$lowerCaseName])) {
                $result += $this->expandClassMethods($map[$lowerCaseName]);
            }
        }

        return $result;
    }

    private function expandTraitMethods(ReflectedTrait $item): array
    {
        $result = [];
        foreach ($item->getMethods() as $child) {
            $result[\strtolower($child->getName())] = $child;
        }

        return $result;
    }

    private function getInterfaceMap(?int $versionIndex): array
    {
        $key = (string) $versionIndex;
        if (!isset($this->interfaceMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];
            foreach ($version->getInterfaces() as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->interfaceMaps[$key] = $map;
        }

        return $this->interfaceMaps[$key];
    }

    private function getClassMap(?int $versionIndex): array
    {
        $key = (string) $versionIndex;
        if (!isset($this->classMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];

            foreach ($version->getClassAliases() as $item) {
                $map[\strtolower($item->getAlias())] = $item->getActualClass();
            }
            foreach ($version->getClasses() as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->classMaps[$key] = $map;
        }

        return $this->classMaps[$key];
    }

    private function getTraitMap(?int $versionIndex): array
    {
        $key = (string) $versionIndex;
        if (!isset($this->traitMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];
            foreach ($version->getTraits() as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->traitMaps[$key] = $map;
        }

        return $this->traitMaps[$key];
    }

    private function getDiffGroups(array $prevItems): DiffGroupList
    {
        $diffGroupList = new DiffGroupList();
        for ($index = \count($this->previousVersions) - 1; $index >= 0; $index--) {
            if ($prevItems[$index] === null) {
                $diffGroupList->add(DiffGroup::TYPE_MISSING, $this->previousVersions[$index]);
            } else {
                $type = $prevItems[$index]->isVendor() ? DiffGroup::TYPE_VENDOR : DiffGroup::TYPE_CORE;
                $diffGroupList->add($type, $this->previousVersions[$index], $type === DiffGroup::TYPE_VENDOR ? $prevItems[$index]->getVendorName() : '');
            }
        }
        $diffGroupList->add(DiffGroup::TYPE_CORE, $this->baseVersion);

        return $diffGroupList;
    }
}
