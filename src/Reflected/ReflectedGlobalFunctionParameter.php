<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

/**
 * Represent a parameter of a global function.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="GlobalFunctionsParams",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="GlobalFunctionsParams_function_position",
 *             columns={"globalFunction", "position"}
 *         ),
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="GlobalFunctionsParams_function_name",
 *             columns={"globalFunction", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of global function parameters"
 *     }
 * )
 */
class ReflectedGlobalFunctionParameter extends ReflectedFunctionParameter
{
    /**
     * The global function this parameter is for.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction", inversedBy="parameters")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="globalFunction", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction
     */
    protected $globalFunction;

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction $globalFunction
     * @param int $position
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedGlobalFunction $globalFunction, int $position, string $name): self
    {
        return parent::createBase($position, $name)->setGlobalFunction($globalFunction);
    }

    /**
     * Get the global function this parameter is for.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction
     */
    public function getGlobalFunction(): ReflectedGlobalFunction
    {
        return $this->globalFunction;
    }

    /**
     * Set the global function this parameter is for.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunction $value
     *
     * @return $this
     */
    public function setGlobalFunction(ReflectedGlobalFunction $value): self
    {
        $this->globalFunction = $value;

        return $this;
    }
}
