<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Diff;

class Patcher
{
    /**
     * @var string
     */
    private $webroot;

    public function __construct(string $webroot)
    {
        $this->webroot = $webroot;
    }

    /**
     * @param \MLocati\C5SinceTagger\Diff\Patch[] $patches
     * @param string $file
     */
    public function apply(array $patches, string $file): void
    {
        \usort($patches, function (Patch $a, Patch $b): int {
            return $b->getLine() - $a->getLine();
        });
        $contents = \file_get_contents("{$this->webroot}/{$file}");
        if ($contents === false || $contents === '') {
            throw new \Exception("Failed to read the file {$file}");
        }
        $defaultEOL = $this->detectEOL($contents);
        $tokens = \token_get_all($contents);
        foreach ($patches as $patch) {
            $this->applyPatch($patch, $tokens, $defaultEOL);
        }
        $contents = '';
        foreach ($tokens as $token) {
            if (\is_array($token)) {
                $contents .= $token[1];
            } else {
                $contents .= $token;
            }
        }
        if (\file_put_contents("{$this->webroot}/{$file}", $contents) === false) {
            throw new \Exception("Failed to write to the file {$file}");
        }
    }

    private function applyPatch(Patch $patch, array &$tokens, string $defaultEOL): void
    {
        [$phpDocCommentIndex, $defaultIndentation] = $this->getOrCreatePHPDocComment($tokens, $patch->getLine(), $defaultEOL);
        $tokens[$phpDocCommentIndex][1] = $this->applyPatchToPHPDoc($patch->getNewSince(), $tokens[$phpDocCommentIndex][1], $defaultEOL, $defaultIndentation);
    }

    private function applyPatchToPHPDoc(string $since, string $phpDoc, string $defaultEOL, string $indentation): string
    {
        $m = null;
        $eol = $this->detectEOL($phpDoc, $defaultEOL);
        $lines = \explode("\n", \str_replace("\r", "\n", \str_replace("\r\n", "\n", $phpDoc)));
        $count = \count($lines);
        $sinceLines = \preg_split('/[\r\n]+/', $since, -1, PREG_SPLIT_NO_EMPTY);
        for ($index = 0; $index < $count; $index++) {
            if (\preg_match('/^(.*)@since(?:.*?)(\s*\*\/)?$/', $lines[$index], $m)) {
                if ($sinceLines === []) {
                    $lines[$index] = $m[1] . ($m[2] ?? '');
                    if (\trim($lines[$index], " \t\0\x0B*") === '') {
                        \array_splice($lines, $index, 1);
                        $index--;
                        $count--;
                    }
                } else {
                    $first = true;
                    for (;;) {
                        $sinceLine = \array_shift($sinceLines);
                        if ($sinceLine === null) {
                            break;
                        }
                        if ($first === true) {
                            $lines[$index] = $m[1] . "@since {$sinceLine}";
                            $first = false;
                        } else {
                            $index++;
                            \array_splice($lines, $index, 0, "{$indentation} * @since {$sinceLine}");
                            $count++;
                        }
                        if ($sinceLines === [] && isset($m[2])) {
                            $lines[$index] .= $m[2];
                        }
                    }
                }
            }
        }
        if ($sinceLines !== []) {
            if ($count === 1) {
                if (!\preg_match('_^(.*?)\s*(\*+/)$_', $lines[0], $m)) {
                    throw new \Exception('Invalid phpdoc');
                }
                $lines = [\rtrim($m[1])];
                foreach ($sinceLines as $sinceLine) {
                    $lines[] = "{$indentation} * @since {$sinceLine}";
                }
                $lines[] = "{$indentation} " . \ltrim($m[2]);
            } else {
                $p = \strpos($lines[1], '*');
                $prefix = $p === false ? "{$indentation} *" : \substr($lines[1], 0, $p + 1);
                for (;;) {
                    $sinceLine = \array_shift($sinceLines);
                    if ($sinceLine === null) {
                        break;
                    }
                    \array_splice($lines, $count - 1, 0, "{$prefix} @since {$sinceLine}");
                    $count++;
                }
            }
        }

        return \implode($eol, $lines);
    }

    private function detectEOL(string $contents, $default = "\n"): string
    {
        $eolPOSIX = \substr_count($contents, "\n");
        $eolWindows = \substr_count($contents, "\r\n");
        $eolMac = \substr_count($contents, "\r");
        if ($eolPOSIX === 0 && $eolMac === 0) {
            return $default;
        }
        if ($eolWindows === 0) {
            return $eolMac > $eolPOSIX ? "\r" : "\n";
        }
        if ($eolPOSIX > $eolMac) {
            return $eolWindows > $eolPOSIX ? "\r\n" : "\n";
        }

        return $eolWindows > $eolMac ? "\r\n" : "\r";
    }

    private function getOrCreatePHPDocComment(array &$tokens, int $line, string $eol): array
    {
        $firstItemIndex = null;
        foreach ($tokens as $index => $token) {
            if (\is_array($token)) {
                if ($token[2] === $line) {
                    $firstItemIndex = $index;
                    break;
                }
                if ($token[2] > $line) {
                    throw new \Exception('Unable to find the first useful token');
                }
            }
        }
        if ($firstItemIndex === null || $firstItemIndex === 0) {
            throw new \Exception('Unable to find the first useful token');
        }
        $isStraightWhitespace = \is_array($tokens[$firstItemIndex]) && $tokens[$firstItemIndex][0] === T_WHITESPACE && \preg_match('/^[ \t]+$/', $tokens[$firstItemIndex][1]);
        $indentation = '';
        for ($index = $firstItemIndex - 1; $index >= 0; $index--) {
            $token = $tokens[$index];
            if (!\is_array($token)) {
                break;
            }
            if ($token[0] === T_WHITESPACE) {
                $first = true;
                foreach (\preg_split('/[\r\n]+/', $token[1]) as $s) {
                    if ($first === true) {
                        $first = false;
                    } elseif (\strlen($s) > \strlen($indentation)) {
                        $indentation = $s;
                    }
                }
                continue;
            }
            if ($token[0] === T_DOC_COMMENT) {
                return [$index, $indentation];
            }
            break;
        }
        if ($indentation === '' && $index === $firstItemIndex - 1 && $isStraightWhitespace) {
            $indentation = $tokens[$firstItemIndex][1];
            //$tokens[$firstItemIndex][1] .= $eol . $tokens[$firstItemIndex][1];
            $insertAt = $firstItemIndex + 1;
        } else {
            $insertAt = $firstItemIndex;
        }
        \array_splice($tokens, $insertAt, 0, [
            [
                T_DOC_COMMENT,
                '/** */',
                $line - 1,
            ],
            [
                T_WHITESPACE,
                "{$eol}{$indentation}",
                $line - 1,
            ],
        ]);

        return [$insertAt, $indentation];
    }
}
