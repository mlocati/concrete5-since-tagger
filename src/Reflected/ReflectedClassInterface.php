<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent an interface implemented by a class.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassInterfaces",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassInterfaces_class_name",
 *             columns={"class", "interface"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interfaces implemented by classes"
 *     }
 * )
 */
class ReflectedClassInterface
{
    use RecordIDTrait;

    /**
     * The associated class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="interfaces")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="class", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    protected $class;

    /**
     * The name of the interface.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the interface"})
     *
     * @var string
     */
    protected $interface;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $class
     * @param string $interface
     *
     * @return static
     */
    public static function create(ReflectedClass $class, string $interface): self
    {
        $result = new static();
        $result
            ->setClass($class)
            ->setInterface($interface)
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
     * Get the name of the interface.
     *
     * @return string
     */
    public function getInterface(): string
    {
        return $this->interface;
    }

    /**
     * Set the name of the interface.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInterface(string $value): self
    {
        $this->interface = $value;

        return $this;
    }
}
