<?php declare(strict_types=1);
use deswerve\colin\CommandLineInterface;
use Kepawni\Serge\CodeGenerator\EventPayloadGenerator;
use Kepawni\Serge\CodeGenerator\ValueObjectGenerator;
use PhpSpec\Factory\ReflectionFactory;

require_once __DIR__ . '/../vendor/autoload.php';
try {
    $cli = createCommandLineInterpreter();
    $commandLine = $cli->processCommandLine($argv);
    $files = $commandLine->params;
    $namespaceInterfaces = scanFilesForInterfaceNames($files);
    $reflector = new ReflectionFactory();
    foreach ($namespaceInterfaces as $namespaceClass) {
        createCodeGeneratorForType($commandLine)->processNamespaceDescriptorClass(
            $reflector->create($namespaceClass),
            $commandLine->options['dir']->values[0],
            convertNamespaceSeparators($commandLine->options['root-ns']->values[0]),
            convertNamespaceSeparators($commandLine->options['param-ns']->values[0])
        );
    }
} catch (Throwable $e) {
    echo $cli, PHP_EOL;
    throw $e;
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
    $cli = new CommandLineInterface(
        'codegen',
        [
            '--help',
            'OPTIONS DESCRIPTOR_FILES...',
        ]
    );
    $cli
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
    ;
    return $cli;
}

function scanFilesForInterfaceNames(array $files): array
{
    $interfaces = get_declared_interfaces();
    foreach ($files as $file) {
        include_once $file;
    }
    return array_diff(get_declared_interfaces(), $interfaces);
}
