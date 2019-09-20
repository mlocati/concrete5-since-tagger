<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Interfaces;

interface DefinedAtInterface
{
    /**
     * Get the position where this item is defined.
     *
     * @return string
     */
    public function getDefinedAt(): string;

    /**
     * Get the file where this item is defined.
     *
     * @return string
     */
    public function getDefinedAtFile(): string;

    /**
     * Get the line in the file where this item is defined.
     *
     * @return int|null
     */
    public function getDefinedAtLine(): ?int;

    /**
     * Is this a vendor item?
     *
     * @return bool
     */
    public function isVendor(): bool;

    /**
     * Get the vendor name.
     *
     * @return string
     */
    public function getVendorName(): string;
}
