<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\FieldDefinition;
use Kepawni\Serge\Infrastructure\AbstractEventPayloadBase;
use Kepawni\Twilted\Basic\AggregateUuid;
use Kepawni\Twilted\Windable;

class GraphQlEventPayloadGenerator
{
    /** @var string */
    private $eventPayloadNamespace;
    /** @var GraphQlSchemaGateway */
    private $schemaGateway;
    /** @var string */
    private $valueObjectNamespace;

    public function __construct(
        GraphQlSchemaGateway $schemaGateway,
        string $eventPayloadNamespace,
        string $valueObjectNamespace
    ) {
        $this->schemaGateway = $schemaGateway;
        $this->eventPayloadNamespace = $eventPayloadNamespace;
        $this->valueObjectNamespace = $valueObjectNamespace;
    }

    public function process(FieldDefinition $eventPayload, string $aggregateName)
    {
        $classifier = new Classifier($eventPayload->name, $this->eventPayloadNamespace . '\\' . $aggregateName);
        $params = [];
        $constructor = new Method('__construct');
        $unwindParams = new IndentedMultilineBlock('return new self(', ');', ',');
        $windUpParams = new IndentedMultilineBlock('return [', '];', ',');
        foreach (array_values($eventPayload->args) as $i => $property) {
            $type = $this->schemaGateway->convertType(
                $property->getType(),
                AggregateUuid::class,
                $this->valueObjectNamespace
            );
            if (!$type->isScalar()) {
                $classifier->useClass($type->getFullName());
            }
            $params[] = sprintf('@property-read %s $%s', $type->toDocParam(), $property->name);
            $parameter = new Parameter($property->name, $type);
            $constructor
                ->appendParameter($type->isNullable() ? $parameter->specifyDefaultValue(null) : $parameter)
                ->addContentString(sprintf('$this->init(\'%s\', $%1$s);', $property->name))
            ;
            $unwindParams->addContentString($type->toConversion(sprintf('$spool[%d]', $i), '%s::unfold'));
            $windUpParams->addContentString(
                sprintf(
                    '%s$this->%s%s',
                    $type->isNullable() && !$type->isScalar() ? sprintf('is_null($this->%s) ? null : ', $property->name)
                        : '',
                    $property->name,
                    $type->isScalar() ? '' : '->fold()'
                )
            );
        }
        array_walk($params, [$classifier, 'addDocCommentLine']);
        return $classifier
            ->addMethod($constructor)
            ->addMethod(
                (new Method('unwind'))
                    ->addDocCommentLine('@param array $spool')
                    ->addDocCommentLine('@return static')
                    ->makeStatic()
                    ->appendParameter(new Parameter('spool', new Type('array', null, false)))
                    ->makeReturn(new Type(Type::short(Windable::class), Type::namespace(Windable::class), false))
                    ->addContentBlock($unwindParams)
            )
            ->addMethod(
                (new Method('windUp'))
                    ->makeReturn(new Type('array', null, false))
                    ->addContentBlock($windUpParams)
            )
            ->extend(
                new Classifier(
                    Type::short(AbstractEventPayloadBase::class),
                    Type::namespace(AbstractEventPayloadBase::class)
                )
            )
            ;
    }
}