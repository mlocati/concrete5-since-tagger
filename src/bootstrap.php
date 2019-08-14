<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use MLocati\C5SinceTagger\Application;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
unset($autoload);
if (!class_exists(Application::class)) {
    fprintf(STDERR, 'You must install the dependencies using `composer install`' . PHP_EOL);
    exit(1);
}
Dotenv::create([__DIR__ . '/..'])->load();

return new Application();
