<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Interfaces\DefinedAtInterface;
use MLocati\C5SinceTagger\Traits\DefinedAtTrait;
use MLocati\C5SinceTagger\Traits\IndexedNameTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent an interface.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="Interfaces",
 *     indexes={
 *         @Index(name="Interfaces_firstLetter", columns={"firstLetter"})
 *     },
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="Interfaces_version_name",
 *             columns={"version", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interfaces"
 *     }
 * )
 */
class ReflectedInterface implements DefinedAtInterface
{
    use RecordIDTrait;

    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="interfaces")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    use IndexedNameTrait;

    /**
     * The parent interfaces extended by this interface.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterfaceInterface", mappedBy="interface", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceInterface[]|\Doctrine\Common\Collections\Collection
     */
    protected $parentInterfaces;

    /**
     * The list of the constants of this interface.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterfaceConstant", mappedBy="interface", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceConstant[]|\Doctrine\Common\Collections\Collection
     */
    protected $constants;

    /**
     * The list of the methods of this interface.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod", mappedBy="interface", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod[]|\Doctrine\Common\Collections\Collection
     */
    protected $methods;

    use DefinedAtTrait;

    protected function __construct()
    {
        $this->parentInterfaces = new ArrayCollection();
        $this->constants = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->methods = new ArrayCollection();
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
     * Get the parent interfaces extended by this interface.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceInterface[]|\Doctrine\Common\Collections\Collection
     */
    public function getParentInterfaces(): Collection
    {
        return $this->parentInterfaces;
    }

    /**
     * Get the list of the constants of this interface.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceConstant[]|\Doctrine\Common\Collections\Collection
     */
    public function getConstants(): Collection
    {
        return $this->constants;
    }

    /**
     * Get the list of the methods of this interface.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod[]|\Doctrine\Common\Collections\Collection
     */
    public function getMethods(): Collection
    {
        return $this->methods;
    }
}
