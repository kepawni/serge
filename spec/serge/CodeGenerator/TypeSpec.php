<?php declare(strict_types=1);
namespace spec\serge\Kepawni\Serge\CodeGenerator;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use Kepawni\Serge\CodeGenerator\Type;
use PhpSpec\ObjectBehavior;
use Ramsey\Uuid\Uuid;

class TypeSpec extends ObjectBehavior
{
    function it_can_be_converted_from_a_GraphQL_ID_type(IDType $graphQlType)
    {
        $this->beConstructedFromGraphQlType($graphQlType, Uuid::class, '\\My\\Project\\');
        $this->shortName->shouldBe('Uuid');
        $this->namespace->shouldBe('\\Ramsey\\Uuid');
        $this->isNullable->shouldBe(true);
        $this->isCollection->shouldBe(false);
        $this->isScalar->shouldBe(false);
    }

    function it_can_be_converted_from_a_GraphQL_input_object_type(InputObjectType $graphQlType)
    {
        $graphQlType->beConstructedWith([['name' => 'Money']]);
        $this->beConstructedFromGraphQlType($graphQlType, Uuid::class, '\\My\\Project\\');
        $this->shortName->shouldBe('Money');
        $this->namespace->shouldBe('\\My\\Project\\');
        $this->isNullable->shouldBe(true);
        $this->isCollection->shouldBe(false);
        $this->isScalar->shouldBe(false);
    }

    function it_can_be_converted_from_a_GraphQL_scalar_type(StringType $graphQlType)
    {
        $this->beConstructedFromGraphQlType($graphQlType, Uuid::class, '\\My\\Project\\');
        $this->shortName->shouldBe('string');
        $this->namespace->shouldBe(null);
        $this->isNullable->shouldBe(true);
        $this->isCollection->shouldBe(false);
        $this->isScalar->shouldBe(true);
    }

    function it_exposes_the_namespace()
    {
        $this->beConstructedWith('ShortName', 'namespace');
        $this->namespace->shouldBe('namespace');
    }

    function it_exposes_the_short_name()
    {
        $this->beConstructedWith('ShortName');
        $this->shortName->shouldBe('ShortName');
    }

    function it_exposes_whether_it_is_a_collection()
    {
        $this->isCollection->shouldBeBool();
    }

    function it_exposes_whether_it_is_nullable()
    {
        $this->isNullable->shouldBeBool();
    }

