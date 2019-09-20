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
 * Represent a trait.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="Traits",
 *     indexes={
 *         @Index(name="Traits_firstLetter", columns={"firstLetter"})
 *     },
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="Traits_version_name",
 *             columns={"version", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of traits"
 *     }
 * )
 */
class ReflectedTrait implements DefinedAtInterface
{
    use RecordIDTrait;

    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="traits")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    use IndexedNameTrait;

    /**
     * The list of the properties of this trait.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTraitProperty", mappedBy="trait", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTraitProperty[]|\Doctrine\Common\Collections\Collection
     */
    protected $properties;

    /**
     * The list of the methods of this trait.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod", mappedBy="trait", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod[]|\Doctrine\Common\Collections\Collection
     */
    protected $methods;

    use DefinedAtTrait;

    protected function __construct()
    {
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
     * Get the list of the properties of this trait.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTraitProperty[]|\Doctrine\Common\Collections\Collection
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * Get the list of the methods of this trait.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod[]|\Doctrine\Common\Collections\Collection
     */
    public function getMethods(): Collection
    {
        return $this->methods;
    }
}
