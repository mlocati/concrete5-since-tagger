<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Traits\DefinedAtTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent a class.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="Classes",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="Classes_version_name",
 *             columns={"version", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of classes"
 *     }
 * )
 */
class ReflectedClass
{
    use RecordIDTrait;

    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="classes")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    /**
     * The name of the class.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "Name of the class"})
     *
     * @var string
     */
    protected $name;

    /**
     * Is this class abstract?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this class abstract?"})
     *
     * @var bool
     */
    protected $abstract;

    /**
     * Is this class final?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this class final?"})
     *
     * @var bool
     */
    protected $final;

    /**
     * The name of the parent class.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "Name of the the parent class"})
     *
     * @var string
     */
    protected $parentClassName;

    /**
     * The list of interfaces implemented by the class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassInterface", mappedBy="class", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassInterface[]|\Doctrine\Common\Collections\Collection
     */
    protected $interfaces;

    /**
     * The list of traits used by the class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassTrait", mappedBy="class", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait[]|\Doctrine\Common\Collections\Collection
     */
    protected $traits;

    /**
     * The list of the constants of this class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassConstant", mappedBy="class", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassConstant[]|\Doctrine\Common\Collections\Collection
     */
    protected $constants;

    /**
     * The list of the properties of this class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassProperty", mappedBy="class", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassProperty[]|\Doctrine\Common\Collections\Collection
     */
    protected $properties;

    /**
     * The list of the methods of this class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassMethod", mappedBy="class", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod[]|\Doctrine\Common\Collections\Collection
     */
    protected $methods;

    /**
     * The list of aliases of this class.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassAlias", mappedBy="actualClass", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassAlias[]|\Doctrine\Common\Collections\Collection
     */
    protected $aliases;

    use DefinedAtTrait;

    protected function __construct()
    {
        $this->interfaces = new ArrayCollection();
        $this->traits = new ArrayCollection();
        $this->constants = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->methods = new ArrayCollection();
        $this->aliases = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $version
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedVersion $version, string $name): self
    {
        $result = new static();
        $result
            ->setVersion($version)
            ->setName($name)
            ->setParentClassName('')
            ->setAbstract(false)
            ->setFinal(false)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;

        return $result;
    }

    /**
     * Get the associated core version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    public function getVersion(): ReflectedVersion
    {
        return $this->version;
    }

    /**
     * Set the associated core version.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $value
     *
     * @return $this
     */
    public function setVersion(ReflectedVersion $value): self
    {
        $this->version = $value;

        return $this;
    }

    /**
     * Get the name of the class.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the class.
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
     * Get the name of the parent class.
     *
     * @return string
     */
    public function getParentClassName(): string
    {
        return $this->parentClassName;
    }

    /**
     * Set the name of the parent class.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setParentClassName(string $value): self
    {
        $this->parentClassName = $value;

        return $this;
    }

    /**
     * Get the list of interfaces implemented by the class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassInterface[]|\Doctrine\Common\Collections\Collection
     */
    public function getInterfaces(): Collection
    {
        return $this->interfaces;
    }

    /**
     * Get the list of traits used by the class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait[]|\Doctrine\Common\Collections\Collection
     */
    public function getTraits(): Collection
    {
        return $this->traits;
    }

    /**
     * Get the list of the constants of this class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassConstant[]|\Doctrine\Common\Collections\Collection
     */
    public function getConstants(): Collection
    {
        return $this->constants;
    }

    /**
     * Get the list of the properties of this class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassProperty[]|\Doctrine\Common\Collections\Collection
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * Get the list of the methods of this class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod[]|\Doctrine\Common\Collections\Collection
     */
    public function getMethods(): Collection
    {
        return $this->methods;
    }

    /**
     * Get the list of aliases of this class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassAlias[]|\Doctrine\Common\Collections\Collection
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }

    /**
     * Is this class abstract?
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Is this class abstract?
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
     * Is this class final?
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * Is this class final?
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
}
