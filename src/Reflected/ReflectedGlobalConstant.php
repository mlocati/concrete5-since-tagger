<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\DefinedAtTrait;

/**
 * Represent a global constant.
 *
 * @\Doctrine\ORM\Mapping\Entity(
 * )
 * @\Doctrine\ORM\Mapping\Table(
 *     name="GlobalConstants",
 *     indexes={
 *         @Index(name="GlobalConstants_firstLetter", columns={"firstLetter"})
 *     },
 *     uniqueConstraints={
 *         @\Doctrine\ORM\Mapping\UniqueConstraint(
 *             name="GlobalConstants_version_name",
 *             columns={"version", "name"}
 *         )
 *     },
 *     options={
 *         "comment": "The list of global constants"
 *     }
 * )
 */
class ReflectedGlobalConstant extends ReflectedConstant
{
    /**
     * The associated core version.
     *
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="\MLocati\C5SinceTagger\Reflected\ReflectedVersion", inversedBy="globalConstants")
     * @\Doctrine\ORM\Mapping\JoinColumn(name="version", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     * @var \MLocati\C5SinceTagger\Reflected\ReflectedVersion
     */
    protected $version;

    use DefinedAtTrait;

    protected function __construct()
    {
    }

    /**
     * Create a new instance.
     *
     * @param \MLocati\C5SinceTagger\Reflected\ReflectedVersion $version
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public static function create(ReflectedVersion $version, string $name, $value): self
    {
        return parent::createBase($name, $value)
            ->setVersion($version)
            ->setDefinedAt('')
            ->setSincePhpDoc('')
        ;
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
}
