<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use Kepawni\Twilted\Basic\SimpleCommandHandler;

class CqrsCommandBus extends TypeResolver
{
    public function __construct(string $mutationType, $base = null)
    {
        parent::__construct($base);
        $this->addResolverForType(
            $mutationType,
            function ($_, array $args) {
                return $args['id'];
            }
        );
    }

    public function append(SimpleCommandHandler $handler)
    {
        foreach (get_class_methods($handler) as $method) {
            $handlerClassShort = strval(array_reverse(explode('\\', get_class($handler)))[0]);
            $this->addResolverForField(
                $handlerClassShort,
                $method,
                function ($aggregateId, array $methodArgs, $context, ResolveInfo $info) use ($handler, $method) {
                    $handler->$method($aggregateId, $methodArgs, $context, $info);
                    return true;
                }
            );
        }
    }
}
