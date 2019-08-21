<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represent a global function.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="GlobalFunctions",
 *     indexes={
 *         @Index(name="GlobalFunctions_firstLetter", columns={"firstLetter"})
 *     },
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="GlobalFunctions_version_name",
 *             columns={"version", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of global functions"
 *     }
 * )
 */
class ReflectedGlobalFunction extends ReflectedFunction
{
    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="globalFunctions")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    /**
     * The list of the function parameters.
     *
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunctionParameter", mappedBy="globalFunction", cascade={"persist", "remove"})
     * @\Doctrine\ORM\Mapping\OrderBy({"position"="ASC"})
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunctionParameter[]|\Doctrine\Common\Collections\Collection
     */
    protected $parameters;

    protected function __construct()
    {
        $this->parameters = new ArrayCollection();
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $version
     * @param string $name
     */
    public static function create(ReflectedVersion $version, string $name): self
    {
        return parent::createBase($name)->setVersion($version);
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
     * Set the associated core version.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $value
     *
     * @return $this
     */
    public function setVersion(ReflectedVersion $value): self
    {
        $this->version = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunctionParameter
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::createParameter()
     */
    public function createParameter(int $position, string $name): ReflectedFunctionParameter
    {
        return ReflectedGlobalFunctionParameter::create($this, $position, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MLocati\C5SinceTagger\Reflected\ReflectedGlobalFunctionParameter[]|\Doctrine\Common\Collections\Collection
     *
     * @see \MLocati\C5SinceTagger\Reflected\ReflectedFunction::getParameters()
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }
}
