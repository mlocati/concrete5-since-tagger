<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Traits;

trait RecordIDTrait
{
    /**
     * The record internal identifier.
     *
     * @\Doctrine\ORM\Mapping\Column(type="integer", options={"unsigned": true, "comment": "Record internal identifier"})
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $id;

    /**
     * Get the record internal identifier.
     *
     * @return int|null return NULL if not persisted yet
     */
    public function getID(): ?int
    {
        return $this->id;
    }
}
