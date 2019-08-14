<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent the aliases applied to traits imported by classes.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassTraitAliases",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassTraitAliases_classTrait_alias",
 *             columns={"classTrait", "alias"}
 *         )
 *     },
 *     options={
 *         "comment": "Aliases applied to traits imported by classes"
 *     }
 * )
 */
class ReflectedClassTraitAlias
{
    use RecordIDTrait;

    /**
     * The associated trait imported by a class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClassTrait", inversedBy="aliases")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="classTrait", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait
     */
    protected $classTrait;

    /**
     * The original name of the trait method.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Original name of the trait method"})
     *
     * @var string
     */
    protected $originalName;

    /**
     * The alias of the trait method.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Alias of the trait method"})
     *
     * @var string
     */
    protected $alias;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait $classTrait
     * @param string $originalName
     * @param string $alias
     *
     * @return static
     */
    public static function create(ReflectedClassTrait $classTrait, string $originalName, string $alias): self
    {
        $result = new static();
        $result
            ->setClassTrait($classTrait)
            ->setOriginalName($originalName)
            ->setAlias($alias)
        ;

        return $result;
    }

    /**
     * Get the associated trait imported by a class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait
     */
    public function getClassTrait(): ReflectedClassTrait
    {
        return $this->classTrait;
    }

    /**
     * Set the associated trait imported by a class.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClassTrait $value
     *
     * @return $this
     */
    public function setClassTrait(ReflectedClassTrait $value): self
    {
        $this->classTrait = $value;

        return $this;
    }

    /**
     * Get the original name of the trait method.
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Set the original name of the trait method.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setOriginalName(string $value): self
    {
        $this->originalName = $value;

        return $this;
    }

    /**
     * Get the alias of the trait method.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Set the alias of the trait method.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setAlias(string $value): self
    {
        $this->alias = $value;

        return $this;
    }
}
