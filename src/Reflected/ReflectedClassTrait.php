<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent an trait used by a class.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassTraits",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassTraits_class_trait",
 *             columns={"class", "trait"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of traits used by classes"
 *     }
 * )
 */
class ReflectedClassTrait
{
    use RecordIDTrait;

    /**
     * The associated class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="traits")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="class", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    protected $class;

    /**
     * The name of the trait.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the trait"})
     *
     * @var string
     */
    protected $trait;

    /**
     * The list of aliases of the trait methods.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassTraitAlias", mappedBy="classTrait", cascade={"persist", "remove"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassTraitAlias[]|\Doctrine\Common\Collections\Collection
     */
    protected $aliases;

    protected function __construct()
    {
        $this->aliases = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $class
     * @param string $trait
     *
     * @return static
     */
    public static function create(ReflectedClass $class, string $trait): self
    {
        $result = new static();
        $result
            ->setClass($class)
            ->setTrait($trait)
        ;

        return $result;
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
     *
     * @return $this
     */
    public function setClass(ReflectedClass $value): self
    {
        $this->class = $value;

        return $this;
    }

    /**
     * Get the name of the trait.
     *
     * @return string
     */
    public function getTrait(): string
    {
        return $this->trait;
    }

    /**
     * Set the name of the trait.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setTrait(string $value): self
    {
        $this->trait = $value;

        return $this;
    }

    /**
     * Get the list of aliases of the trait methods.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassTraitAlias[]|\Doctrine\Common\Collections\Collection
     */
    public function getAliases(): Collection
    {
        return $this->aliases;
    }
}
