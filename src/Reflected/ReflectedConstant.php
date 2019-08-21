<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Reflected;

use MLocati\C5SinceTagger\Traits\IndexedNameTrait;
use MLocati\C5SinceTagger\Traits\JsonTrait;
use MLocati\C5SinceTagger\Traits\RecordIDTrait;

/**
 * Base class for interface constants, class constants, global constants.
 */
abstract class ReflectedConstant
{
    use RecordIDTrait;

    use IndexedNameTrait;

    /**
     * The JSON-encoded value of the constant.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "JSON-encoded value of the constant"})
     *
     * @var string
     */
    protected $value;

    use JsonTrait;

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
    protected static function createBase(string $name, $value): self
    {
        $result = new static();
        $result
            ->setName($name)
            ->setValue($value)
        ;

        return $result;
    }

    /**
     * Get the value of the constant.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->fromJson($this->value);
    }

    /**
     * Set the value of the constant.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value): self
    {
        $this->value = $this->toJson($value);

        return $this;
    }
}
