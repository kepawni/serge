<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Kepawni\Twilted\Basic\AggregateUuid;
use Kepawni\Twilted\Basic\SimpleCommandHandler;

class GraphQlCommandHandlerGenerator
{
    /** @var string */
    private $aggregateNamespace;
    /** @var string */
    private $aggregatePrefix;
    /** @var string */
    private $aggregateSuffix;
    /** @var string */
    private $eventPayloadNamespace;
    /** @var string */
    private $handlerNamespace;
    /** @var string */
    private $handlerPrefix;
    /** @var string */
    private $handlerSuffix;
    /** @var GraphQlSchemaGateway */
    private $schemaGateway;
    /** @var string */
    private $valueObjectNamespace;
    /** @var string */
    private $valueObjectPrefix;
    /** @var string */
    private $valueObjectSuffix;

    public function __construct(
        GraphQlSchemaGateway $schemaGateway,
        string $handlerNamespace,
        string $handlerPrefix,
        string $handlerSuffix,
        string $aggregateNamespace,
        string $aggregatePrefix,
        string $aggregateSuffix,
        string $eventPayloadNamespace,
        string $valueObjectNamespace,
        string $valueObjectPrefix,
        string $valueObjectSuffix
    ) {
        $this->schemaGateway = $schemaGateway;
        $this->aggregateNamespace = $aggregateNamespace;
        $this->aggregatePrefix = $aggregatePrefix;
        $this->aggregateSuffix = $aggregateSuffix;
        $this->eventPayloadNamespace = $eventPayloadNamespace;
        $this->handlerPrefix = $handlerPrefix;
        $this->handlerSuffix = $handlerSuffix;
        $this->handlerNamespace = $handlerNamespace;
        $this->valueObjectNamespace = $valueObjectNamespace;
        $this->valueObjectPrefix = $valueObjectPrefix;
        $this->valueObjectSuffix = $valueObjectSuffix;
    }

    public function process(ObjectType $aggregate): Classifier
    {
        $classifier = new Classifier(
            $this->handlerPrefix . $aggregate->name . $this->handlerSuffix,
            $this->handlerNamespace
        );
        /** @var FieldDefinition $action */
        foreach ($this->schemaGateway->iterateAggregateMethods($aggregate) as $action) {
            $classifier->addMethod($this->actionToMethod($aggregate, $action));
        }
        return $classifier->extend(
            new Classifier(Type::short(SimpleCommandHandler::class), Type::namespace(SimpleCommandHandler::class))
        );
    }

    private function actionToMethod(ObjectType $aggregate, FieldDefinition $action): Method
    {
        $method = (new Method($action->name))
            ->appendParameter(new Parameter('aggregateId', new Type('string', null, false)))
            ->appendParameter(new Parameter('methodArgs', new Type('array', null, false)))
            ->appendParameter(new Parameter('context', new Type(null)))
            ->appendParameter(
                new Parameter(
                    'info',
                    new Type(Type::short(ResolveInfo::class), Type::namespace(ResolveInfo::class), false)
                )
            )
            ->makeReturn(new Type('void'))
            ->useClass(
                $this->aggregateNamespace . '\\' . $this->aggregatePrefix . $aggregate->name . $this->aggregateSuffix
            )
        ;
        $invocation = null;
        if ($this->schemaGateway->unwrapGraphQlType($action->getType())->name === IDType::ID) {
            $invocation = new IndentedMultilineBlock(
                sprintf(
                    '$new%s = %s::%s(',
                    $aggregate->name,
                    $this->aggregatePrefix . $aggregate->name . $this->aggregateSuffix,
                    $action->name
                ),
                ');',
                ','
            );
            $method
                ->addContentString(
                    sprintf(
                        '/** @var %s $new%s */',
                        $this->aggregatePrefix . $aggregate->name . $this->aggregateSuffix,
                        $aggregate->name
                    )
                )
                ->addContentBlock($invocation)
                ->addContentString(sprintf('$this->saveToRepository($new%s);', $aggregate->name))
            ;
            $invocation->addContentString('AggregateUuid::unfold($aggregateId)');
        } else {
            $invocation = new IndentedMultilineBlock(
                sprintf('$the%s->%s(', $aggregate->name, $action->name), ');', ','
            );
            $method
                ->addContentString(
                    sprintf(
                        '/** @var %s $the%s */',
                        $this->aggregatePrefix . $aggregate->name . $this->aggregateSuffix,
                        $aggregate->name
                    )
                )
                ->addContentString(
                    sprintf(
                        '$the%s = $this->loadFromRepository(AggregateUuid::unfold($aggregateId));',
                        $aggregate->name
                    )
                )
                ->addContentBlock($invocation)
                ->addContentString(sprintf('$this->saveToRepository($the%s);', $aggregate->name))
            ;
        }
        /** @var FieldArgument $argument */
        foreach ($action->args as $argument) {
            $type = $this->schemaGateway->convertType(
                $argument->getType(),
                AggregateUuid::class,
                $this->valueObjectNamespace
            )
                ->withNameSurroundedWhenInNamespace(
                    $this->valueObjectPrefix,
                    $this->valueObjectSuffix,
                    $this->valueObjectNamespace
                )
            ;
            $invocation->addContentString(
                $type->toConversion(
                    sprintf('$methodArgs[\'%s\']', $argument->name),
                    $type->getFullName() === AggregateUuid::class
                        ? '%s::unfold'
                        : '%s::fromHashMap'
                )
            );
            if (!$type->isScalar()) {
                $method->useClass($type->getFullName());
            }
        }
        return $method->useClass(AggregateUuid::class);
    }
}
