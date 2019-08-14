<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\JsonTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Base class for parameters of methods and global functions.
 */
abstract class ReflectedFunctionParameter
{
    use RecordIDTrait;

    /**
     * The 0-based ordinal position of the parameter.
     *
     * @\Doctrine\ORM\Mapping\Column(type="integer", length=100, nullable=false, options={"unsigned": true, "comment": "The 0-based ordinal position of the parameter"})
     *
     * @var int
     */
    protected $position;

    /**
     * The name of the parameter.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "The name of the parameter"})
     *
     * @var string
     */
    protected $name;

    /**
     * The parameter accepted type.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "The parameter accepted type"})
     *
     * @var string
     */
    protected $type;

    /**
     * The value can be null?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "The value can be null?"})
     *
     * @var bool
     */
    protected $allowsNull;

    /**
     * Is the value passed by reference?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is the value passed by reference?"})
     *
     * @var bool
     */
    protected $byReference;

    /**
     * Is the value variadic?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is the value variadic?"})
     *
     * @var bool
     */
    protected $variadic;

    /**
     * Is the value optional?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is the value optional?"})
     *
     * @var bool
     */
    protected $optional;

    /**
     * The JSON-encoded default value of the parameter.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "JSON-encoded default value of the parameter"})
     *
     * @var string
     */
    protected $defaultValue;

    /**
     * The name of the constant used as the default value of the parameter.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the constant used as the default value of the parameter"})
     *
     * @var string
     */
    protected $defaultValueConstantName;

    use JsonTrait;

    /**
     * Create a new instance.
     *
     * @return static
     */
    protected static function createBase(int $position, string $name): self
    {
        $result = new static();
        $result
            ->setPosition($position)
            ->setName($name)
            ->setType('')
            ->setAllowsNull(false)
            ->setByReference(false)
            ->setVariadic(false)
            ->setOptional(false)
            ->clearDefaultValue()
            ->setDefaultValueConstantName('')
        ;

        return $result;
    }

    /**
     * Get the 0-based ordinal position of the parameter.
     *
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set the 0-based ordinal position of the parameter.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setPosition(int $value): self
    {
        $this->position = $value;

        return $this;
    }

    /**
     * Get the name of the parameter.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the parameter.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Get the parameter accepted type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the parameter accepted type.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setType(string $value): self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * The value can be null?
     *
     * @return bool
     */
    public function isAllowsNull(): bool
    {
        return $this->allowsNull;
    }

    /**
     * The value can be null?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setAllowsNull(bool $value): self
    {
        $this->allowsNull = $value;

        return $this;
    }

    /**
     * Is the value passed by reference?
     *
     * @return bool
     */
    public function isByReference(): bool
    {
        return $this->byReference;
    }

    /**
     * Is the value passed by reference?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setByReference(bool $value): self
    {
        $this->byReference = $value;

        return $this;
    }

    /**
     * Is the value variadic?
     *
     * @return bool
     */
    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    /**
     * Is the value variadic?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setVariadic(bool $value): self
    {
        $this->variadic = $value;

        return $this;
    }

    /**
     * Is the value optional?
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * Is the value optional?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setOptional(bool $value): self
    {
        $this->optional = $value;

        return $this;
    }

    /**
     * Get the default value of the parameter.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->fromJson($this->defaultValue);
    }

    /**
     * Set the default value of the parameter.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setDefaultValue($value): self
    {
        $this->defaultValue = $this->toJson($value);

        return $this;
    }

    /**
     * Remove the default value of the parameter.
     *
     * @return $this
     */
    public function clearDefaultValue(): self
    {
        $this->defaultValue = '';

        return $this;
    }

    /**
     * Get the name of the constant used as the default value of the parameter.
     *
     * @return string
     */
    public function getDefaultValueConstantName(): string
    {
        return $this->defaultValueConstantName;
    }

    /**
     * Set the name of the constant used as the default value of the parameter.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setDefaultValueConstantName(string $value): self
    {
        $this->defaultValueConstantName = $value;

        return $this;
    }
}
