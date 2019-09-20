<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Interfaces\DefinedAtInterface;
use MLocati\C5SinceTagger\Interfaces\VisibilityInterface;
use MLocati\C5SinceTagger\Traits\DefinedAtTrait;

/**
 * Represent a class constant.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassConstants",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassConstants_class_name",
 *             columns={"class", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of class constants"
 *     }
 * )
 */
class ReflectedClassConstant extends ReflectedConstant implements DefinedAtInterface, VisibilityInterface
{
    /**
     * The associated class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="constants")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="class", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    protected $class;

    /**
     * The visibility modifier.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=20, nullable=false, options={"comment": "The visibility modifier"})
     *
     * @var string
     *
     * @see \MLocati\C5SinceTagger\Interfaces\VisibilityInterface
     */
    protected $visibility;

    use DefinedAtTrait;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $class
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public static function create(ReflectedClass $class, string $name, $value): self
    {
        return parent::createBase($name, $value)
            ->setClass($class)
            ->setVisibility(VisibilityInterface::PUBLIC)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;
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
}
