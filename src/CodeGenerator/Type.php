<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type as GraphQlType;
use UnexpectedValueException;

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

    public static function fromGraphQlType(GraphQlType $type, string $idClass, string $defaultNamespace)
    {
        return self::traverseGraphQlType(
            $type,
            new self(self::short($idClass), self::namespace($idClass)),
            $defaultNamespace
        );
    }

    public static function namespace(string $className): string
    {
        return implode('\\', array_slice(explode('\\', '\\' . trim($className, '\\')), 0, -1));
    }

    public static function short(string $className): string
    {
        return strval(array_reverse(explode('\\', $className))[0]);
    }

    private static function traverseGraphQlType(GraphQlType $type, self $result, string $defaultNamespace): self
    {
        if ($type instanceof NonNull) {
            $result->isNullable = false;
        } elseif ($type instanceof ListOfType) {
            $result->isCollection = true;
        } elseif ($type instanceof ScalarType) {
            if ($type->name !== GraphQlType::ID) {
                $result->isScalar = true;
                $result->namespace = null;
                switch ($type->name) {
                    case GraphQlType::BOOLEAN:
                        $result->shortName = self::BOOL;
                        break;
                    case GraphQlType::FLOAT:
                        $result->shortName = self::FLOAT;
                        break;
                    case GraphQlType::INT:
                        $result->shortName = self::INT;
                        break;
                    case GraphQlType::STRING:
                        $result->shortName = self::STRING;
                        break;
                }
            }
        } elseif ($type instanceof InputObjectType) {
            $result->shortName = $type->name;
            $result->namespace = $defaultNamespace;
        } else {
            throw new UnexpectedValueException(
                sprintf('Unsupported GraphQL type “%s”', $type->name ?: get_class($type))
            );
        }
        return $result;
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
