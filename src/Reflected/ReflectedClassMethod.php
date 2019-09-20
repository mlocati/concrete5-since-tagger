<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Interfaces\VisibilityInterface;

/**
 * Represent a class method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassMethods",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassMethods_class_name",
 *             columns={"class", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of class methods"
 *     }
 * )
 */
class ReflectedClassMethod extends ReflectedFunction implements VisibilityInterface
{
    /**
     * The associated class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="methods")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="class", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    protected $class;

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
     * Is this method abstract?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method abstract?"})
     *
     * @var bool
     */
    protected $abstract;

    /**
     * Is this method static?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method static?"})
     *
     * @var bool
     */
    protected $static;

    /**
     * Is this method final?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method final?"})
     *
     * @var bool
     */
    protected $final;

    /**
     * The list of the method parameters.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassMethodParameter", mappedBy="method", cascade={"persist", "remove"})
     * @\Doctrine\ORM\Mapping\OrderBy({"position"="ASC"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassMethodParameter[]|\Doctrine\Common\Collections\Collection
     */
    protected $parameters;

    protected function __construct()
    {
        $this->parameters = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $class
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedClass $class, string $name): self
    {
        return parent::createBase($name)
            ->setClass($class)
            ->setVisibility(VisibilityInterface::PUBLIC)
            ->setAbstract(false)
            ->setStatic(false)
            ->setFinal(false)
        ;
    }

    /**
     * Get the associated class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    public function getClass(): ReflectedClass
    {
        return $this->class;
    }

    /**
     * Set the associated class.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $value
     */
    public function setClass(ReflectedClass $value): self
    {
        $this->class = $value;

        return $this;
    }

    /**
     * Get the visibility modifier.
     *
     * @return string
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Get the visibility modifier.
     *
     * @param string $visibility
     */
    public function setVisibility(string $value): self
    {
        $this->visibility = $value;

        return $this;
    }

    /**
     * Is this method abstract?
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Is this method abstract?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setAbstract(bool $value): self
    {
        $this->abstract = $value;

        return $this;
    }

    /**
     * Is this method static?
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * Is this method static?
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
     * Is this method final?
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * Is this method final?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setFinal(bool $value): self
    {
        $this->final = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassMethodParameter
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::createParameter()
     */
    public function createParameter(int $position, string $name): ReflectedFunctionParameter
    {
        return ReflectedClassMethodParameter::create($this, $position, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassMethodParameter[]|\Doctrine\Common\Collections\Collection
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::getParameters()
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
