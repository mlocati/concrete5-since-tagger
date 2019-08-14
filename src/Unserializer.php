<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger;

use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Reflected\ReflectedClass;
use MLocati\C5SinceTagger\Reflected\ReflectedClassAlias;
use MLocati\C5SinceTagger\Reflected\ReflectedClassConstant;
use MLocati\C5SinceTagger\Reflected\ReflectedClassInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedClassMethod;
use MLocati\C5SinceTagger\Reflected\ReflectedClassProperty;
use MLocati\C5SinceTagger\Reflected\ReflectedClassTrait;
use MLocati\C5SinceTagger\Reflected\ReflectedClassTraitAlias;
use MLocati\C5SinceTagger\Reflected\ReflectedFunction;
use MLocati\C5SinceTagger\Reflected\ReflectedFunctionParameter;
use MLocati\C5SinceTagger\Reflected\ReflectedGlobalConstant;
use MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction;
use MLocati\C5SinceTagger\Reflected\ReflectedInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedInterfaceConstant;
use MLocati\C5SinceTagger\Reflected\ReflectedInterfaceInterface;
use MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod;
use MLocati\C5SinceTagger\Reflected\ReflectedTrait;
use MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod;
use MLocati\C5SinceTagger\Reflected\ReflectedTraitProperty;
use MLocati\C5SinceTagger\Reflected\ReflectedVersion;

class Unserializer
{
    public function unserializeJsonFile(string $jsonFile): ReflectedVersion
    {
        $data = \file_get_contents($jsonFile);
        if (!$data) {
            throw new \Exception('Failed to read the JSON file');
        }

        return $this->unserializeJson($data);
    }

