<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represent an interface method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="InterfaceMethods",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="InterfaceMethods_interface_name",
 *             columns={"interface", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interface methods"
 *     }
 * )
 */
class ReflectedInterfaceMethod extends ReflectedFunction
{
    /**
     * The associated interface.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterface", inversedBy="methods")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="interface", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterface
     */
    protected $interface;

    /**
     * Is this method static?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method static?"})
     *
     * @var bool
     */
    protected $static;

    /**
     * The list of the method parameters.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethodParameter", mappedBy="method", cascade={"persist", "remove"})
     * @\Doctrine\ORM\Mapping\OrderBy({"position"="ASC"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethodParameter[]|\Doctrine\Common\Collections\Collection
     */
    protected $parameters;

    protected function __construct()
    {
        $this->parameters = new ArrayCollection();
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
        return parent::createBase($name)
            ->setInterface($interface)
            ->setStatic(false)
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

    /**
     * Is this method static?
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * Is this method static?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setStatic(bool $value): self
    {
        $this->static = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethodParameter
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::createParameter()
     */
    public function createParameter(int $position, string $name): ReflectedFunctionParameter
    {
        return ReflectedInterfaceMethodParameter::create($this, $position, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethodParameter[]|\Doctrine\Common\Collections\Collection
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::getParameters()
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
