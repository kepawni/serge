<?php declare(strict_types=1);
namespace spec\serge\Kepawni\Serge\CodeGenerator;

use Kepawni\Serge\CodeGenerator\CodeBlock;
use Kepawni\Serge\CodeGenerator\IndentedMultilineBlock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IndentedMultilineBlockSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(IndentedMultilineBlock::class);
    }
    function it_is_a_special_CodeBlock()
    {
        $this->shouldHaveType(CodeBlock::class);
    }

    function it_serializes_with_empty_contents()
    {
        $this->__toString()->shouldBe(<<<'EOD'
{
    
}
EOD
);
    }

    function it_accepts_string_lines()
    {
        $this
            ->addContentString('$foo = 1;')
            ->addContentString('$bar = 2;')
            ->__toString()
            ->shouldBe(<<<'EOD'
{
    $foo = 1;
    $bar = 2;
}
EOD
);
    }

    function it_supports_recursive_block_construction(CodeBlock $content)
    {
        $this->beConstructedWith('[', ']', ',');
        $content->beConstructedWith(['[', ']', ', ']);
        $content->addContentString('1')->willReturn($content);
        $content->addContentString('2')->willReturn($content);
        $content->__toString()->willReturn('[1, 2]');
        $this
            ->addContentBlock($content)
            ->addContentBlock($content)
            ->__toString()
            ->shouldBe(<<<'EOD'
[
    [1, 2],
    [1, 2]
]
EOD
);
    }
}
