<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use Kepawni\Twilted\Basic\SimpleAggregateRoot;
use Kepawni\Twilted\EntityIdentifier;

class GraphQlAggregateGenerator
{
    /** @var string */
    private $aggregateNamespace;
    /** @var string */
    private $eventPayloadNamespace;
    /** @var string[] */
    private $predicates = [
        'bool' => 'MatchesCurrentState',
        'float' => 'IsWithinAllowedPrecision',
        'int' => 'IsWithinAllowedRange',
        'string' => 'IsUtf8Encoded',
        'EntityIdentifier' => 'IsVersion4Uuid',
    ];
    /** @var GraphQlSchemaGateway */
    private $schemaGateway;
    /** @var string */
    private $valueObjectNamespace;

    public function __construct(
        GraphQlSchemaGateway $schemaGateway,
        string $aggregateNamespace,
        string $eventPayloadNamespace,
        string $valueObjectNamespace
    ) {
        $this->schemaGateway = $schemaGateway;
        $this->aggregateNamespace = $aggregateNamespace;
        $this->eventPayloadNamespace = $eventPayloadNamespace;
        $this->valueObjectNamespace = $valueObjectNamespace;
    }

    public function process(ObjectType $aggregate): Classifier
    {
        $classifier = new Classifier($aggregate->name, $this->aggregateNamespace);
        /** @var FieldDefinition $action */
        foreach ($this->schemaGateway->iterateAggregateMethods($aggregate) as $action) {
            $classifier->addMethod($this->actionToMethod($aggregate, $action));
        }
        foreach ($this->schemaGateway->iterateAggregateEvents($aggregate) as $event) {
            $classifier->addMethod($this->eventToMethod($event, $aggregate->name));
        }
        return $classifier->extend(
            new Classifier(Type::short(SimpleAggregateRoot::class), Type::namespace(SimpleAggregateRoot::class))
        );
    }

    private function actionToMethod(ObjectType $aggregate, FieldDefinition $action): Method
    {
        $method = (new Method($action->name))
            ->makeStatic($this->schemaGateway->unwrapGraphQlType($action->getType()) === $aggregate);
        $eventInvocation = new CodeBlock(
            sprintf('// new %s(', $this->eventNameForAggregateMethod($aggregate->name, $action->name)),
            ')',
            ', '
        );
        if ($method->isStatic()) {
            $method
                ->appendParameter(
                    new Parameter(
                        sprintf('%sId', lcfirst($aggregate->name)),
                        new Type(Type::short(EntityIdentifier::class), Type::namespace(EntityIdentifier::class), false)
                    )
                )
                ->addContentString(
                    sprintf(
                        '// $this->guard%sIdIsVersion4Uuid($%sId);',
                        $aggregate->name,
                        lcfirst($aggregate->name)
                    )
                )
            ;
            $this->processArguments($method, $action, $eventInvocation);
            $method
                ->addContentString(
                    sprintf('$new%s = new static($%sId);', $aggregate->name, lcfirst($aggregate->name))
                )
                ->addContentBlock(
                    (new IndentedMultilineBlock(sprintf('$new%s->recordThat(', $aggregate->name), ');'))
                        ->addContentBlock($eventInvocation)
                )
                ->addContentString(sprintf('return $new%s;', $aggregate->name))
            ;
        } else {
            $this->processArguments($method, $action, $eventInvocation);
            $method->addContentBlock(
                (new IndentedMultilineBlock('$this->recordThat(', ');'))->addContentBlock($eventInvocation)
            );
        }
        return $method->makeReturn(new Type($method->isStatic() ? 'self' : 'void', null, false));
    }

    private function appendExampleGuard(Method $method, FieldArgument $argument): void
    {
        $type =
            $this->schemaGateway->convertType(
                $argument->getType(),
                EntityIdentifier::class,
                $this->valueObjectNamespace
            );
        $method
            ->appendParameter(new Parameter($argument->name, $type))
            ->addContentString(
                sprintf(
                    '// $this->guard%s%s($%s);',
                    ucfirst($argument->name),
                    $this->predicates[$type->getShortName()] ?? 'IsSupported',
                    $argument->name
                )
            )
        ;
    }

    private function eventNameForAggregateMethod(string $aggregateName, string $methodName): string
    {
        $words = preg_split('<(?=[A-Z])>', $methodName);
        return (implode('', array_slice($words, 1)) ?: $aggregateName)
            . (preg_match('<[^s]s$>', $methodName) ? 'Were' : 'Was')
            . ucfirst(
                preg_replace(['<[^aeiou][aeiou]([bcdfgklmnprtz])$>', '<y$>', '<e+$>'], ['\\0\\1', 'i', ''], $words[0])
            )
            . 'ed';
    }

    private function eventToMethod(FieldDefinition $event, string $aggregateName): Method
    {
        return (new Method('when' . $event->name))
            ->makeProtected()
            ->makeReturn(new Type('void'))
            ->appendParameter(
                new Parameter(
                    'event',
                    new Type($event->name, $this->eventPayloadNamespace . '\\' . $aggregateName, false)
                )
            )
            ;
    }

    private function processArguments(Method $method, FieldDefinition $action, CodeBlock $eventInvocation): void
    {
        /** @var FieldArgument $argument */
        foreach ($action->args as $argument) {
            $this->appendExampleGuard($method, $argument);
            $eventInvocation->addContentString(sprintf('$%s', $argument->name));
        }
    }
}
