<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Traits;

trait IndexedNameTrait
{
    /**
     * The name.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "Name"})
     *
     * @var string
     */
    protected $name;

    /**
     * The first letter of the name (without namespace).
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=1, nullable=false, options={"fixed": true, "comment": "First letter of the name (without namespace)"})
     *
     * @var string
     */
    protected $firstLetter;

    /**
     * Get the name of the class.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the first letter of the name (without namespace).
     *
     * @return string
     */
    public function getFirstLetter(): string
    {
        return $this->firstLetter;
    }

    /**
     * Set the name of the class.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setName(string $value): self
    {
        $firstLetter = '';
        $chunks = \explode('\\', $value);
        for (;;) {
            $chunk = \array_pop($chunks);
            if ($chunk === null) {
                break;
            }
            if ($chunk === '') {
                continue;
            }
            $firstLetter = \strtolower($chunk[0]);
            break;
        }

        $this->name = $value;
        $this->firstLetter = $firstLetter;

        return $this;
    }
}
