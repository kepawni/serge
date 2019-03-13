<?php declare(strict_types=1);
/**
 * Usage, when called from command line:
 *     php index.php [ REQUEST.graphql [ VARIABLES.json ] ]
 * REQUEST.graphql
 *     A file containing a GraphQL query or mutation request. If omitted, the
 *     request will default to querying the status.
 * VARIABLES.json
 *     An optional file holding a JSON-encoded hashmap of variable definitions.
 */
use GraphQL\Server\OperationParams;
use GraphQL\Server\StandardServer;
use Kepawni\Serge\Infrastructure\GraphQL\CqrsCommandBus;
use Kepawni\Serge\Infrastructure\GraphQL\CustomizedGraphqlServerConfig;
use Kepawni\Serge\Infrastructure\GraphQL\SchemaFileCache;
use Kepawni\Serge\Infrastructure\GraphQL\TypeResolver;

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    if (($_SERVER['REQUEST_METHOD'] ?? null) == 'OPTIONS') {
        header('Access-Control-Allow-Headers: content-type');
        header('Access-Control-Allow-Methods: POST');
        exit;
    }
}
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/694b59db-816c-40b2-9d81-bf06f6192b0f';
$schemaCache = __DIR__ . '/c6b514bd-b9e0-4b43-a2fd-88b3d32d2a24';
$schemaFile = __DIR__ . '/a266afe5-d493-4160-868c-112fc087a20es';
$context = null;
$rootValue = null;
$serverConfig = null;
$typeResolver = new TypeResolver();
$typeResolver->addResolverForField('CqrsQuery', 'status', function () {
    return true;
});
$commandBus = new CqrsCommandBus('CqrsAggregateMutators', $typeResolver);
addCommandHandlersToCommandBus($commandBus);
try {
    $schemaFileCache = new SchemaFileCache($schemaCache);
    $schema = $schemaFileCache->loadCacheForFile($schemaFile, $commandBus->generateTypeConfigDecorator());
    $serverConfig = new CustomizedGraphqlServerConfig($schema, $context, $rootValue);
    $standardServer = new StandardServer($serverConfig);
    if (PHP_SAPI === 'cli') {
        $query = isset($argv[1]) ? file_get_contents($argv[1]) : 'query { status }';
        $variables = isset($argv[2]) ? file_get_contents($argv[2]) : '{}';
        echo json_encode(
            $standardServer->executeRequest(
                OperationParams::create(
                    ['query' => $query, 'variables' => $variables],
                    substr(ltrim($query), 0, 8) === 'mutation'
                )
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    } else {
        $standardServer->handleRequest();
    }
} catch (Throwable $e) {
    StandardServer::send500Error(
        $serverConfig
            ? new Exception(json_encode($serverConfig->formatError($e), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            : $e,
        true
    );
}
