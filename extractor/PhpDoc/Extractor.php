<?php

namespace MLocati\C5SinceTagger\Extractor\PhpDoc;

class Extractor
{
    /**
     * @param string $phpDoc
     *
     * @return string
     */
    public function extractSince($phpDoc)
    {
        $phpDoc = \trim((string) $phpDoc);
        if ($phpDoc === '') {
            return '';
        }
        $m = null;
        if (!\preg_match('_^/\*\*+(.*)\*+/$_ms', $phpDoc, $m)) {
            throw new \Exception('Not a phpdoc');
        }
        $sinces = [];
        foreach (\preg_split('/[\r\n]+/', $m[1], -1, PREG_SPLIT_NO_EMPTY) as $line) {
            if (\preg_match('/^.*?@since\s+(\S.*?)\s*$/i', $line, $m)) {
                $sinces[] = $m[1];
            }
        }
        // @todo
        return \implode("\n", $sinces);
    }
}
