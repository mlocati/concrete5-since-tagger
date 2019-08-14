<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represent a trait method.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="TraitMethods",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="TraitMethods_trait_name",
 *             columns={"trait", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of trait methods"
 *     }
 * )
 */
class ReflectedTraitMethod extends ReflectedFunction
{
    /**
     * The associated trait.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTrait", inversedBy="methods")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="trait", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTrait
     */
    protected $trait;

    /**
     * The visibility modifier.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=20, nullable=false, options={"comment": "The visibility modifier"})
     *
     * @var string
     *
     * @see \MLocati\C5SinceTagger\Reflected\Visibility
     */
    protected $visibility;

    /**
     * Is this method abstract?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method abstract?"})
     *
     * @var bool
     */
    protected $abstract;

    /**
     * Is this method static?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method static?"})
     *
     * @var bool
     */
    protected $static;

    /**
     * Is this method final?
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean", nullable=false, options={"comment": "Is this method final?"})
     *
     * @var bool
     */
    protected $final;

    /**
     * The list of the method parameters.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedTraitMethodParameter", mappedBy="method", cascade={"persist", "remove"})
     * @\Doctrine\ORM\Mapping\OrderBy({"position"="ASC"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethodParameter[]|\Doctrine\Common\Collections\Collection
     */
    protected $parameters;

    protected function __construct()
    {
        $this->parameters = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTrait $trait
     * @param string $name
     *
     * @return static
     */
    public static function create(ReflectedTrait $trait, string $name): self
    {
        return parent::createBase($name)
            ->setTrait($trait)
            ->setVisibility(Visibility::PUBLIC)
            ->setAbstract(false)
            ->setStatic(false)
            ->setFinal(false)
        ;
    }

    /**
     * Get the associated trait.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTrait
     */
    public function getTrait(): ReflectedTrait
    {
        return $this->trait;
    }

    /**
     * Set the associated trait.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedTrait $value
     */
    public function setTrait(ReflectedTrait $value): self
    {
        $this->trait = $value;

        return $this;
    }

    /**
     * Get the visibility modifier.
     *
     * @return string
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Get the visibility modifier.
     *
     * @param string $visibility
     */
    public function setVisibility(string $value): self
    {
        $this->visibility = $value;

        return $this;
    }

    /**
     * Is this method abstract?
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Is this method abstract?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setAbstract(bool $value): self
    {
        $this->abstract = $value;

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
     * Is this method final?
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * Is this method final?
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setFinal(bool $value): self
    {
        $this->final = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethodParameter
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::createParameter()
     */
    public function createParameter(int $position, string $name): ReflectedFunctionParameter
    {
        return ReflectedTraitMethodParameter::create($this, $position, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedTraitMethodParameter[]|\Doctrine\Common\Collections\Collection
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::getParameters()
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
