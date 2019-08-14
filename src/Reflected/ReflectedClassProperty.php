<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a class property.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassProperties",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassProperties_class_name",
 *             columns={"class", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of class properties"
 *     }
 * )
 */
class ReflectedClassProperty extends ReflectedProperty
{
    /**
     * The associated class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="properties")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="class", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    protected $class;

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
    public static function create(ReflectedClass $class, string $name): self
    {
        return parent::createBase($name)->setClass($class);
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
}
