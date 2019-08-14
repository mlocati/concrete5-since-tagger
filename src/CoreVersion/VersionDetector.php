<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\CoreVersion;

class VersionDetector
{
    public function detectVersion(string $webroot): string
    {
        $file = "{$webroot}/concrete/config/concrete.php";
        if (\is_file($file)) {
            return $this->detectVersionFromConfigConcreteFile($file);
        }

        return '';
    }

    private function detectVersionFromConfigConcreteFile(string $file): string
    {
        $php = \file_get_contents($file);
        if ($php === false) {
            throw new \Exception("Failed to read the file {$file}");
        }
        $usefulTokens = [];
        foreach (\token_get_all($php) as $token) {
            if (!\is_array($token) || !\in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)) {
                $usefulTokens[] = $token;
            }
        }

        return $this->detectVersionFromConfigConcreteTokens($usefulTokens);
    }

    private function detectVersionFromConfigConcreteTokens(array $usefulTokens): string
    {
        $arrayStart = null;
        $count = \count($usefulTokens);
        for ($index = 0; $index < $count - 3; $index++) {
            if (\is_array($usefulTokens[$index]) && $usefulTokens[$index][0] === T_RETURN) {
                if ($usefulTokens[$index + 1] === '[') {
                    $arrayStart = $index + 2;
                    break;
                }
                if (\is_array($usefulTokens[$index + 1]) && $usefulTokens[$index + 1][0] === T_ARRAY && $usefulTokens[$index + 2] === '(') {
                    $arrayStart = $index + 3;
                    break;
                }
            }
        }
        if ($arrayStart === null) {
            throw new \Exception('Failed to parse the configuration file');
        }
        $deep = 0;
        for ($index = $arrayStart; $index < $count - 3; $index++) {
            $token = $usefulTokens[$index];
            if ($token === '[' || $token === '(' || $token === '{' || (\is_array($token) && $token[0] === T_CURLY_OPEN)) {
                $deep++;
                continue;
            }
            if ($token === ']' || $token === ')' || $token === '}') {
                $deep--;
                continue;
            }
            if ($deep !== 0) {
                continue;
            }
            if (
                \is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && \preg_match('/^["\']version["\']$/', $token[1])
                && \is_array($usefulTokens[$index + 1]) && $usefulTokens[$index + 1][0] === T_DOUBLE_ARROW
                && \is_array($usefulTokens[$index + 2]) && $usefulTokens[$index + 2][0] === T_CONSTANT_ENCAPSED_STRING
            ) {
                $m = null;
                if (!\preg_match('/^(["\'])(.+)\1$/', $usefulTokens[$index + 2][1], $m)) {
                    throw new \Exception('Failed to parse the configuration file');
                }

                return $m[2];
            }
            break;
        }

        throw new \Exception('Failed to parse the configuration file');
    }
}
