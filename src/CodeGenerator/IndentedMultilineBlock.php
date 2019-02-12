<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class IndentedMultilineBlock extends CodeBlock
{
    protected $usedClasses = [];

    public function __construct(string $prefix = '{', string $suffix = '}', string $separator = '')
    {
        parent::__construct($prefix, $suffix, $separator);
    }

    public function getUsedClasses(): array
    {
        return $this->usedClasses;
    }

    public function useClass(string $fullyQualifiedClassName): self
    {
        $this->usedClasses[] = trim($fullyQualifiedClassName, '\\');
        return $this;
    }

    protected function contentToString(): string
    {
        return PHP_EOL . '    '
            . str_replace(PHP_EOL, PHP_EOL . '    ', implode($this->getSeparator() . PHP_EOL, $this->getContents()))
            . PHP_EOL;
    }
}
