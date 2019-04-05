<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\InputObjectType;
use Kepawni\Serge\Infrastructure\AbstractValueObjectBase;
use Kepawni\Twilted\Basic\AggregateUuid;
use Kepawni\Twilted\Windable;

class GraphQlValueObjectGenerator
{
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
        string $valueObjectNamespace,
        string $valueObjectPrefix,
        string $valueObjectSuffix
    ) {
        $this->schemaGateway = $schemaGateway;
        $this->valueObjectNamespace = $valueObjectNamespace;
        $this->valueObjectPrefix = $valueObjectPrefix;
        $this->valueObjectSuffix = $valueObjectSuffix;
    }

    public function process(InputObjectType $valueObject)
    {
        $classifier = new Classifier(
            $this->valueObjectPrefix . $valueObject->name . $this->valueObjectSuffix,
            $this->valueObjectNamespace
        );
        $params = [];
        $methods = [];
        $constructor = new Method('__construct');
        $fromHashMapParams = new IndentedMultilineBlock('return new self(', ');', ',');
        $unwindParams = new IndentedMultilineBlock('return new self(', ');', ',');
        $windUpParams = new IndentedMultilineBlock('return [', '];', ',');
        foreach (array_values($valueObject->getFields()) as $i => $property) {
            $type = $this->schemaGateway->convertType(
                $property->getType(),
                AggregateUuid::class,
                $this->valueObjectNamespace
            )
                ->withNameSurroundedWhenInNamespace(
                    $this->valueObjectPrefix,
                    $this->valueObjectSuffix,
                    $this->valueObjectNamespace
                )
            ;
            if (!$type->isScalar()) {
                $classifier->useClass($type->getFullName());
            }
            $params[] = sprintf('@property-read %s $%s', $type->toDocParam(), $property->name);
            $methods[] = sprintf('@method self with%s(%s$v)', ucfirst($property->name), $type->toParam());
            $parameter = new Parameter($property->name, $type);
            $constructor
                ->appendParameter($type->isNullable() ? $parameter->specifyDefaultValue(null) : $parameter)
                ->addContentString(sprintf('$this->init(\'%s\', $%1$s);', $property->name))
            ;
            $fromHashMapParams->addContentString(
                $type->toConversion(
                    sprintf('$map[\'%s\']', $property->name),
                    $type->getFullName() === AggregateUuid::class
                        ? '%s::unfold'
                        : '%s::fromHashMap'
                )
            );
            $unwindParams->addContentString(
                $type->toConversion(
                    sprintf('$spool[%d]', $i),
                    $type->getFullName() === AggregateUuid::class
                        ? '%s::unfold'
                        : '%s::unwind'
                )
            );
            $windUpParams->addContentString(
                sprintf(
                    '%s$this->%s%s',
                    $type->isNullable() && !$type->isScalar()
                        ? sprintf('is_null($this->%s) ? null : ', $property->name)
                        : '',
                    $property->name,
                    $type->isScalar() ? '' : ($type->getFullName() === AggregateUuid::class ? '->fold()' : '->windUp()')
                )
            );
        }
        array_walk($params, [$classifier, 'addDocCommentLine']);
        array_walk($methods, [$classifier, 'addDocCommentLine']);
        return $classifier
            ->addMethod($constructor)
            ->addMethod(
                (new Method('fromHashMap'))
                    ->addDocCommentLine('@param array $map')
                    ->addDocCommentLine('@return static')
                    ->makeStatic()
                    ->appendParameter(new Parameter('map', new Type('array', null, false)))
                    ->makeReturn(
                        new Type(
                            Type::short(AbstractValueObjectBase::class),
                            Type::namespace(AbstractValueObjectBase::class),
                            false
                        )
                    )
                    ->addContentBlock($fromHashMapParams)
            )
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
                    Type::short(AbstractValueObjectBase::class),
                    Type::namespace(AbstractValueObjectBase::class)
                )
            )
            ;
    }
}
