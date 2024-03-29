<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Interfaces;

interface VisibilityInterface
{
    /**
     * Visibility flag: private.
     *
     * @var string
     */
    const PRIVATE = 'private';

    /**
     * Visibility flag: protected.
     *
     * @var string
     */
    const PROTECTED = 'protected';

    /**
     * Visibility flag: public.
     *
     * @var string
     */
    const PUBLIC = 'public';

    /**
     * Get the visibility modifier.
     *
     * @return string
     */
    public function getVisibility(): string;
}
