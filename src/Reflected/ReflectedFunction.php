<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Traits\DefinedAtTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Base class for methods and global functions.
 */
abstract class ReflectedFunction
{
    use RecordIDTrait;

    /**
     * The name of the function.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the function"})
     *
     * @var string
     */
    protected $name;

    /**
     * The return value is by reference?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "The return value is by reference?"})
     *
     * @var bool
     */
    protected $returnsReference;

    /**
     * The return type.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "The return type"})
     *
     * @var string
     */
    protected $returnType;

    /**
     * The return type allows null values?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=true, options={"comment": "The return type allows null values?"})
     *
     * @var bool|null
     */
    protected $returnTypeAllowsNull;

    /**
     * Is this function a generator?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this function a generator?"})
     *
     * @var bool
     */
    protected $generator;

    use DefinedAtTrait;

    /**
     * Create a new instance.
     *
     * @param string $name
     *
     * @return static
     */
    protected static function createBase(string $name): self
    {
        $result = new static();
        $result
            ->setName($name)
            ->setReturnsReference(false)
            ->setReturnType('')
            ->setReturnTypeAllowsNull(null)
            ->setGenerator(false)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;

        return $result;
    }

    /**
     * Get the name of the function/method.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the function/method.
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
     * The return value is by reference?
     *
     * @return bool
     */
    public function isReturnsReference(): bool
    {
        return $this->returnsReference;
    }

    /**
     * The return value is by reference?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setReturnsReference(bool $value): self
    {
        $this->returnsReference = $value;

        return $this;
    }

    /**
     * Get the return type.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * Set the return type.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setReturnType(string $value): self
    {
        $this->returnType = $value;

        return $this;
    }

    /**
     * The return type allows null values?
     *
     * @return bool|null
     */
    public function getReturnTypeAllowsNull(): ?bool
    {
        return $this->returnTypeAllowsNull;
    }

    /**
     * The return type allows null values?
     *
     * @param bool|null $value
     *
     * @return $this
     */
    public function setReturnTypeAllowsNull(?bool $value): self
    {
        $this->returnTypeAllowsNull = $value;

        return $this;
    }

    /**
     * Is this function a generator?
     *
     * @return bool
     */
    public function isGenerator(): bool
    {
        return $this->generator;
    }

    /**
     * Is this function a generator?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setGenerator(bool $value): self
    {
        $this->generator = $value;

        return $this;
    }

    /**
     * Create a new instance of a parameter for this function.
     *
     * @return ReflectedFunctionParameter
     */
    abstract public function createParameter(int $position, string $name): ReflectedFunctionParameter;

    /**
     * Get the list of the function parameters.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedFunctionParameter[]|\Doctrine\Common\Collections\Collection
     */
    abstract public function getParameters(): Collection;
}
