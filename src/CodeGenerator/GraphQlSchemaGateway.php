<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type as GraphQlType;
use UnexpectedValueException;

class GraphQlSchemaGateway
{
    public function convertType(GraphQlType $type, string $idClass, string $defaultNamespace): Type
    {
        return $this->traverseGraphQlType(
            $type,
            new Type(Type::short($idClass), Type::namespace($idClass)),
            $defaultNamespace
        );
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
