<?php declare(strict_types=1);
namespace spec\serge\Kepawni\Serge\CodeGenerator;

use Kepawni\Serge\CodeGenerator\Parameter;
use Kepawni\Serge\CodeGenerator\Type;
use PhpSpec\ObjectBehavior;

class ParameterSpec extends ObjectBehavior
{
    function it_converts_to_a_method_parameter_expression(Type $type)
    {
        $type->toParam()->willReturn('?SomeType ');
        $this->__toString()->shouldBe('?SomeType $name');
    }

    function it_exposes_the_name()
    {
        $this->getName()->shouldBe('name');
    }

    function it_exposes_the_type()
    {
        $this->getType()->shouldBeAnInstanceOf(Type::class);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Parameter::class);
    }

    function let(Type $type)
    {
        $this->beConstructedWith('name', $type);
    }
}
