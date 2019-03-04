<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class Type
{
    public const BOOL = 'bool';
    public const FLOAT = 'float';
    public const INT = 'int';
    public const MIXED = null;
    public const SELF = 'self';
    public const STATIC = 'static';
    public const STRING = 'string';
    public const VOID = 'void';
    private $isCollection;
    private $isNullable;
    private $isScalar;
    private $namespace;
    private $shortName;

    public function __construct(
        ?string $shortName = null,
        ?string $namespace = null,
        bool $isNullable = true,
        bool $isCollection = false
    ) {
        $this->shortName = $shortName === 'mixed' ? null : $shortName;
        $this->namespace = $namespace;
        $this->isNullable = $isNullable && $shortName !== self::VOID;
        $this->isCollection = $isCollection && $shortName !== self::VOID;
        $this->isScalar = in_array($shortName, [self::BOOL, self::FLOAT, self::INT, self::STRING]);
    }

    public static function namespace(string $className): string
    {
        return implode('\\', array_slice(explode('\\', '\\' . trim($className, '\\')), 0, -1));
    }

    public static function short(string $className): string
    {
        return strval(array_reverse(explode('\\', $className))[0]);
    }

    public function getFullName()
    {
        return $this->namespace === null ? null : ltrim($this->namespace . '\\', '\\') . $this->shortName;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return bool
     */
    public function isScalar(): bool
    {
        return $this->isScalar;
    }

    public function toConversion(string $valueExpression, string $classConversionSprintfTemplate = 'new %s'): string
    {
        return sprintf(
            $this->shortName && !$this->isCollection ? '%s%s(%s)' : '%s%3$s',
            $this->isNullable ? sprintf('is_null(%s) ? null : ', $valueExpression) : '',
            $this->isScalar
                ? str_replace('string', 'str', $this->shortName) . 'val'
                : sprintf($classConversionSprintfTemplate, $this->shortName),
            $valueExpression
        );
    }

    public function toDocParam()
    {
        return ($this->shortName ?: 'mixed')
            . ($this->isCollection ? '[]|iterable' : '')
            . ($this->isNullable ? '|null' : '');
    }

    public function toDocReturn()
    {
        return ($this->shortName ?: 'mixed')
            . ($this->isCollection ? '[]|iterable' : '')
            . ($this->isNullable ? '|null' : '');
    }

    public function toParam()
    {
        return $this->shortName || $this->isCollection
            ? sprintf(
                '%s%s ',
                $this->isNullable ? '?' : '',
                $this->isCollection ? 'iterable' : ($this->shortName === 'static' ? 'self' : $this->shortName)
            )
            : '';
    }

    public function toReturn()
    {
        return $this->shortName || $this->isCollection
            ? sprintf(
                ': %s%s',
                $this->isNullable ? '?' : '',
                $this->isCollection ? 'iterable' : ($this->shortName === 'static' ? 'self' : $this->shortName)
            )
            : '';
    }
}
