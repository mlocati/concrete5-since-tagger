<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a parameter of a class method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassMethodParams",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassMethodParams_method_position",
 *             columns={"method", "position"}
 *         ),
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassMethodParams_method_name",
 *             columns={"method", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of class method parameters"
 *     }
 * )
 */
class ReflectedClassMethodParameter extends ReflectedFunctionParameter
{
    /**
     * The class method this parameter is for.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassMethod", inversedBy="parameters")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="method", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod
     */
    protected $method;

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod $method
     * @param int $position
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedClassMethod $method, int $position, string $name): self
    {
        return parent::createBase($position, $name)->setMethod($method);
    }

    /**
     * Get the class method this parameter is for.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod
     */
    public function getMethod(): ReflectedClassMethod
    {
        return $this->method;
    }

    /**
     * Set the class method this parameter is for.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClassMethod $value
     *
     * @return $this
     */
    public function setMethod(ReflectedClassMethod $value): self
    {
        $this->method = $value;

        return $this;
    }
}
