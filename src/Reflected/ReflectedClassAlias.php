<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Represent a class alias.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="ClassAliases",
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="ClassAliases_version_alias",
 *             columns={"version", "alias"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of class aliases"
 *     }
 * )
 */
class ReflectedClassAlias
{
    use RecordIDTrait;

    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="classAliases")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    /**
     * The name of the alias.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=false, options={"comment": "Name of the alias"})
     *
     * @var string
     */
    protected $alias;

    /**
     * The actual class.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedClass", inversedBy="aliases")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="actualClass", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $actualClass;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param string $alias
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $actualClass
     *
     * @return static
     */
    public static function create(string $alias, ReflectedClass $actualClass): self
    {
        $result = new static();
        $result
            ->setAlias($alias)
            ->setActualClass($actualClass)
        ;

        return $result;
    }

    /**
     * Get the associated core version.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    public function getVersion(): ReflectedVersion
    {
        return $this->version;
    }

    /**
     * Get the name of the alias.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Set the name of the alias.
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

    /**
     * Get the actual class.
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedClass
     */
    public function getActualClass(): ReflectedClass
    {
        return $this->actualClass;
    }

    /**
     * Set the actual class.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedClass $value
     *
     * @return $this
     */
    public function setActualClass(ReflectedClass $value): self
    {
        $this->version = $value->getVersion();
        $this->actualClass = $value;

        return $this;
    }
}
