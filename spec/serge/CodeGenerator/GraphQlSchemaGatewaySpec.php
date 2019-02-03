<?php declare(strict_types=1);
namespace spec\serge\Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use Kepawni\Serge\CodeGenerator\Type;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use UnexpectedValueException;

class GraphQlSchemaGatewaySpec extends ObjectBehavior
{
    function it_can_convert_a_GraphQL_ID_type(IDType $graphQlType)
    {
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Uuid');
        $type->getNamespace()->shouldBe('\\Ramsey\\Uuid');
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_bool_type(BooleanType $graphQlType)
    {
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('bool');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_float_type(FloatType $graphQlType)
    {
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('float');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_input_object_type(InputObjectType $graphQlType)
    {
        $graphQlType->beConstructedWith([['name' => 'Money']]);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Money');
        $type->getNamespace()->shouldBe('\\My\\Project\\');
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_int_type(IntType $graphQlType)
    {
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('int');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_ID_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        IDType $wrappedType
    ) {
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Uuid');
        $type->getNamespace()->shouldBe('\\Ramsey\\Uuid');
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_non_null_ID_type(NonNull $graphQlType, IDType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Uuid');
        $type->getNamespace()->shouldBe('\\Ramsey\\Uuid');
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_non_null_bool_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        BooleanType $wrappedType
    ) {
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('bool');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_bool_type(NonNull $graphQlType, BooleanType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('bool');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_float_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        FloatType $wrappedType
    ) {
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('float');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_float_type(NonNull $graphQlType, FloatType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('float');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_input_object_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        InputObjectType $wrappedType
    ) {
        $wrappedType->beConstructedWith([['name' => 'Money']]);
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Money');
        $type->getNamespace()->shouldBe('\\My\\Project\\');
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_non_null_input_object_type(
        NonNull $graphQlType,
        InputObjectType $wrappedType
    ) {
        $wrappedType->beConstructedWith([['name' => 'Money']]);
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('Money');
        $type->getNamespace()->shouldBe('\\My\\Project\\');
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(false);
    }

    function it_can_convert_a_GraphQL_non_null_int_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        IntType $wrappedType
    ) {
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('int');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_int_type(NonNull $graphQlType, IntType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('int');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_string_list_type(
        NonNull $graphQlType,
        ListOfType $listType,
        NonNull $nonNullType,
        StringType $wrappedType
    ) {
        $graphQlType->getWrappedType()->willReturn($listType);
        $listType->getWrappedType()->willReturn($nonNullType);
        $nonNullType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('string');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(true);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_non_null_string_type(NonNull $graphQlType, StringType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('string');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(false);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_can_convert_a_GraphQL_string_type(StringType $graphQlType)
    {
        $type = $this->convertType($graphQlType, Uuid::class, '\\My\\Project\\');
        $type->shouldBeAnInstanceOf(Type::class);
        $type->getShortName()->shouldBe('string');
        $type->getNamespace()->shouldBe(null);
        $type->isNullable()->shouldBe(true);
        $type->isCollection()->shouldBe(false);
        $type->isScalar()->shouldBe(true);
    }

    function it_does_not_convert_a_GraphQL_interface_type(InterfaceType $graphQlType)
    {
        $this->shouldThrow(UnexpectedValueException::class)->duringConvertType(
            $graphQlType,
            Uuid::class,
            '\\My\\Project\\'
        )
        ;
    }

    function it_does_not_convert_a_GraphQL_wrapped_interface_type(ListOfType $graphQlType, InterfaceType $wrappedType)
    {
        $graphQlType->getWrappedType()->willReturn($wrappedType);
        $this->shouldThrow(UnexpectedValueException::class)->duringConvertType(
            $graphQlType,
            Uuid::class,
            '\\My\\Project\\'
        )
        ;
    }
}
