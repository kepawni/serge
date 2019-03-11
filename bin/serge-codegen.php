<?php declare(strict_types=1);
use GraphQL\Utils\BuildSchema;
use Kepawni\Serge\CodeGenerator\ClassWriter;
use Kepawni\Serge\CodeGenerator\GraphQlAggregateGenerator;
use Kepawni\Serge\CodeGenerator\GraphQlCommandHandlerGenerator;
use Kepawni\Serge\CodeGenerator\GraphQlEventPayloadGenerator;
use Kepawni\Serge\CodeGenerator\GraphQlSchemaGateway;
use Kepawni\Serge\CodeGenerator\GraphQlValueObjectGenerator;

$autoloaderDir = locateAutoloaderDir($argv ?? []);
$configFile = $autoloaderDir . '/../serge.config.xml';
$schemaFile = $autoloaderDir . '/kepawni/serge/serge.config.xsd';
require_once $autoloaderDir . '/autoload.php';
$config = new DOMDocument();
if (!$config->load($configFile)) {
    throw new RuntimeException('Failed to load config file');
}
if (!$config->schemaValidate($schemaFile)) {
    throw new RuntimeException('Failed to validate config file against XSD schema');
};
$xpath = new DOMXPath($config);
if (!$xpath->registerNamespace('s', 'https://github.com/kepawni/serge')) {
    throw new RuntimeException('Failed to register namespace of config file');
}
$namespaceRootDirectory = $xpath->evaluate('string(/s:serge-code-generator/s:destination/@directory)');
$rootNamespace = $xpath->evaluate('string(/s:serge-code-generator/s:destination/@namespace)');
$handlerNamespace = $rootNamespace . '\\'
    . $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:handler/@sub-namespace)');
$aggregateNamespace = $rootNamespace . '\\'
    . $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:aggregate/@sub-namespace)');
$valueObjectNamespace = $rootNamespace . '\\'
    . $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:value-object/@sub-namespace)');
$eventPayloadNamespace = $rootNamespace . '\\'
    . $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:event-payload/@sub-namespace)');
$aggregatePrefix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:aggregate/@prefix)');
$aggregateSuffix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:aggregate/@suffix)');
$handlerPrefix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:handler/@prefix)');
$handlerSuffix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:handler/@suffix)');
$valueObjectPrefix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:value-object/@prefix)');
$valueObjectSuffix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:value-object/@suffix)');
$eventPayloadPrefix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:event-payload/@prefix)');
$eventPayloadSuffix = $xpath->evaluate('string(/s:serge-code-generator/s:destination/s:event-payload/@suffix)');
$schema = BuildSchema::build(
    file_get_contents($xpath->evaluate('string(/s:serge-code-generator/s:source/@graphql-schema)'))
);
$schemaGateway = new GraphQlSchemaGateway($schema);
$writer = new ClassWriter($namespaceRootDirectory, $rootNamespace);
$aggregateGenerator = new GraphQlAggregateGenerator(
    $schemaGateway,
    $aggregateNamespace, $aggregatePrefix, $aggregateSuffix,
    $eventPayloadNamespace, $eventPayloadPrefix, $eventPayloadSuffix,
    $valueObjectNamespace, $valueObjectPrefix, $valueObjectSuffix
);
$commandHandlerGenerator = new GraphQlCommandHandlerGenerator(
    $schemaGateway,
    $handlerNamespace, $handlerPrefix, $handlerSuffix,
    $aggregateNamespace, $aggregatePrefix, $aggregateSuffix,
    $eventPayloadNamespace,
    $valueObjectNamespace, $valueObjectPrefix, $valueObjectSuffix
);
$eventPayloadGenerator = new GraphQlEventPayloadGenerator(
    $schemaGateway,
    $eventPayloadNamespace, $eventPayloadPrefix, $eventPayloadSuffix,
    $valueObjectNamespace, $valueObjectPrefix, $valueObjectSuffix
);
$valueObjectGenerator = new GraphQlValueObjectGenerator(
    $schemaGateway,
    $valueObjectNamespace,
    $valueObjectPrefix,
    $valueObjectSuffix
);
foreach ($schemaGateway->iterateAggregates() as $aggregate) {
    $writer->saveToFile(
        $aggregatePrefix . $aggregate->name . $aggregateSuffix,
        $aggregateNamespace,
        $aggregateGenerator->process($aggregate)
    );
    $writer->saveToFile(
        $handlerPrefix . $aggregate->name . $handlerSuffix,
        $handlerNamespace,
        $commandHandlerGenerator->process($aggregate)
    );
    foreach ($schemaGateway->iterateAggregateEvents($aggregate) as $event) {
        $writer->saveToFile(
            $eventPayloadPrefix . $event->name . $eventPayloadSuffix,
            str_replace('#', $aggregate->name, $eventPayloadNamespace),
            $eventPayloadGenerator->process($event, $aggregate->name)
        );
    }
}
foreach ($schemaGateway->iterateValueObjects() as $valueObject) {
    $writer->saveToFile(
        $valueObjectPrefix . $valueObject->name . $valueObjectSuffix,
        $valueObjectNamespace,
        $valueObjectGenerator->process($valueObject)
    );
}
//
function locateAutoloaderDir(array $argv): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('This is a command line executable');
    }
    if (!isset($argv[0])) {
        throw new RuntimeException('Could not locate executable script');
    }
    $realpath = realpath(dirname($argv[0]));
    $lastTwo = implode('/', array_slice(explode(DIRECTORY_SEPARATOR, $realpath), -2));
    if ($lastTwo === 'vendor/bin') {
        return $realpath . '/..';
    }
    $path = explode(DIRECTORY_SEPARATOR, realpath(__DIR__));
    foreach (array_keys($path) as $i) {
        $filename = implode('/', array_slice($path, 0, -$i)) . '/vendor';
        if (is_file($filename)) {
            return $filename;
        }
    }
    return __DIR__ . '/../vendor';
}
