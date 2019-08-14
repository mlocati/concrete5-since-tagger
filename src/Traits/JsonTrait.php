<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Traits;

trait JsonTrait
{
    protected function toJson($var): string
    {
        static $flags = null;
        if ($flags === null) {
            $flags = 0
                + JSON_PRETTY_PRINT +
                +JSON_UNESCAPED_SLASHES
                + (\defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0)
            ;
        }

        return \json_encode($var, $flags);
    }

    protected function fromJson(string $json)
    {
        static $flags = null;
        if ($flags === null) {
            $flags = 0
                + (\defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0)
            ;
        }

        return \json_decode($json, true, 512, $flags);
    }
}
