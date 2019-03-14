<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class CodeBlock
{
    /** @var array */
    private $contents = [];
    /** @var string */
    private $prefix;
    /** @var string */
    private $separator;
    /** @var string */
    private $suffix;

    public function __construct(string $prefix = '', string $suffix = '', string $separator = '')
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $this->separator = $separator;
    }

    public function __toString(): string
    {
        return $this->getPrefix() . $this->contentToString() . $this->getSuffix();
    }

    public function addContentBlock(self $block): self
    {
        $this->contents[] = $block;
        return $this;
    }

    public function addContentString(string $chunk): self
    {
        $this->contents[] = $chunk;
        return $this;
    }

    protected function contentToString(): string
    {
        return implode($this->getSeparator(), $this->getContents());
    }

    protected function getContents(): array
    {
        return $this->contents;
    }

    protected function getPrefix(): string
    {
        return $this->prefix;
    }

    protected function getSeparator(): string
    {
        return $this->separator;
    }

    protected function getSuffix(): string
    {
        return $this->suffix;
    }
}
