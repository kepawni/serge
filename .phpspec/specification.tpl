<?php declare(strict_types=1);
namespace %namespace%;

use %subject%;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class %name% extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(%subject_class%::class);
    }

    function it_exposes_the_member()
    {
        $this->member->shouldBe('member');
    }

    function it_supports_the_method()
    {
        $this->method()->shouldBe('result');
    }

    function let()
    {
        $this->beConstructedWith(
            'member'
        );
    }
}