    function it_exposes_whether_it_is_scalar()
    {
        $this->isScalar->shouldBeBool();
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Type::class);
    }

    function it_serializes_to_different_type_specifiers_when_mixed()
    {
        $this->beConstructedWith(Type::MIXED);
        $this->toDocParam()->shouldBe('mixed|null');
        $this->toDocReturn()->shouldBe('mixed|null');
        $this->toParam()->shouldBe('');
        $this->toReturn()->shouldBe('');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : $value');
    }

    function it_serializes_to_different_type_specifiers_when_mixed_collection()
    {
        $this->beConstructedWith(Type::MIXED, null, true, true);
        $this->toDocParam()->shouldBe('mixed[]|iterable|null');
        $this->toDocReturn()->shouldBe('mixed[]|iterable|null');
        $this->toParam()->shouldBe('?iterable ');
        $this->toReturn()->shouldBe(': ?iterable');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : $value');
    }

    function it_serializes_to_different_type_specifiers_when_required_mixed()
    {
        $this->beConstructedWith(Type::MIXED, null, false);
        $this->toDocParam()->shouldBe('mixed');
        $this->toDocReturn()->shouldBe('mixed');
        $this->toParam()->shouldBe('');
        $this->toReturn()->shouldBe('');
        $this->toConversion('$value')->shouldBe('$value');
    }

    function it_serializes_to_different_type_specifiers_when_required_mixed_collection()
    {
        $this->beConstructedWith(Type::MIXED, null, false, true);
        $this->toDocParam()->shouldBe('mixed[]|iterable');
        $this->toDocReturn()->shouldBe('mixed[]|iterable');
        $this->toParam()->shouldBe('iterable ');
        $this->toReturn()->shouldBe(': iterable');
        $this->toConversion('$value')->shouldBe('$value');
    }

    function it_serializes_to_different_type_specifiers_when_static()
    {
        $this->beConstructedWith(Type::STATIC);
        $this->toDocParam()->shouldBe('static|null');
        $this->toDocReturn()->shouldBe('static|null');
        $this->toParam()->shouldBe('?static ');
        $this->toReturn()->shouldBe(': ?self');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : new static($value)');
    }

    function it_serializes_to_different_type_specifiers_when_static_collection()
    {
        $this->beConstructedWith(Type::STATIC, null, true, true);
        $this->toDocParam()->shouldBe('static[]|iterable|null');
        $this->toDocReturn()->shouldBe('static[]|iterable|null');
        $this->toParam()->shouldBe('?iterable ');
        $this->toReturn()->shouldBe(': ?iterable');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : $value');
    }

    function it_serializes_to_different_type_specifiers_when_required_static()
    {
        $this->beConstructedWith(Type::STATIC, null, false);
        $this->toDocParam()->shouldBe('static');
        $this->toDocReturn()->shouldBe('static');
        $this->toParam()->shouldBe('static ');
        $this->toReturn()->shouldBe(': self');
        $this->toConversion('$value')->shouldBe('new static($value)');
    }

    function it_serializes_to_different_type_specifiers_when_required_static_collection()
    {
        $this->beConstructedWith(Type::STATIC, null, false, true);
        $this->toDocParam()->shouldBe('static[]|iterable');
        $this->toDocReturn()->shouldBe('static[]|iterable');
        $this->toParam()->shouldBe('iterable ');
        $this->toReturn()->shouldBe(': iterable');
        $this->toConversion('$value')->shouldBe('$value');
    }

    function it_serializes_to_different_type_specifiers_when_scalar()
    {
        $this->beConstructedWith(Type::FLOAT);
        $this->toDocParam()->shouldBe('float|null');
        $this->toDocReturn()->shouldBe('float|null');
        $this->toParam()->shouldBe('?float ');
        $this->toReturn()->shouldBe(': ?float');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : floatval($value)');
    }

    function it_serializes_to_different_type_specifiers_when_scalar_collection()
    {
        $this->beConstructedWith(Type::FLOAT, null, true, true);
        $this->toDocParam()->shouldBe('float[]|iterable|null');
        $this->toDocReturn()->shouldBe('float[]|iterable|null');
        $this->toParam()->shouldBe('?iterable ');
        $this->toReturn()->shouldBe(': ?iterable');
        $this->toConversion('$value')->shouldBe('is_null($value) ? null : $value');
    }

    function it_serializes_to_different_type_specifiers_when_required_scalar()
    {
        $this->beConstructedWith(Type::FLOAT, null, false);
        $this->toDocParam()->shouldBe('float');
        $this->toDocReturn()->shouldBe('float');
        $this->toParam()->shouldBe('float ');
        $this->toReturn()->shouldBe(': float');
        $this->toConversion('$value')->shouldBe('floatval($value)');
    }

    function it_serializes_to_different_type_specifiers_when_required_scalar_collection()
    {
        $this->beConstructedWith(Type::FLOAT, null, false, true);
        $this->toDocParam()->shouldBe('float[]|iterable');
        $this->toDocReturn()->shouldBe('float[]|iterable');
        $this->toParam()->shouldBe('iterable ');
        $this->toReturn()->shouldBe(': iterable');
        $this->toConversion('$value')->shouldBe('$value');
    }

    function it_serializes_to_different_type_specifiers_when_object()
    {
        $this->beConstructedWith('Uuid', '\\Ramsey\\Uuid');
        $this->toDocParam()->shouldBe('Uuid|null');
        $this->toDocReturn()->shouldBe('Uuid|null');
        $this->toParam()->shouldBe('?Uuid ');
        $this->toReturn()->shouldBe(': ?Uuid');
        $this->toConversion('$value', '%s::fromString')->shouldBe('is_null($value) ? null : Uuid::fromString($value)');
    }

    function it_serializes_to_different_type_specifiers_when_object_collection()
    {
        $this->beConstructedWith('Uuid', '\\Ramsey\\Uuid', true, true);
        $this->toDocParam()->shouldBe('Uuid[]|iterable|null');
        $this->toDocReturn()->shouldBe('Uuid[]|iterable|null');
        $this->toParam()->shouldBe('?iterable ');
        $this->toReturn()->shouldBe(': ?iterable');
        $this->toConversion('$value', '%s::fromString')->shouldBe('is_null($value) ? null : $value');
    }

    function it_serializes_to_different_type_specifiers_when_required_object()
    {
        $this->beConstructedWith('Uuid', '\\Ramsey\\Uuid', false);
        $this->toDocParam()->shouldBe('Uuid');
        $this->toDocReturn()->shouldBe('Uuid');
        $this->toParam()->shouldBe('Uuid ');
        $this->toReturn()->shouldBe(': Uuid');
        $this->toConversion('$value', '%s::fromString')->shouldBe('Uuid::fromString($value)');
    }

    function it_serializes_to_different_type_specifiers_when_required_object_collection()
    {
        $this->beConstructedWith('Uuid', '\\Ramsey\\Uuid', false, true);
        $this->toDocParam()->shouldBe('Uuid[]|iterable');
        $this->toDocReturn()->shouldBe('Uuid[]|iterable');
        $this->toParam()->shouldBe('iterable ');
        $this->toReturn()->shouldBe(': iterable');
        $this->toConversion('$value', '%s::fromString')->shouldBe('$value');
    }

    function it_serializes_to_different_type_specifiers_when_void()
    {
        $this->beConstructedWith(Type::VOID);
        $this->toDocReturn()->shouldBe('void');
        $this->toReturn()->shouldBe(': void');
    }
}
