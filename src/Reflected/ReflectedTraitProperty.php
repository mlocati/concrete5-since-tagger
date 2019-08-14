<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a trait property.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="TraitProperties",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="TraitProperties_trait_name",
 *             columns={"trait", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of trait properties"
 *     }
 * )
 */
class ReflectedTraitProperty extends ReflectedProperty
{
    /**
     * The associated trait.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTrait", inversedBy="properties")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="trait", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTrait
     */
    protected $trait;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTrait $trait
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedTrait $trait, string $name): self
    {
        return parent::createBase($name)->setTrait($trait);
    }

    /**
     * Get the associated trait.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTrait
     */
    public function getTrait(): ReflectedTrait
    {
        return $this->trait;
    }

    /**
     * Set the associated trait.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTrait $value
     *
     * @return $this
     */
    public function setTrait(ReflectedTrait $value): self
    {
        $this->trait = $value;

        return $this;
    }
}
