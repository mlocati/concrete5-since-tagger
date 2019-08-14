<?php

namespace MLocati\C5SinceTagger\Extractor;

\spl_autoload_register(function ($class) {
    if (\strpos($class, __NAMESPACE__ . '\\') === 0) {
        require_once __DIR__ . \str_replace('\\', \DIRECTORY_SEPARATOR, \substr($class, \strlen(__NAMESPACE__))) . '.php';
    }
});
