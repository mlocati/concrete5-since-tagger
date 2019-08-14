<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a parameter of an interface method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="InterfaceMethodParams",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="InterfaceMethodParams_method_position",
 *             columns={"method", "position"}
 *         ),
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="InterfaceMethodParams_method_name",
 *             columns={"method", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of interface method parameters"
 *     }
 * )
 */
class ReflectedInterfaceMethodParameter extends ReflectedFunctionParameter
{
    /**
     * The interface method this parameter is for.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod", inversedBy="parameters")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="method", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod
     */
    protected $method;

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod $method
     * @param int $position
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedInterfaceMethod $method, int $position, string $name): self
    {
        return parent::createBase($position, $name)->setMethod($method);
    }

    /**
     * Get the interface method this parameter is for.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod
     */
    public function getMethod(): ReflectedInterfaceMethod
    {
        return $this->method;
    }

    /**
     * Set the interface method this parameter is for.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedInterfaceMethod $value
     *
     * @return $this
     */
    public function setMethod(ReflectedInterfaceMethod $value): self
    {
        $this->method = $value;

        return $this;
    }
}
