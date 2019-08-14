<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent an interface that's a base interface of an ReflectedInterface instance.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="InterfaceInterfaces",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="InterfaceInterfaces_interface_name",
 *             columns={"interface", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interfaces implemented by interfaces"
 *     }
 * )
 */
class ReflectedInterfaceInterface
{
    use RecordIDTrait;

    /**
     * The associated interface.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterface", inversedBy="parentInterfaces")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="interface", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterface
     */
    protected $interface;

    /**
     * The name of the interface.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the interface"})
     *
     * @var string
     */
    protected $name;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface $interface
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedInterface $interface, string $name): self
    {
        $result = new static();
        $result
            ->setInterface($interface)
            ->setName($name)
        ;

        return $result;
    }

    /**
     * Get the associated interface.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterface
     */
    public function getInterface(): ReflectedInterface
    {
        return $this->interface;
    }

    /**
     * Set the associated interface.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface $value
     *
     * @return $this
     */
    public function setInterface(ReflectedInterface $value): self
    {
        $this->interface = $value;

        return $this;
    }

    /**
     * Get the name of the interface.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the interface.
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
}
