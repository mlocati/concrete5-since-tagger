<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\DefinedAtTrait;

/**
 * Represent an interface constant.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="InterfaceConstants",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="InterfaceConstants_interface_name",
 *             columns={"interface", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interface constants"
 *     }
 * )
 */
class ReflectedInterfaceConstant extends ReflectedConstant
{
    /**
     * The associated interface.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterface", inversedBy="constants")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="interface", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterface
     */
    protected $interface;

    use DefinedAtTrait;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterface $interface
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public static function create(ReflectedInterface $interface, string $name, $value): self
    {
        return parent::createBase($name, $value)->setInterface($interface)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;
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
     */
    public function setInterface(ReflectedInterface $value): self
    {
        $this->interface = $value;

        return $this;
    }
}
