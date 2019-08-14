<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent a core version.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="Versions",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="Versions_name",
 *             columns={"name"}
 *         )
 *     },
 *     options={
 *         "comment": "The parsed core versions"
 *     }
 * )
 */
class ReflectedVersion
{
    use RecordIDTrait;

    /**
     * The version name.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Version name"})
     *
     * @var string
     */
    protected $name;

    /**
     * Global constants in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedGlobalConstant", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedGlobalConstant[]|\Doctrine\Common\Collections\Collection
     */
    protected $globalConstants;

    /**
     * Global functions in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction[]|\Doctrine\Common\Collections\Collection
     */
    protected $globalFunctions;

    /**
     * Interfaces defined in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterface", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterface[]|\Doctrine\Common\Collections\Collection
     */
    protected $interfaces;

    /**
     * Classes defined in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass[]|\Doctrine\Common\Collections\Collection
     */
    protected $classes;

    /**
     * Traits defined in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTrait", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTrait[]|\Doctrine\Common\Collections\Collection
     */
    protected $traits;

    /**
     * Class aliases defined in this version.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassAlias", mappedBy="version", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassAlias[]|\Doctrine\Common\Collections\Collection
     */
    protected $classAliases;

    /**
     * The date/time when this data was parsed.
     *
     * @\Doctrine\ORM\Mapping\Column(type="datetime_immutable", nullable=false, options={"comment": "Date/time when this data was parsed"})
     *
     * @var \DateTimeImmutable
     */
    protected $parsedAt;

    /**
     * The date/time when this data was persisted.
     *
     * @\Doctrine\ORM\Mapping\Column(type="datetime_immutable", nullable=false, options={"comment": "Date/time when this data was persisted"})
     *
     * @var \DateTimeImmutable
     */
    protected $savedAt;

    protected function __construct()
    {
        $this->globalConstants = new ArrayCollection();
        $this->globalFunctions = new ArrayCollection();
        $this->interfaces = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->traits = new ArrayCollection();
        $this->classAliases = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     *
     * @return static
     */
    public static function create(string $name): self
    {
        $result = new static();
        $ts = \time();
        $result
            ->setName($name)
            ->setParsedAt(new \DateTimeImmutable("@{$ts}"))
            ->setSavedAt(new \DateTimeImmutable("@{$ts}"))
        ;

        return $result;
    }

    /**
     * Get the version name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the version name.
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
     * Get the global constants in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedGlobalConstant[]|\Doctrine\Common\Collections\Collection
     */
    public function getGlobalConstants(): Collection
    {
        return $this->globalConstants;
    }

    /**
     * Get the global functions in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction[]|\Doctrine\Common\Collections\Collection
     */
    public function getGlobalFunctions(): Collection
    {
        return $this->globalFunctions;
    }

    /**
     * Get the interfaces defined in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterface[]|\Doctrine\Common\Collections\Collection
     */
    public function getInterfaces(): Collection
    {
        return $this->interfaces;
    }

    /**
     * Get the classes defined in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClass[]|\Doctrine\Common\Collections\Collection
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    /**
     * Get the traits defined in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTrait[]|\Doctrine\Common\Collections\Collection
     */
    public function getTraits(): Collection
    {
        return $this->traits;
    }

    /**
     * Get the class aliases defined in this version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassAlias[]|\Doctrine\Common\Collections\Collection
     */
    public function getClassAliases(): Collection
    {
        return $this->classAliases;
    }

    /**
     * Get the date/time when this data was parsed.
     *
     * @return \DateTimeImmutable
     */
    public function getParsedAt(): \DateTimeImmutable
    {
        return $this->parsedAt;
    }

    /**
     * Set the date/time when this data was parsed.
     *
     * @param \DateTimeImmutable $value
     *
     * @return $this
     */
    public function setParsedAt(\DateTimeImmutable $value): self
    {
        $this->parsedAt = $value;

        return $this;
    }

    /**
     * Get the date/time when this data was persisted.
     *
     * @return \DateTimeImmutable
     */
    public function getSavedAt(): \DateTimeImmutable
    {
        return $this->savedAt;
    }

    /**
     * Set the date/time when this data was persisted.
     *
     * @param \DateTimeImmutable $value
     *
     * @return $this
     */
    public function setSavedAt(\DateTimeImmutable $value): self
    {
        $this->savedAt = $value;

        return $this;
    }
}
