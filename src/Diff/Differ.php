<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

use Doctrine\Common\Collections\Criteria;
use MLocati\C5SinceTagger\Interfaces\VisibilityInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedClass;
use MLocati\C5SinceTagger\Reflected\ReflectedInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedTrait;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class Differ
{
    public const FLAG_ALL = -1;
    public const FLAG_GLOBALCONSTANTS = 0b1;
    public const FLAG_GLOBALFUNCTIONS = 0b10;
    public const FLAG_INTERFACES = 0b100;
    public const FLAG_CLASSES = 0b1000;
    public const FLAG_TRAITS = 0b10000;

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

    public function getPatches(int $flags = self::FLAG_ALL, string $start = '', string $end = ''): Patches
    {
        $result = new Patches();
        if ($this->progressInitHandler !== null) {
            \call_user_func(
                $this->progressInitHandler,
                0
                + ($flags & self::FLAG_GLOBALCONSTANTS ? $this->baseVersion->getGlobalConstants()->count() : 0)
                + ($flags & self::FLAG_GLOBALFUNCTIONS ? $this->baseVersion->getGlobalFunctions()->count() : 0)
                + ($flags & self::FLAG_INTERFACES ? $this->baseVersion->getInterfaces()->count() : 0)
                + ($flags & self::FLAG_CLASSES ? $this->baseVersion->getClasses()->count() : 0)
                + ($flags & self::FLAG_TRAITS ? $this->baseVersion->getTraits()->count() : 0)
            );
        }
        if ($flags & self::FLAG_GLOBALCONSTANTS) {
            $this->analyzeGlobalConstants($result, $start, $end);
        }
        if ($flags & self::FLAG_GLOBALFUNCTIONS) {
            $this->analyzeGlobalFunctions($result, $start, $end);
        }
        if ($flags & self::FLAG_INTERFACES) {
            $this->analyzeInterfaces($result, $start, $end);
        }
        if ($flags & self::FLAG_CLASSES) {
            $this->analyzeClasses($result, $start, $end);
        }
        if ($flags & self::FLAG_TRAITS) {
            $this->analyzeTraits($result, $start, $end);
        }
        if ($this->progressCompletedHandler !== null) {
            \call_user_func($this->progressCompletedHandler);
        }

        return $result;
    }

    private function analyzeGlobalConstants(Patches $patches, string $start, string $end): void
    {
        $criteria = $this->getStartEndCriteria($start, $end);
        $baseMap = [];
        foreach ($this->baseVersion->getGlobalConstants() as $item) {
            $baseMap[$item->getName()] = $item;
        }
        $baseMap = $this->filterBaseMap($baseMap, $start, $end, false);
        $previousMaps = [];
        foreach ($this->previousVersions as $index => $previousVersion) {
            $previousMaps[$index] = [];
            $list = $criteria === null ? $previousVersion->getGlobalConstants() : $previousVersion->getGlobalConstants()->matching($criteria);
            foreach ($list as $item) {
                $previousMaps[$index][$item->getName()] = $item;
            }
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeGlobalFunctions(Patches $patches, string $start, string $end): void
    {
        $criteria = $this->getStartEndCriteria($start, $end);
        $baseMap = [];
        foreach ($this->baseVersion->getGlobalFunctions() as $item) {
            $baseMap[\strtolower($item->getName())] = $item;
        }
        $baseMap = $this->filterBaseMap($baseMap, $start, $end, true);
        $previousMaps = [];
        foreach ($this->previousVersions as $index => $previousVersion) {
            $previousMaps[$index] = [];
            $list = $criteria === null ? $previousVersion->getGlobalFunctions() : $previousVersion->getGlobalFunctions()->matching($criteria);
            foreach ($list as $item) {
                $previousMaps[$index][\strtolower($item->getName())] = $item;
            }
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeInterfaces(Patches $patches, string $start, string $end): void
    {
        $baseMap = $this->getInterfaceMap(null, $start, $end);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getInterfaceMap($index, $start, $end);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeClasses(Patches $patches, string $start, string $end): void
    {
        $baseMap = $this->getClassMap(null, $start, $end);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getClassMap($index, $start, $end);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function analyzeTraits(Patches $patches, string $start, string $end): void
    {
        $baseMap = $this->getTraitMap(null, $start, $end);
        $previousMaps = [];
        foreach (\array_keys($this->previousVersions) as $index) {
            $previousMaps[$index] = $this->getTraitMap($index, $start, $end);
        }
        $this->analyzeMaps($patches, $baseMap, $previousMaps);
    }

    private function filterBaseMap(array $baseMap, string $start, string $end, bool $keysInLowerCase): array
    {
        if ($start === '' && $end === '') {
            return $baseMap;
        }
        echo "\nstart\n";
        $result = [];
        foreach ($baseMap as $key => $value) {
            $firstLetter = $value->getFirstLetter();
            if ($start !== '' && $firstLetter < $start) {
                continue;
            }
            if ($end !== '' && $firstLetter > $end) {
                continue;
            }
            $result[$key] = $value;
        }
        echo "\nend\n";

        return $result;
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
                $since = $this->getDiffGroups($prevItems, $item)->getSince();
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
            foreach ($item->getParentInterfaces() as $parentConnection) {
                $interface = $this->findPreviousInterfaceByName($item->getVersion(), $parentConnection->getName());
                if ($interface !== null) {
                    $result += $this->expandInterfaceConstants($interface);
                }
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
        foreach ($item->getInterfaces() as $parentConnection) {
            $interface = $this->findPreviousInterfaceByName($item->getVersion(), $parentConnection->getInterface());
            if ($interface !== null) {
                $result += $this->expandInterfaceConstants($interface);
            }
        }
        if ($item->getParentClassName() !== '') {
            $class = $this->findPreviousClassByName($item->getVersion(), $item->getParentClassName());
            if ($class !== null) {
                $result += $this->expandClassConstants($class);
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
        foreach ($item->getTraits() as $child) {
            $trait = $this->findPreviousTraitByName($item->getVersion(), $child->getTrait());
            if ($trait !== null) {
                $result += $this->expandTraitProperties($trait);
            }
        }
        if ($item->getParentClassName() !== '') {
            $class = $this->findPreviousClassByName($item->getVersion(), $item->getParentClassName());
            if ($class !== null) {
                $result += $this->expandClassProperties($class);
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
        foreach ($item->getParentInterfaces() as $parentConnection) {
            $interface = $this->findPreviousInterfaceByName($item->getVersion(), $parentConnection->getName());
            if ($interface !== null) {
                $result += $this->expandInterfaceMethods($interface);
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
        foreach ($item->getTraits() as $child) {
            $trait = $this->findPreviousTraitByName($item->getVersion(), $child->getTrait());
            if ($trait !== null) {
                $traitResult = $this->expandTraitMethods($trait);
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
            $class = $this->findPreviousClassByName($item->getVersion(), $item->getParentClassName());
            if ($class !== null) {
                $result += $this->expandClassMethods($class);
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

    private function getInterfaceMap(?int $versionIndex, string $start = '', string $end = ''): array
    {
        $key = ((string) $versionIndex) . '|' . $start . '|' . $end;
        if (!isset($this->interfaceMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];
            $criteria = $this->getStartEndCriteria($start, $end);
            $list = $criteria === null ? $version->getInterfaces() : $version->getInterfaces()->matching($criteria);
            foreach ($list as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->interfaceMaps[$key] = $map;
        }

        return $this->interfaceMaps[$key];
    }

    private function findPreviousInterfaceByName(ReflectedVersion $previousVersion, string $name): ?ReflectedInterface
    {
        $key = ((string) \array_search($previousVersion, $this->previousVersions, true)) . '||';
        if (isset($this->interfaceMaps[$key])) {
            return $this->interfaceMaps[$key][\strtolower($name)] ?? null;
        }
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('name', $name))->setMaxResults(1);

        return $previousVersion->getInterfaces()->matching($criteria)->first() ?: null;
    }

    private function getClassMap(?int $versionIndex, string $start = '', string $end = ''): array
    {
        $key = ((string) $versionIndex) . '|' . $start . '|' . $end;
        if (!isset($this->classMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];
            $criteria = $this->getStartEndCriteria($start, $end);
            foreach ($version->getClassAliases() as $item) {
                $map[\strtolower($item->getAlias())] = $item->getActualClass();
            }
            $list = $criteria === null ? $version->getClasses() : $version->getClasses()->matching($criteria);
            foreach ($list as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->classMaps[$key] = $map;
        }

        return $this->classMaps[$key];
    }

    private function findPreviousClassByName(ReflectedVersion $previousVersion, string $name): ?ReflectedClass
    {
        $key = ((string) \array_search($previousVersion, $this->previousVersions, true)) . '||';
        if (isset($this->classMaps[$key])) {
            return $this->classMaps[$key][\strtolower($name)] ?? null;
        }
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('name', $name))->setMaxResults(1);
        $class = $previousVersion->getClasses()->matching($criteria)->first() ?: null;
        if ($class !== null) {
            return $class;
        }
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('alias', $name))->setMaxResults(1);
        $alias = $previousVersion->getClassAliases()->matching($criteria)->first() ?: null;
        if ($alias !== null) {
            return $alias->getActualClass();
        }

        return null;
    }

    private function getTraitMap(?int $versionIndex, string $start = '', string $end = ''): array
    {
        $key = ((string) $versionIndex) . '|' . $start . '|' . $end;
        if (!isset($this->traitMaps[$key])) {
            $map = [];
            $version = $versionIndex === null ? $this->baseVersion : $this->previousVersions[$versionIndex];
            $criteria = $this->getStartEndCriteria($start, $end);
            $list = $criteria === null ? $version->getTraits() : $version->getTraits()->matching($criteria);
            foreach ($list as $item) {
                $map[\strtolower($item->getName())] = $item;
            }
            $this->traitMaps[$key] = $map;
        }

        return $this->traitMaps[$key];
    }

    private function findPreviousTraitByName(ReflectedVersion $previousVersion, string $name): ?ReflectedTrait
    {
        $key = ((string) \array_search($previousVersion, $this->previousVersions, true)) . '||';
        if (isset($this->traitMaps[$key])) {
            return $this->traitMaps[$key][\strtolower($name)] ?? null;
        }
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('name', $name))->setMaxResults(1);

        return $previousVersion->getTraits()->matching($criteria)->first() ?: null;
    }

    private function getDiffGroups(array $prevItems, object $currentItem): DiffGroupList
    {
        $diffGroupList = new DiffGroupList();
        for ($index = \count($this->previousVersions) - 1; $index >= 0; $index--) {
            if ($prevItems[$index] === null) {
                $diffGroupList->add(DiffGroup::TYPE_MISSING, $this->previousVersions[$index]);
            } else {
                $type = $prevItems[$index]->isVendor() ? DiffGroup::TYPE_VENDOR : DiffGroup::TYPE_CORE;
                $diffGroupList->add(
                    $type,
                    $this->previousVersions[$index],
                    $type === DiffGroup::TYPE_VENDOR ? $prevItems[$index]->getVendorName() : '',
                    $prevItems[$index] instanceof VisibilityInterface ? $prevItems[$index]->getVisibility() : ''
                );
            }
        }
        $diffGroupList->add(
            DiffGroup::TYPE_CORE,
            $this->baseVersion,
            '',
            $currentItem instanceof VisibilityInterface ? $currentItem->getVisibility() : ''
        );

        return $diffGroupList;
    }

    private function getStartEndCriteria(string $start, string $end): ?Criteria
    {
        if ($start === '' && $end === '') {
            return null;
        }
        $criteria = Criteria::create();
        if ($start !== '') {
            $criteria->andWhere(Criteria::expr()->gte('firstLetter', $start));
        }
        if ($end !== '') {
            $criteria->andWhere(Criteria::expr()->lte('firstLetter', $end));
        }

        return $criteria;
    }
}
