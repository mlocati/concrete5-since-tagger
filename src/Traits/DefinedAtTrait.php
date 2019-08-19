<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\Traits;

trait DefinedAtTrait
{
    /**
     * The file where this item is defined.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=190, nullable=false, options={"comment": "File where this item is defined"})
     *
     * @var string
     */
    protected $definedAtFile;

    /**
     * The line in the file where this item is defined.
     *
     * @\Doctrine\ORM\Mapping\Column(type="integer", nullable=true, options={"unsigned": true, "comment": "Line in the file where this item is defined"})
     *
     * @var int|null
     */
    protected $definedAtLine;

    /**
     * The value of the since tag extracted from PHPDoc.
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=50, nullable=false, options={"comment": "Value of the since tag extracted from PHPDoc"})
     *
     * @var string
     */
    protected $sincePhpDoc;

    /**
     * Get the position where this item is defined.
     *
     * @return string
     */
    public function getDefinedAt(): string
    {
        if ($this->definedAtLine === null) {
            return $this->definedAtFile;
        }

        return $this->definedAtFile . ':' . $this->definedAtLine;
    }

    /**
     * Set the position where this item is defined.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setDefinedAt(string $value): self
    {
        $p = $value === '' ? false : \strripos($value, ':');
        if ($p === false) {
            $this->definedAtFile = $value;
            $this->definedAtLine = null;
        } else {
            $this->definedAtFile = \substr($value, 0, $p);
            $this->definedAtLine = (int) \substr($value, $p + 1);
        }

        return $this;
    }

    /**
     * Get the file where this item is defined.
     *
     * @return string
     */
    public function getDefinedAtFile(): string
    {
        return $this->definedAtFile;
    }

    /**
     * Get the line in the file where this item is defined.
     *
     * @return int|null
     */
    public function getDefinedAtLine(): ?int
    {
        return $this->definedAtLine;
    }

    /**
     * Get the value of the since tag extracted from PHPDoc.
     *
     * @return string
     */
    public function getSincePhpDoc(): string
    {
        return $this->sincePhpDoc;
    }

    /**
     * Set the value of the since tag extracted from PHPDoc.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setSincePhpDoc(string $value): self
    {
        $this->sincePhpDoc = $value;

        return $this;
    }

    /**
     * Is this a vendor item?
     *
     * @return bool
     */
    public function isVendor(): bool
    {
        return \strpos($this->getDefinedAtFile(), 'concrete/vendor/') === 0;
    }

    /**
     * Get the vendor name.
     *
     * @return string
     */
    public function getVendorName(): string
    {
        if ($this->isVendor() === false) {
            return '';
        }
        $m = null;
        if (!\preg_match('%^concrete/vendor/([^/]+/[^/]+)/%', $this->getDefinedAtFile(), $m)) {
            throw new \Exception('Unable to extract the vendor from ' . $this->getDefinedAtFile());
        }

        return $m[1];
    }
}
