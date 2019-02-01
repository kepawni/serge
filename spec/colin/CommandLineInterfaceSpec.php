<?php declare(strict_types=1);
namespace spec\colin\deswerve\colin;

use deswerve\colin\CommandLineInterface;
use PhpSpec\ObjectBehavior;
use RuntimeException;
use stdClass;

class CommandLineInterfaceSpec extends ObjectBehavior
{
    function it_can_handle_null_values_for_arrays_with_addOption()
    {
        $this->beConstructedWith(
            'my-application',
            ['[-v | --verbose]*']
        );
        $this->addOption('verbose', 'Increase verbosity', null, 'v', true, null);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CommandLineInterface::class);
    }

    function it_reports_invocations_with_unsupported_options()
    {
        $this->addOption('verbose', 'Increase verbosity');
        $this->addOption('level', '', ['INT'], 'l');
        $this->shouldThrow(RuntimeException::class)->duringProcessCommandLine(explode(' ', 'my-application -e'));
        $this->shouldThrow(RuntimeException::class)->duringProcessCommandLine(explode(' ', 'my-application --error'));
    }

    function it_prevents_duplicate_option_names()
    {
        $this->addOption('verbose', 'Increase verbosity');
        $this->addOption('level', '', ['INT'], 'l');
        $this->shouldThrow(RuntimeException::class)->duringAddOption('verbose', '');
        $this->shouldThrow(RuntimeException::class)->duringAddOption('depth', '', [], 'l');
    }

    function it_prevents_mismatched_options_defaults()
    {
        $this->shouldThrow(RuntimeException::class)->duringAddOption('level', '', ['INT'], 'l', true, [1, 2]);
    }

    function it_prevents_invalid_option_names()
    {
        $this->shouldThrow(RuntimeException::class)->duringAddOption('s', '');
        $this->shouldThrow(RuntimeException::class)->duringAddOption('short', '', [], 'sh');
    }

    function it_shows_usage_info_when_converted_to_string()
    {
        $this->__toString()->shouldBe("Usage:\n  my-application \n");
    }

    function it_supports_assignments_with_colon()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-l | --level}']
        );
        $this->addOption('level', '', ['INT'], 'l');
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -l:2'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('level');
        $descriptor->options['level']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['level']->values->shouldIterateAs(['2']);
        $descriptor->options['level']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs([]);
    }

    function it_supports_assignments_with_equals()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-l | --level}']
        );
        $this->addOption('level', '', ['INT'], 'l');
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -l=2'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('level');
        $descriptor->options['level']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['level']->values->shouldIterateAs(['2']);
        $descriptor->options['level']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs([]);
    }

    function it_supports_assignments_with_space()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-l | --level}']
        );
        $this->addOption('level', '', ['INT'], 'l');
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -l 2'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('level');
        $descriptor->options['level']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['level']->values->shouldIterateAs(['2']);
        $descriptor->options['level']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs([]);
    }

    function it_supports_assignments_without_operator()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-l | --level}']
        );
        $this->addOption('level', '', ['INT'], 'l');
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -l2'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('level');
        $descriptor->options['level']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['level']->values->shouldIterateAs(['2']);
        $descriptor->options['level']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs([]);
    }

    function it_supports_different_invocations()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-h | --help}', 'INFILE OUTFILE']
        );
        $this->addOption('help', 'Show this usage info', [], 'h', true);
        $this->__toString()->shouldBe(
            <<<'EOD'
Usage:
  my-application {-h | --help}
  my-application INFILE OUTFILE

Options (optional):
  -h  --help  Show this usage info

EOD
        )
        ;
    }

    function it_supports_optional_switches_with_defaults()
    {
        $this->beConstructedWith(
            'my-application',
            ['--origin POINT --destination POINT']
        );
        $this->addOption('origin', '', ['X', 'Y', 'Z'], 'o', true, [-1, 0, 1]);
        $this->addOption('destination', '', ['X', 'Y', 'Z'], 'd');
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -d 2 3 4'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('origin');
        $descriptor->options['origin']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['origin']->values->shouldIterateAs([-1, 0, 1]);
        $descriptor->options['origin']->count->shouldBe(0);
        $descriptor->options->shouldHaveKey('destination');
        $descriptor->options['destination']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['destination']->values->shouldIterateAs(['2', '3', '4']);
        $descriptor->options['destination']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs([]);
    }

    function it_supports_parameters_between_options()
    {
        $this->beConstructedWith(
            'my-application',
            ['{-l | --level} [-v | --verbose]* INFILE OUTFILE']
        );
        $this->addOption('verbose', 'Increase verbosity', [], 'v', true, []);
        $this->addOption('level', '', ['INT'], 'l');
        $descriptor =
            $this->processCommandLine(explode(' ', 'my-application --verbose foo.in -l some.level bar.out -v'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('verbose');
        $descriptor->options['verbose']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['verbose']->values->shouldIterateAs([]);
        $descriptor->options['verbose']->count->shouldBe(2);
        $descriptor->options->shouldHaveKey('level');
        $descriptor->options['level']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['level']->values->shouldIterateAs(['some.level']);
        $descriptor->options['level']->count->shouldBe(1);
        $descriptor->params->shouldIterateAs(['foo.in', 'bar.out']);
    }

    function it_supports_repeated_switches()
    {
        $this->beConstructedWith(
            'my-application',
            ['[-v | --verbose]*']
        );
        $this->addOption('verbose', 'Increase verbosity', [], 'v', true);
        $this->__toString()->shouldBe(
            <<<'EOD'
Usage:
  my-application [-v | --verbose]*

Options (optional):
  -v  --verbose  Increase verbosity

EOD
        )
        ;
        $descriptor = $this->processCommandLine(explode(' ', 'my-application -vv --verbose'));
        $descriptor->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options->shouldBeArray();
        $descriptor->options->shouldHaveKey('verbose');
        $descriptor->options['verbose']->shouldBeAnInstanceOf(stdClass::class);
        $descriptor->options['verbose']->values->shouldIterateAs([]);
        $descriptor->options['verbose']->count->shouldBe(3);
        $descriptor->params->shouldIterateAs([]);
    }

    function let()
    {
        $this->beConstructedWith('my-application', ['']);
    }
}
