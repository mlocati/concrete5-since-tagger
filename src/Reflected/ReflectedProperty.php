<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Interfaces\DefinedAtInterface;
use MLocati\C5SinceTagger\Interfaces\VisibilityInterface;
use MLocati\C5SinceTagger\Traits\DefinedAtTrait;
use MLocati\C5SinceTagger\Traits\JsonTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent a property of a class/interface/trait.
 */
abstract class ReflectedProperty implements DefinedAtInterface, VisibilityInterface
{
    use RecordIDTrait;

    /**
     * The name of the property.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the property"})
     *
     * @var string
     */
    protected $name;

    /**
     * Is this a static property?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this a static property?"})
     *
     * @var bool
     */
    protected $static;

    /**
     * The visibility modifier.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=20, nullable=false, options={"comment": "The visibility modifier"})
     *
     * @var string
     *
     * @see \MLocati\C5SinceTagger\Interfaces\VisibilityInterface
     */
    protected $visibility;

    /**
     * The JSON-encoded default value.
     *
     * @\Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "The JSON-encoded default value"})
     *
     * @var string
     */
    protected $defaultValue;

    /**
     * The name of the constant used as the default value of the property.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the constant used as the default value of the property"})
     *
     * @var string
     */
    protected $defaultValueConstantName;

    /**
     * The type.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "The type"})
     *
     * @var string
     */
    protected $type;

    /**
     * The type allows null values?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=true, options={"comment": "The type allows null values?"})
     *
     * @var bool|null
     */
    protected $typeAllowsNull;

    use JsonTrait;

    use DefinedAtTrait;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $class
     * @param string $name
     *
     * @return static
     */
    protected static function createBase(string $name): self
    {
        $result = new static();
        $result
            ->setName($name)
            ->setStatic(false)
            ->setVisibility(VisibilityInterface::PUBLIC)
            ->setDefaultValue(null)
            ->setDefaultValueConstantName('')
            ->setType('')
            ->setTypeAllowsNull(null)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;

        return $result;
    }

    /**
     * Get the name of the property.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the property.
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
     * Is this a static property?
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * Is this a static property?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setStatic(bool $value): self
    {
        $this->static = $value;

        return $this;
    }

    /**
     * Get the visibility modifier.
     *
     * @return string
     *
     * @see \MLocati\C5SinceTagger\Interfaces\VisibilityInterface
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Set the visibility modifier.
     *
     * @param string $value
     *
     * @return $this
     *
     * @see \MLocati\C5SinceTagger\Interfaces\VisibilityInterface
     */
    public function setVisibility(string $value): self
    {
        $this->visibility = $value;

        return $this;
    }

    /**
     * Get the default value.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->fromJson($this->defaultValue);
    }

    /**
     * Set the default value.
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
     * Get the name of the constant used as the default value of the property.
     *
     * @return string
     */
    public function getDefaultValueConstantName(): string
    {
        return $this->defaultValueConstantName;
    }

    /**
     * Set the name of the constant used as the default value of the property.
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

    /**
     * Get the type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type.
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
     * The type allows null values?
     *
     * @return bool|null
     */
    public function getTypeAllowsNull(): ?bool
    {
        return $this->typeAllowsNull;
    }

    /**
     * The type allows null values?
     *
     * @param bool|null $value
     *
     * @return $this
     */
    public function setTypeAllowsNull(?bool $value): self
    {
        $this->typeAllowsNull = $value;

        return $this;
    }
}
