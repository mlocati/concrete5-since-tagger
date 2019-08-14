<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a parameter of a trait method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="TraitMethodParams",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="TraitMethodParams_method_position",
 *             columns={"method", "position"}
 *         ),
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="TraitMethodParams_method_name",
 *             columns={"method", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of trait method parameters"
 *     }
 * )
 */
class ReflectedTraitMethodParameter extends ReflectedFunctionParameter
{
    /**
     * The trait method this parameter is for.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod", inversedBy="parameters")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="method", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod
     */
    protected $method;

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod $method
     * @param int $position
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedTraitMethod $method, int $position, string $name): self
    {
        return parent::createBase($position, $name)->setMethod($method);
    }

    /**
     * Get the trait method this parameter is for.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod
     */
    public function getMethod(): ReflectedTraitMethod
    {
        return $this->method;
    }

    /**
     * Set the trait method this parameter is for.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethod $value
     *
     * @return $this
     */
    public function setMethod(ReflectedTraitMethod $value): self
    {
        $this->method = $value;

        return $this;
    }
}
