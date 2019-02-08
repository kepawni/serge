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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Utils\BuildSchema;
use Kepawni\Serge\CodeGenerator\Type;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;
use UnexpectedValueException;

class GraphQlSchemaGatewaySpec extends ObjectBehavior
{
    private $schema;

    function __construct()
    {
        $this->schema = BuildSchema::build(
            <<<'EOD'
schema {
    query: CqrsQuery
    mutation: CqrsAggregateMutators
}
type CqrsQuery {
    status: Boolean!
}
type CqrsAggregateMutators {
    Customer(id: ID!): Customer!
    Invoice(id: ID!): Invoice!
}
type Customer {
    engageInBusiness(name: String! billingAddress: Address!): Customer!
    relocate(billingAddress: Address!): Boolean!
    rename(name: String!): Boolean!
    fleeFromPrison(name: String!): Boolean!
    cryForHelp(name: String!): Boolean!
    stirPorridge(name: String!): Boolean!
    labelBottles(name: String!): Boolean!
    kneelBeforeTheQueen(name: String!): Boolean!
    haulFurniture(name: String!): Boolean!
    belittleKnownProblems(name: String!): Boolean!
}
type Invoice {
    chargeCustomer(customerId: ID!, invoiceNumber: String, invoiceDate: Date): Invoice!
    appendLineItem(item: LineItem!): Boolean!
    correctMistypedInvoiceDate(invoiceDate: Date!): Boolean!
    overrideDueDate(dueDate: Date!): Boolean!
    removeLineItemByPosition(position: Int!): Boolean!
    requestPaymentReference(paymentReference: String!): Boolean!
}
input Address {
    city: String!
    postalCode: String!
    countryCode: String!
    addressLine1: String
    addressLine2: String
    addressLine3: String
}
input Date {
    iso8601value: String!
}
input LineItem {
    title: String!
    quantity: Float!
    price: Money!
}
input Money {
    amount: Float!
    currency: String!
}
interface CustomerEvents {
    InBusinessWasEngaged(name: String!, billingAddress: Address!): Boolean!
    CustomerWasRelocated(billingAddress: Address!): Boolean!
    CustomerWasRenamed(name: String!): Boolean!
    FromPrisonWasFled(name: String!): Boolean!
    ForHelpWasCried(name: String!): Boolean!
    PorridgeWasStirred(name: String!): Boolean!
    BottlesWereLabelled(name: String!): Boolean!
    BeforeTheQueenWasKneeled(name: String!): Boolean!
    FurnitureWasHauled(name: String!): Boolean!
    KnownProblemsWereBelittled(name: String!): Boolean!
}
interface InvoiceEvents {
    CustomerWasCharged(customerId: ID!, invoiceNumber: String, invoiceDate: Date): Boolean!
}

EOD
        );
    }

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

    function it_can_iterate_aggregate_event_types_encoded_as_schema_interfaces()
    {
        $descriptors = $this->iterateAggregateEventDescriptors($this->schema);
        $descriptors->shouldIterateAs(
            [
                'Customer' => $this->schema->getType('CustomerEvents'),
                'Invoice' => $this->schema->getType('InvoiceEvents'),
            ]
        );
        foreach ($descriptors as $descriptor) {
            $descriptor->shouldBeAnInstanceOf(InterfaceType::class);
        }
    }

    function it_can_iterate_aggregates_encoded_as_return_types_of_methods_in_the_top_level_mutator()
    {
        $aggregates = $this->iterateAggregates($this->schema);
        $aggregates->shouldIterateAs(
            [
                $this->schema->getType('Customer'),
                $this->schema->getType('Invoice'),
            ]
        );
        foreach ($aggregates as $aggregate) {
            $aggregate->shouldBeAnInstanceOf(ObjectType::class);
        }
    }

    function it_can_iterate_value_objects_encoded_as_input_types()
    {
        $valueObjects = $this->iterateValueObjects($this->schema);
        $valueObjects->shouldIterateAs(
            [
                $this->schema->getType('Address'),
                $this->schema->getType('Date'),
                $this->schema->getType('LineItem'),
                $this->schema->getType('Money'),
            ]
        );
        foreach ($valueObjects as $valueObject) {
            $valueObject->shouldBeAnInstanceOf(InputObjectType::class);
        }
    }

    function it_can_iterate_methods_of_an_aggregate()
    {
        /** @var ObjectType $aggregate */
        $aggregate = $this->schema->getType('Customer');
        $methods = $this->iterateAggregateMethods($aggregate);
        $methods->shouldIterateAs(
            [
                $aggregate->getField('engageInBusiness'),
                $aggregate->getField('relocate'),
                $aggregate->getField('rename'),
                $aggregate->getField('fleeFromPrison'),
                $aggregate->getField('cryForHelp'),
                $aggregate->getField('stirPorridge'),
                $aggregate->getField('labelBottles'),
                $aggregate->getField('kneelBeforeTheQueen'),
                $aggregate->getField('haulFurniture'),
                $aggregate->getField('belittleKnownProblems'),
            ]
        );
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
