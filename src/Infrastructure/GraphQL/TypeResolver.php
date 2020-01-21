<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\GraphQL;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;

class TypeResolver
{
    /** @var TypeResolver */
    private $base;
    /** @var Closure[][] */
    private $resolverMap = [];

    /**
     * Constructs a new instance or decorates the given one.
     *
     * @param self|null $base
     */
    public function __construct(self $base = null)
    {
        $this->base = $base;
    }

    /**
     * Adds a resolver function for a specific field of an object type.
     *
     * @param string $objectType
     * @param string $fieldName
     * @param callable $resolver ($typeValue, array $args, $context, ResolveInfo $info)
     */
    public function addResolverForField(string $objectType, string $fieldName, callable $resolver): void
    {
        if ($this->base) {
            $this->base->addResolverForField($objectType, $fieldName, $resolver);
        } else {
            $this->resolverMap[$objectType][$fieldName] = $resolver;
        }
    }

    /**
     * Adds a resolver function to an object type that handles all fields without a specific resolver.
     *
     * @param string $objectType
     * @param callable $resolver ($typeValue, array $args, $context, ResolveInfo $info)
     */
    public function addResolverForType(string $objectType, callable $resolver): void
    {
        if ($this->base) {
            $this->base->addResolverForType($objectType, $resolver);
        } else {
            $this->resolverMap[$objectType][''] = $resolver;
        }
    }

    /**
     * Returns a type config decorator that can be used with {@link BuildSchema::build} to add field resolution to a
     * schema given in GraphQL Type Language.
     * @return Closure The type config decorator.
     */
    public function generateTypeConfigDecorator(): Closure
    {
        if ($this->base) {
            return $this->base->generateTypeConfigDecorator();
        } else {
            $map = $this->resolverMap;
            return function (array $origTypeConfig) use ($map) {
                $result = $origTypeConfig;
                if (isset($map[$origTypeConfig['name']])) {
                    $resolvers = $map[$origTypeConfig['name']];
                    $result['resolveField'] = function ($value, array $args, $context, ResolveInfo $info) use (
                        $resolvers
                    ) {
                        $resolver = $resolvers[$info->fieldName]
                            ?? $resolvers['']
                            ?? [Executor::class, 'defaultFieldResolver'];
                        return $resolver($value, $args, $context, $info);
                    };
                }
                return $result;
            };
        }
    }
}
