<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type as GraphQlType;
use Kepawni\Twilted\Basic\ImmutableValue;
use UnexpectedValueException;

/**
 * @property-read string|null $shortName
 * @property-read string|null $namespace
 * @property-read bool|null $isNullable
 * @property-read bool|null $isCollection
 * @property-read bool|null $isScalar
 * @method self withShortName(?string $v)
 * @method self withNamespace(?string $v)
 * @method self withIsNullable(?bool $v)
 * @method self withIsCollection(?bool $v)
 * @method self withIsScalar(?bool $v)
 */
class Type extends ImmutableValue
{
    public const BOOL = 'bool';
    public const FLOAT = 'float';
    public const INT = 'int';
    public const MIXED = null;
    public const SELF = 'self';
    public const STATIC = 'static';
    public const STRING = 'string';
    public const VOID = 'void';

    public function __construct(
        ?string $shortName = null,
        ?string $namespace = null,
        bool $isNullable = true,
        bool $isCollection = false
    ) {
        $this->init('shortName', $shortName === 'mixed' ? null : $shortName);
        $this->init('namespace', $namespace);
        $this->init('isNullable', $isNullable && $shortName !== self::VOID);
        $this->init('isCollection', $isCollection && $shortName !== self::VOID);
        $this->init('isScalar', in_array($shortName, [self::BOOL, self::FLOAT, self::INT, self::STRING]));
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
            return $result->withIsNullable(false);
        } elseif ($type instanceof ListOfType) {
            return $result->withIsCollection(true);
        } elseif ($type instanceof ScalarType) {
            switch ($type->name) {
                case GraphQlType::BOOLEAN:
                    return $result->withIsScalar(true)->withShortName(self::BOOL)->withNamespace(null);
                case GraphQlType::FLOAT:
                    return $result->withIsScalar(true)->withShortName(self::FLOAT)->withNamespace(null);
                case GraphQlType::ID:
                    return $result;
                case GraphQlType::INT:
                    return $result->withIsScalar(true)->withShortName(self::INT)->withNamespace(null);
                case GraphQlType::STRING:
                    return $result->withIsScalar(true)->withShortName(self::STRING)->withNamespace(null);
            }
        } elseif ($type instanceof InputObjectType) {
            return $result->withShortName($type->name)->withNamespace($defaultNamespace);
        }
        throw new UnexpectedValueException(
            sprintf('Unsupported GraphQL type “%s”', $type->name ?: get_class($type))
        );
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
            ? ($this->isNullable ? '?' : '') . ($this->isCollection ? 'iterable' : $this->shortName) . ' '
            : '';
    }

    public function toReturn()
    {
        return $this->shortName || $this->isCollection
            ? (': '
                . ($this->isNullable ? '?' : '')
                . ($this->isCollection ? 'iterable' : ($this->shortName === 'static' ? 'self' : $this->shortName))
            )
            : '';
    }
}
