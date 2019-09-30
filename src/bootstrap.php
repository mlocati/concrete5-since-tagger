<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use MLocati\C5SinceTagger\Application;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    $rootDir = dirname(__DIR__);
} else {
    $autoload = dirname(__DIR__, 3) . '/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        $rootDir = dirname(__DIR__, 4);
    } else {
        fprintf(STDERR, 'You must install the dependencies using `composer install`' . PHP_EOL);
        exit(1);
    }
}
unset($autoload);
Dotenv::create($rootDir)->load();
unset($rootDir);

return new Application();
