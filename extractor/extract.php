<?php

namespace MLocati\C5SinceTagger\Extractor;

if (!\defined('PHP_VERSION_ID') || \PHP_VERSION_ID < 50500) {
    \fprintf(\STDERR, 'Minimum supported PHP version is 5.5.0 (you are using ' . PHP_VERSION . ").\n");
    exit(1);
}

require_once __DIR__ . '/autoload.php';

if (\defined('C5_EXECUTE')) {
    /** @var array $args */
    $scriptArguments = isset($args) && \is_array($args) ? \array_merge([__FILE__], $args) : [];
} else {
    /** @var array $argv */
    $scriptArguments = isset($argv) && \is_array($argv) ? $argv : [];
}

/* @var array $argv */
if (!isset($scriptArguments[1]) || !\is_string($scriptArguments[1]) || $scriptArguments[1] === '') {
    \fprintf(STDERR, "Missing output file name\n");
    exit(1);
}
$outputFileName = $scriptArguments[1];

if (!\defined('C5_EXECUTE')) {
    /* @var array $argv */
    if (!isset($scriptArguments[2]) || !\is_string($scriptArguments[2]) || $scriptArguments[2] === '') {
        \fprintf(STDERR, "Missing concrete5 webroot directory\n");
        exit(1);
    }
    $webroot = $scriptArguments[2];
    if (!\is_dir($webroot)) {
        \fprintf(\STDERR, "Unable to find the directory {$webroot}\n");
        exit(1);
    }
    $dispatcher = "{$webroot}/concrete/dispatcher.php";
    if (!\is_file($dispatcher)) {
        \fprintf(\STDERR, "Unable to find the file {$dispatcher}\n");
        exit(1);
    }
    $_SERVER['SCRIPT_FILENAME'] = "{$webroot}/index.php";

    echo 'Bootstrapping concrete5... ';
    require $dispatcher;
    echo "done.\n";
}

echo 'Determining actual version... ';
$webroot = \rtrim(\str_replace(\DIRECTORY_SEPARATOR, '/', \DIR_BASE), '/');
$version = (new Filesystem\VersionDetector())->detectVersion($webroot);
if ($version === null) {
    \fprintf(STDERR, "Failed to determine the actual concrete5 version\n");
    exit(1);
}
echo "{$version}\n";

$serializer = new Serializer($version, $webroot);

echo 'Loading all files... ';
$count = (new Filesystem\FileLoader($webroot, $version))->loadAllFiles();
echo "{$count} files loaded.\n";

echo 'Loading aliases... ';
list($count, $total) = $serializer->loadClassAliases();
echo "{$count}/{$total} aliases loaded.\n";

$serializedData = $serializer->serialize();

echo 'Exporting data as a JSON file... ';
$json = toJson($serializedData);
if (!\file_put_contents($outputFileName, $json)) {
    \fprintf(STDERR, "ERROR!\n");
    exit(1);
}
echo "done.\n";

exit(0);

/**
 * @param mixed $var
 *
 * @return string
 */
function toJson($var)
{
    static $flags = null;
    if ($flags === null) {
        $flags = 0 +
            (\defined('JSON_PRETTY_PRINT') ? \JSON_PRETTY_PRINT : 0) +
            (\defined('JSON_UNESCAPED_SLASHES') ? \JSON_UNESCAPED_SLASHES : 0) +
            (\defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0)
        ;
    }

    return \json_encode($var, $flags);
}
