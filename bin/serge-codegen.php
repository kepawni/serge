<?php declare(strict_types=1);
use deswerve\colin\CommandLineInterface;
use Kepawni\Serge\CodeGenerator\EventPayloadGenerator;
use Kepawni\Serge\CodeGenerator\ValueObjectGenerator;
use PhpSpec\Factory\ReflectionFactory;

require_once __DIR__ . '/../vendor/autoload.php';
$cli = '';
try {
    $cli = createCommandLineInterpreter();
    $commandLine = $cli->processCommandLine($argv);
    if ($commandLine->options['help']->count >= 1) {
        echo $cli;
    } else {
        processNamespaceDescriptors(
            scanFilesForInterfaceNames($commandLine->params),
            $commandLine,
            new ReflectionFactory()
        );
    }
} catch (Throwable $e) {
    printf('%s: %s%4$s%4$s%s', get_class($e), $e->getMessage(), $cli, PHP_EOL);
}
//
function convertNamespaceSeparators(?string $namespace): ?string
{
    return $namespace ? preg_replace('<\\W+>', '\\', $namespace) : null;
}

function createCodeGeneratorForType($commandLine)
{
    switch ($commandLine->options['type']->values[0]) {
        case 'EventPayload':
            return new EventPayloadGenerator();
        case 'ValueObject':
            return new ValueObjectGenerator();
        default:
            throw new RuntimeException(
                sprintf('Type %s not supported', $commandLine->options['type']->values[0])
            );
    }
}

function createCommandLineInterpreter(): CommandLineInterface
{
    return (new CommandLineInterface('serge-codegen', ['{--help | -h}', 'OPTIONS DESCRIPTOR_FILES...',]))
        ->addOption('type', 'One of EventPayload, ValueObject', ['TYPE'], 't')
        ->addOption(
            'root-ns',
            'The root namespace for the generated classes (which may declare sub-namespaces)',
            ['NAMESPACE'],
            'r',
            true,
            ['\\Kepawni\\Serge\\Model']
        )
        ->addOption(
            'param-ns',
            'The namespace to assume for undeclared value object params (which may declare sub-namespaces)',
            ['NAMESPACE'],
            'p',
            true,
            [null]
        )
        ->addOption(
            'dir',
            'The target directory corresponding to the root namespace',
            ['PATH'],
            'd',
            true,
            [__DIR__ . '/../src/Model']
        )
        ->addOption('help', 'Show this usage info and ignore any other options', null, 'h')
        ;
}

function guardNonEmpty(array $namespaceInterfaces): array
{
    if (!$namespaceInterfaces) {
        throw new Exception('Could not find any namespace descriptor interfaces in the specified file paths.');
    }
    return $namespaceInterfaces;
}

function processNamespaceDescriptors($namespaceInterfaces, $commandLine, $reflector): void
{
    foreach (guardNonEmpty($namespaceInterfaces) as $namespaceClass) {
        createCodeGeneratorForType($commandLine)->processNamespaceDescriptorClass(
            $reflector->create($namespaceClass),
            $commandLine->options['dir']->values[0],
            convertNamespaceSeparators($commandLine->options['root-ns']->values[0]),
            convertNamespaceSeparators($commandLine->options['param-ns']->values[0])
        );
    }
}

function scanFilesForInterfaceNames(array $files): array
{
    $interfaces = get_declared_interfaces();
    foreach ($files as $file) {
        include_once $file;
    }
    return array_diff(get_declared_interfaces(), $interfaces);
}
