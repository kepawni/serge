<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use Generator;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type as GraphQlType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use UnexpectedValueException;

class GraphQlSchemaGateway
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function convertType(GraphQlType $type, string $idClass, string $defaultNamespace): Type
    {
        return $this->traverseGraphQlType(
            $type,
            new Type(Type::short($idClass), Type::namespace($idClass)),
            $defaultNamespace
        );
    }

    /**
     * @return InterfaceType[]|Generator
     * @throws UnexpectedValueException
     */
    public function iterateAggregateEventDescriptors(): Generator
    {
        foreach ($this->schema->getTypeMap() as $type) {
            if ($type instanceof InterfaceType) {
                $this->guardEventDescriptorName($type->name);
                $aggregateName = substr($type->name, 0, -6);
                $this->guardEventDescriptorAggregate($aggregateName);
                yield $aggregateName => $type;
            }
        }
    }

    /**
     * @param ObjectType $aggregate
     *
     * @return FieldDefinition[]|Generator
     * @throws UnexpectedValueException
     */
    public function iterateAggregateEvents(ObjectType $aggregate): Generator
    {
        try {
            $type = $this->schema->getType($aggregate->name . 'Events');
            if ($type instanceof InterfaceType) {
                foreach ($type->getFields() as $fieldDefinition) {
                    $this->guardEventFieldType($fieldDefinition, $aggregate->name);
                    yield $fieldDefinition;
                }
            }
        } catch (Error $e) {
            // ignore errors
            // $this->schema->hasType() also throws this, so we had to try...catch anyway
        }
    }

    /**
     * @param ObjectType $aggregate
     *
     * @return FieldDefinition[]|Generator
     * @throws InvariantViolation
     * @throws UnexpectedValueException
     */
    public function iterateAggregateMethods(ObjectType $aggregate): Generator
    {
        foreach ($aggregate->getFields() as $fieldDefinition) {
            $this->guardMutationMethodFieldValid($fieldDefinition, $aggregate->name);
            yield $fieldDefinition;
        }
    }

    /**
     * @return ObjectType[]|Generator
     * @throws InvariantViolation
     * @throws UnexpectedValueException
     */
    public function iterateAggregates(): Generator
    {
        /** @var FieldDefinition $fieldDefinition */
        foreach ($this->schema->getMutationType()->getFields() as $fieldDefinition) {
            $this->guardAggregateMutatorType($fieldDefinition);
            $this->guardAggregateMutatorArgs($fieldDefinition, $fieldDefinition->name);
            $this->guardAggregateMutatorIdArgType($fieldDefinition, $fieldDefinition->name);
            yield $fieldDefinition->getType()->getWrappedType();
        }
    }

    /**
     * @return InputObjectType[]|Generator
     */
    public function iterateValueObjects(): Generator
    {
        foreach ($this->schema->getTypeMap() as $type) {
            if ($type instanceof InputObjectType) {
                yield $type;
            }
        }
    }

    public function unwrapGraphQlType(GraphQlType $type): GraphQlType
    {
        return $type instanceof WrappingType ? $this->unwrapGraphQlType($type->getWrappedType()) : $type;
    }

    private function guardAggregateMutatorArgs(FieldDefinition $fieldDefinition, string $aggregateName): void
    {
        if (count($fieldDefinition->args) !== 1 || $fieldDefinition->args[0]->name !== 'id') {
            throw new UnexpectedValueException(
                sprintf('The only allowed argument for the “%s” mutator should be named “id”', $aggregateName)
            );
        }
    }

    private function guardAggregateMutatorIdArgType(
        FieldDefinition $fieldDefinition,
        string $aggregateName
    ): void {
        if (!($fieldDefinition->args[0]->getType() instanceof NonNull)
            || !($fieldDefinition->args[0]->getType()->getWrappedType() instanceof IDType)
        ) {
            throw new UnexpectedValueException(
                sprintf('The type of the “id” argument of the “%s” mutator should be “ID!”', $aggregateName)
            );
        }
    }

    private function guardAggregateMutatorType(FieldDefinition $fieldDefinition): void
    {
        if (!($fieldDefinition->getType() instanceof NonNull)
            || !($fieldDefinition->getType()->getWrappedType() instanceof ObjectType)
            || $fieldDefinition->getType()->getWrappedType()->name !== $fieldDefinition->name
        ) {
            throw new UnexpectedValueException(
                sprintf('The return type of the “%s” mutator should be “%1$s!”', $fieldDefinition->name)
            );
        }
    }

    private function guardEventDescriptorAggregate(string $aggregateName): void
    {
        try {
            if ($this->schema->getType($aggregateName) instanceof ObjectType) {
                return;
            }
        } catch (Error $error) {
            // ignore errors
            // $this->schema->hasType() also throws this, so we had to try...catch anyway
        }
        throw new UnexpectedValueException(
            sprintf('Could not find an aggregate “%s” for the event descriptor “%1$sEvent”', $aggregateName)
        );
    }

    private function guardEventDescriptorName(string $name): void
    {
        if (substr($name, -6) !== 'Events') {
            throw new UnexpectedValueException(
                'Event descriptors should be named like an aggregate with the suffix “Events”'
            );
        }
    }

    private function guardEventFieldType(FieldDefinition $methodField, string $aggregateName): void
    {
        if (!($methodField->getType() instanceof NonNull)
            || !($methodField->getType()->getWrappedType() instanceof BooleanType)
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The return type of the %s\'s “%s” event should be “Boolean!”',
                    $aggregateName,
                    $methodField->name
                )
            );
        }
    }

    private function guardMutationMethodFieldValid(FieldDefinition $methodField, string $aggregateName): void
    {
        if (!($methodField->getType() instanceof NonNull)
            || !($methodField->getType()->getWrappedType() instanceof ScalarType)
            || !in_array($methodField->getType()->getWrappedType()->name, ['Boolean', 'ID'])
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The return type of the %s\'s “%s” method should be '
                    . '“Boolean!” for a mutation or “ID!” for a factory',
                    $aggregateName,
                    $methodField->name
                )
            );
        }
    }

    private function traverseGraphQlType(GraphQlType $type, Type $result, string $defaultNamespace): Type
    {
        if ($type instanceof NonNull) {
            return $this->traverseGraphQlType(
                $type->getWrappedType(),
                new Type($result->getShortName(), $result->getNamespace(), false, $result->isCollection()),
                $defaultNamespace
            );
        } elseif ($type instanceof ListOfType) {
            return $this->traverseGraphQlType(
                $type->getWrappedType(),
                new Type($result->getShortName(), $result->getNamespace(), $result->isNullable(), true),
                $defaultNamespace
            );
        } elseif ($type instanceof IDType) {
            return $result;
        } elseif ($type instanceof ScalarType) {
            switch ($type->name) {
                case GraphQlType::BOOLEAN:
                    return new Type(Type::BOOL, null, $result->isNullable(), $result->isCollection());
                case GraphQlType::FLOAT:
                    return new Type(Type::FLOAT, null, $result->isNullable(), $result->isCollection());
                case GraphQlType::INT:
                    return new Type(Type::INT, null, $result->isNullable(), $result->isCollection());
                case GraphQlType::STRING:
                    return new Type(Type::STRING, null, $result->isNullable(), $result->isCollection());
            }
        } elseif ($type instanceof InputObjectType) {
            return new Type($type->name, $defaultNamespace, $result->isNullable(), $result->isCollection());
        }
        throw new UnexpectedValueException(
            sprintf('Unsupported GraphQL type “%s”', $type->name ?: get_class($type))
        );
    }
}