    public function unserializeJson(string $json): ReflectedVersion
    {
        $data = \json_decode($json, true, 512, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
        if (!$data) {
            throw new \Exception('Failed to unserialize the JSON-encoded data');
        }

        return $this->unserializeVersion($data);
    }

    public function unserializeVersion(array $data): ReflectedVersion
    {
        $version = ReflectedVersion::create($this->popString($data, 'version'))
            ->setParsedAt($this->popDateTime($data, 'parsedAt'))
        ;
        foreach ($this->popArray($data, 'globalConstants') as $v) {
            $version->getGlobalConstants()->add($this->unserializeGlobalConstant($version, $v));
        }
        foreach ($this->popArray($data, 'globalFunctions') as $v) {
            $version->getGlobalFunctions()->add($this->unserializeGlobalFunction($version, $v));
        }
        foreach ($this->popArray($data, 'interfaces') as $v) {
            $version->getInterfaces()->add($this->unserializeInterface($version, $v));
        }
        foreach ($this->popArray($data, 'classes') as $v) {
            $version->getClasses()->add($this->unserializeClass($version, $v));
        }
        foreach ($this->popArray($data, 'traits') as $v) {
            $version->getTraits()->add($this->unserializeTrait($version, $v));
        }
        foreach ($this->popArray($data, 'classAliases') as $v) {
            $this->unserializeClassAlias($version->getClasses(), $v);
        }
        $this->assertDataIsEmpty($data);

        return $version;
    }

    public function unserializeGlobalConstant(ReflectedVersion $version, array $data): ReflectedGlobalConstant
    {
        $globalConstant = ReflectedGlobalConstant::create($version, $this->popString($data, 'name'), $this->pop($data, 'value'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        $this->assertDataIsEmpty($data);

        return $globalConstant;
    }

    public function unserializeGlobalFunction(ReflectedVersion $version, array $data): ReflectedGlobalFunction
    {
        $globalFunction = ReflectedGlobalFunction::create($version, $this->popString($data, 'name'));
        $this->unserializeFunction($globalFunction, $data);
        $this->assertDataIsEmpty($data);

        return $globalFunction;
    }

    public function unserializeInterface(ReflectedVersion $version, array $data): ReflectedInterface
    {
        $interface = ReflectedInterface::create($version, $this->popString($data, 'name'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        foreach ($this->popArray($data, 'parentInterfaces') as $parentInterface) {
            $interface->getParentInterfaces()->add(ReflectedInterfaceInterface::create($interface, $parentInterface));
        }
        foreach ($this->popArray($data, 'constants') as $v) {
            $interface->getConstants()->add($this->unserializeInterfaceConstant($interface, $v));
        }
        foreach ($this->popArray($data, 'methods') as $v) {
            $interface->getMethods()->add($this->unserializeInterfaceMethod($interface, $v));
        }
        $this->assertDataIsEmpty($data);

        return $interface;
    }

    public function unserializeClass(ReflectedVersion $version, array $data): ReflectedClass
    {
        $class = ReflectedClass::create($version, $this->popString($data, 'name'))
            ->setFinal($this->popBoolean($data, 'final'))
            ->setAbstract($this->popBoolean($data, 'abstract'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
            ->setParentClassName($this->popString($data, 'parentClassName'))
        ;
        foreach ($this->popArray($data, 'implements') as $interface) {
            $class->getInterfaces()->add(ReflectedClassInterface::create($class, $interface));
        }
        foreach ($this->popArray($data, 'traits') as $v) {
            $class->getTraits()->add($this->unserializeClassTrait($class, $v));
        }
        foreach ($this->popArray($data, 'constants') as $v) {
            $class->getConstants()->add($this->unserializeClassConstant($class, $v));
        }
        foreach ($this->popArray($data, 'properties') as $v) {
            $class->getProperties()->add($this->unserializeClassProperty($class, $v));
        }
        foreach ($this->popArray($data, 'methods') as $v) {
            $class->getMethods()->add($this->unserializeClassMethod($class, $v));
        }

        $this->assertDataIsEmpty($data);

        return $class;
    }

    public function unserializeClassAlias(Collection $reflectedClasses, array $data): void
    {
        $actualClass = $this->popString($data, 'actualClass');
        $alias = $this->popString($data, 'alias');
        $this->assertDataIsEmpty($data);
        foreach ($reflectedClasses as $reflectedClass) {
            if ($reflectedClass->getName() === $actualClass) {
                $reflectedClass->getAliases()->add(ReflectedClassAlias::create($alias, $reflectedClass));

                return;
            }
        }
        throw new \Exception("Failed to find the actual class {$actualClass} for the alias {$alias}");
    }

    public function unserializeTrait(ReflectedVersion $version, array $data): ReflectedTrait
    {
        $trait = ReflectedTrait::create($version, $this->popString($data, 'name'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        foreach ($this->popArray($data, 'properties') as $v) {
            $trait->getProperties()->add($this->unserializeTraitProperty($trait, $v));
        }
        foreach ($this->popArray($data, 'methods') as $v) {
            $trait->getMethods()->add($this->unserializeTraitMethod($trait, $v));
        }
        $this->assertDataIsEmpty($data);

        return $trait;
    }

    public function unserializeClassTrait(ReflectedClass $class, array $data): ReflectedClassTrait
    {
        $classTrait = ReflectedClassTrait::create($class, $this->popString($data, 'name'));
        foreach ($this->popArray($data, 'aliases') as $v) {
            $classTrait->getAliases()->add(ReflectedClassTraitAlias::create($classTrait, $this->popString($v, 'originalName'), $this->popString($v, 'alias')));
            $this->assertDataIsEmpty($v);
        }
        $this->assertDataIsEmpty($data);

        return $classTrait;
    }

    private function unserializeInterfaceConstant(ReflectedInterface $interface, array &$data): ReflectedInterfaceConstant
    {
        $constant = ReflectedInterfaceConstant::create($interface, $this->popString($data, 'name'), $this->pop($data, 'value'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        $this->assertDataIsEmpty($data);

        return $constant;
    }

    private function unserializeClassConstant(ReflectedClass $class, array &$data): ReflectedClassConstant
    {
        $constant = ReflectedClassConstant::create($class, $this->popString($data, 'name'), $this->pop($data, 'value'))
            ->setVisibility($this->popString($data, 'visibility'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        $this->assertDataIsEmpty($data);

        return $constant;
    }

    private function unserializeClassProperty(ReflectedClass $class, array &$data): ReflectedClassProperty
    {
        $property = ReflectedClassProperty::create($class, $this->popString($data, 'name'))
            ->setVisibility($this->popString($data, 'visibility'))
            ->setStatic($this->popBoolean($data, 'static'))
            ->setType($this->popString($data, 'type'))
            ->setTypeAllowsNull($this->popBooleanNullable($data, 'typeAllowsNull'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        if (\array_key_exists('defaultValueConstantName', $data)) {
            $property->setDefaultValueConstantName($this->popString($data, 'defaultValueConstantName'));
        } else {
            $property->setDefaultValue($this->pop($data, 'defaultValue'));
        }
        $this->assertDataIsEmpty($data);

        return $property;
    }

    private function unserializeTraitProperty(ReflectedTrait $trait, array &$data): ReflectedTraitProperty
    {
        $property = ReflectedTraitProperty::create($trait, $this->popString($data, 'name'))
            ->setVisibility($this->popString($data, 'visibility'))
            ->setStatic($this->popBoolean($data, 'static'))
            ->setDefaultValue($this->pop($data, 'defaultValue'))
            ->setType($this->popString($data, 'type'))
            ->setTypeAllowsNull($this->popBooleanNullable($data, 'typeAllowsNull'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        $this->assertDataIsEmpty($data);

        return $property;
    }

    private function unserializeInterfaceMethod(ReflectedInterface $interface, array &$data): ReflectedInterfaceMethod
    {
        $method = ReflectedInterfaceMethod::create($interface, $this->popString($data, 'name'))
            ->setStatic($this->popBoolean($data, 'static'))
        ;
        $this->unserializeFunction($method, $data);
        $this->assertDataIsEmpty($data);

        return $method;
    }

    private function unserializeClassMethod(ReflectedClass $class, array &$data): ReflectedClassMethod
    {
        $method = ReflectedClassMethod::create($class, $this->popString($data, 'name'))
            ->setAbstract($this->popBoolean($data, 'abstract'))
            ->setStatic($this->popBoolean($data, 'static'))
            ->setFinal($this->popBoolean($data, 'final'))
            ->setVisibility($this->popString($data, 'visibility'))
        ;
        $this->unserializeFunction($method, $data);
        $this->assertDataIsEmpty($data);

        return $method;
    }

    private function unserializeTraitMethod(ReflectedTrait $trait, array &$data): ReflectedTraitMethod
    {
        $method = ReflectedTraitMethod::create($trait, $this->popString($data, 'name'))
            ->setAbstract($this->popBoolean($data, 'abstract'))
            ->setStatic($this->popBoolean($data, 'static'))
            ->setFinal($this->popBoolean($data, 'final'))
            ->setVisibility($this->popString($data, 'visibility'))
        ;
        $this->unserializeFunction($method, $data);
        $this->assertDataIsEmpty($data);

        return $method;
    }

    private function unserializeFunction(ReflectedFunction $function, array &$data): void
    {
        $function
            ->setReturnsReference($this->popBoolean($data, 'returnsReference'))
            ->setReturnType($this->popString($data, 'returnType'))
            ->setReturnTypeAllowsNull($this->popBooleanNullable($data, 'returnTypeAllowsNull'))
            ->setGenerator($this->popBoolean($data, 'generator'))
            ->setDefinedAt($this->popString($data, 'definedAt'))
            ->setSincePhpDoc($this->popString($data, 'since'))
        ;
        foreach ($this->popArray($data, 'parameters') as $position => $v) {
            $function->getParameters()->add($this->unserializeFunctionParameter($function, $position, $v));
        }
    }

    private function unserializeFunctionParameter(ReflectedFunction $function, int $position, array &$data): ReflectedFunctionParameter
    {
        $parameter = $function->createParameter($position, $this->popString($data, 'name'))
            ->setType($this->popString($data, 'type'))
            ->setAllowsNull($this->popBoolean($data, 'allowsNull'))
            ->setByReference($this->popBoolean($data, 'byReference'))
            ->setVariadic($this->popBoolean($data, 'variadic'))
            ->setOptional($this->popBoolean($data, 'optional'))
        ;
        if ($parameter->isOptional()) {
            if (\array_key_exists('defaultValue', $data)) {
                $parameter->setDefaultValue($this->pop($data, 'defaultValue'));
            } else {
                $parameter->setDefaultValueConstantName($this->popString($data, 'defaultValueConstantName'));
            }
        } else {
            $parameter->clearDefaultValue();
        }
        $this->assertDataIsEmpty($data);

        return $parameter;
    }

    private function pop(array &$data, string $key)
    {
        if (!\array_key_exists($key, $data)) {
            throw new \Exception("Missing required key: {$key}");
        }
        $result = $data[$key];
        unset($data[$key]);

        return $result;
    }

    private function popBoolean(array &$data, string $key): bool
    {
        $result = $this->pop($data, $key);
        if (!\is_bool($result)) {
            throw new \Exception("The key {$key} should be a boolean, but it has the type " . \gettype($result));
        }

        return $result;
    }

    private function popBooleanNullable(array &$data, string $key): ?bool
    {
        $result = $this->pop($data, $key);
        if ($result !== null && !\is_bool($result)) {
            throw new \Exception("The key {$key} should be a boolean or NULL, but it has the type " . \gettype($result));
        }

        return $result;
    }

    private function popInteger(array &$data, string $key): int
    {
        $result = $this->pop($data, $key);
        if (!\is_int($result)) {
            throw new \Exception("The key {$key} should be an integer, but it has the type " . \gettype($result));
        }

        return $result;
    }

    private function popString(array &$data, string $key): string
    {
        $result = $this->pop($data, $key);
        if (!\is_string($result)) {
            throw new \Exception("The key {$key} should be a string, but it has the type " . \gettype($result));
        }

        return $result;
    }

    private function popArray(array &$data, string $key): array
    {
        $result = $this->pop($data, $key);
        if (!\is_array($result)) {
            throw new \Exception("The key {$key} should be an array, but it has the type " . \gettype($result));
        }

        return $result;
    }

    private function popDateTime(array &$data, string $key): \DateTimeImmutable
    {
        $timestamp = $this->popInteger($data, $key);
        if ($timestamp < \mktime(0, 0, 0, 1, 1, 2000) || $timestamp > \time() + 60 * 60 * 24 * 12) {
            throw new \Exception("The key {$key} contains an invalid timestamp ({$timestamp})");
        }

        return new \DateTimeImmutable("@{$timestamp}");
    }

    private function assertDataIsEmpty(array $data): void
    {
        if ($data !== []) {
            throw new \Exception('Unrecognized keys: ' . \implode(', ', \array_keys($data)));
        }
    }
}
