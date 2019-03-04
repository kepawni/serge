<?php declare(strict_types=1);
namespace spec\serge\Kepawni\Serge\CodeGenerator;

use Kepawni\Serge\CodeGenerator\CodeBlock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CodeBlockSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(CodeBlock::class);
    }

    function it_serializes_with_empty_contents()
    {
        $this->__toString()->shouldBe('()');
    }

    function it_supports_method_chaining(CodeBlock $content)
    {
        $this->addContentString('int $foo')->shouldBe($this);
        $this->addContentBlock($content)->shouldBe($this);
    }

    function it_accepts_string_content_chunks()
    {
        $this
            ->addContentString('int $foo')
            ->addContentString('?bool $bar')
            ->__toString()
            ->shouldBe('(int $foo, ?bool $bar)');
    }

    function it_supports_recursive_block_construction(CodeBlock $content)
    {
        $content->beConstructedWith(['[', ']', ', ']);
        $content->addContentString('1')->willReturn($content);
        $content->addContentString('2')->willReturn($content);
        $content->__toString()->willReturn('[1, 2]');
        $this
            ->addContentBlock($content)
            ->addContentBlock($content)
            ->__toString()
            ->shouldBe('([1, 2], [1, 2])');
    }

    function let()
    {
        $this->beConstructedWith('(', ')', ', ');
    }
}
