<?php

namespace MLocati\C5SinceTagger\Extractor\PhpDoc;

class TokenExtractor
{
    /**
     * @var string
     */
    private $webroot;

    /**
     * @var \MLocati\C5SinceTagger\Extractor\PhpDoc\Extractor
     */
    private $extractor;

    /**
     * @var \stdClass
     */
    private $list = [];

    /**
     * @param string $webroot
     * @param \MLocati\C5SinceTagger\Extractor\PhpDoc\Extractor $extractor
     */
    public function __construct($webroot, Extractor $extractor = null)
    {
        $this->webroot = $webroot;
        $this->extractor = $extractor ?: new Extractor();
    }

    /**
     * @param \stdClass $item
     *
     * @return $this
     */
    public function queue(\stdClass $item)
    {
        if (!\in_array($item, $this->list, true)) {
            $this->list[] = $item;
        }

        return $this;
    }

    public function process()
    {
        $map = [];
        foreach ($this->list as $item) {
            $definedAt = isset($item->definedAt) ? $item->definedAt : '';
            if ((int) \strpos($definedAt, ':') === 0) {
                throw new \Exception('Failed to parse the definedAt field');
            }
            list($filename, $line) = \explode(':', $definedAt);
            if (!isset($map[$filename])) {
                $map[$filename] = [$line => $item];
            } elseif (isset($map[$filename][$line])) {
                throw new \Exception("Multiple items found for file {$filename} at line {$line}");
            } else {
                $map[$filename][$line] = $item;
            }
        }
        foreach ($map as $filename => $items) {
            $this->processItems($filename, $items);
        }

        $this->list = [];
    }

    /**
     * @param string $filename
     * @param array $items
     */
    private function processItems($filename, array $items)
    {
        $php = \file_get_contents("{$this->webroot}/{$filename}");
        if ($php === false) {
            throw new \Exception("Failed to read file {$filename}");
        }
        $nonWhitespaceTokens = [];
        foreach (\token_get_all($php) as $token) {
            if (!\is_array($token) || $token[0] !== T_WHITESPACE) {
                $nonWhitespaceTokens[] = $token;
            }
        }
        foreach ($items as $line => $item) {
            $item->since = $this->extractor->extractSince($this->getItemPhpDoc($nonWhitespaceTokens, $line, $item));
        }
    }

    /**
     * @param array $nonWhitespaceTokens
     * @param int $line
     * @param \stdClass $item
     *
     * @return string
     */
    private function getItemPhpDoc(array &$nonWhitespaceTokens, $line, \stdClass $item)
    {
        $firstItemIndex = null;
        foreach ($nonWhitespaceTokens as $index => $token) {
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
        $token = $nonWhitespaceTokens[$firstItemIndex - 1];

        return \is_array($token) && $token[0] === T_DOC_COMMENT ? $token[1] : '';
    }
}
