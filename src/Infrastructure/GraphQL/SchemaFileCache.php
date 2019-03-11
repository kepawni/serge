<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use InvalidArgumentException;

class SchemaFileCache
{
    private $cacheDirectory;
    private $cacheFileExtension;
    private $origFileExtension;

    /**
     * SchemaFileCache constructor.
     * @param string $cacheDirectory
     * @param string $origFileExtension
     * @param string $cacheFileExtension
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $cacheDirectory,
        string $origFileExtension = '.graphqls',
        string $cacheFileExtension = '.inc.php'
    ) {
        if (!is_dir($cacheDirectory)) {
            throw new InvalidArgumentException('Cache directory does not exist: ' . $cacheDirectory);
        }
        $this->cacheDirectory = realpath($cacheDirectory);
        $this->origFileExtension = $origFileExtension;
        $this->cacheFileExtension = $cacheFileExtension;
    }

    /**
     * @param string $schemaPath
     * @param callable $typeConfigDecorator (array $origTypeConfig, DefinitionNode $typeDefNode, array $allDefNodes): array
     * Receives the original type configuration as an associative array, the DefinitionNode for that type and an associative
     * array of DefinitionNodes for all types and is expected to return an associative array representing the altered type
     * configuration.
     *
     * @return Schema
     * @throws Error
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws SyntaxError
     */
    public function loadCacheForFile(string $schemaPath, $typeConfigDecorator): Schema
    {
        $cacheFilename = $this->createCacheFileNameForOriginalPath($schemaPath);
        if (!file_exists($cacheFilename)) {
            $schemaSourceCode = file_get_contents($schemaPath);
            return $this->fillCacheFromSourceCode($cacheFilename, $schemaSourceCode, $typeConfigDecorator);
        }
        return $this->restoreFromExistingCache($cacheFilename, $typeConfigDecorator);
    }

    /**
     * @param string $schemaSourceCode
     * @param callable $typeConfigDecorator (array $origTypeConfig, DefinitionNode $typeDefNode, array $allDefNodes): array
     * Receives the original type configuration as an associative array, the DefinitionNode for that type and an associative
     * array of DefinitionNodes for all types and is expected to return an associative array representing the altered type
     * configuration.
     *
     * @return Schema
     * @throws Error
     * @throws InvariantViolation
     * @throws SyntaxError
     */
    public function loadCacheForSource(string $schemaSourceCode, $typeConfigDecorator): Schema
    {
        $cacheFilename = $this->createCacheFileNameForSourceCode($schemaSourceCode);
        if (!file_exists($cacheFilename)) {
            return $this->fillCacheFromSourceCode($cacheFilename, $schemaSourceCode, $typeConfigDecorator);
        }
        /** @var DocumentNode $document */
        return $this->restoreFromExistingCache($cacheFilename, $typeConfigDecorator);
    }

    /**
     * @param string $schemaPath
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function createCacheFileNameForOriginalPath(string $schemaPath): string
    {
        if (!file_exists($schemaPath)) {
            throw new InvalidArgumentException('Could not open original schema file: ' . $schemaPath);
        }
        return sprintf(
            '%s/%s%s',
            $this->cacheDirectory,
            basename($schemaPath, $this->origFileExtension),
            $this->cacheFileExtension
        );
    }

    /**
     * @param string $schemaSourceCode
     *
     * @return string
     */
    protected function createCacheFileNameForSourceCode(string $schemaSourceCode): string
    {
        return sprintf(
            '%s/%s%s',
            $this->cacheDirectory,
            sha1($schemaSourceCode),
            $this->cacheFileExtension
        );
    }

    /**
     * @param $cacheFilename
     * @param $schemaSourceCode
     * @param $typeConfigDecorator
     *
     * @return Schema
     * @throws Error
     * @throws InvariantViolation
     * @throws SyntaxError
     */
    protected function fillCacheFromSourceCode($cacheFilename, $schemaSourceCode, $typeConfigDecorator): Schema
    {
        $document = $this->parseSchemaCodeAndPutIntoCache($schemaSourceCode, $cacheFilename);
        $schema = BuildSchema::buildAST($document, $typeConfigDecorator);
        $schema->assertValid();
        return $schema;
    }

    /**
     * @param $schemaSourceCode
     * @param $cacheFilename
     *
     * @return DocumentNode
     * @throws SyntaxError
     */
    protected function parseSchemaCodeAndPutIntoCache($schemaSourceCode, $cacheFilename): DocumentNode
    {
        $document = Parser::parse($schemaSourceCode);
        file_put_contents($cacheFilename, "<?php\nreturn " . var_export(AST::toArray($document), true) . ';');
        return $document;
    }

    /**
     * @param $cacheFilename
     * @param $typeConfigDecorator
     *
     * @return Schema
     * @throws InvariantViolation
     * @throws Error
     */
    protected function restoreFromExistingCache($cacheFilename, $typeConfigDecorator): Schema
    {
        /** @var DocumentNode $document */
        $document = AST::fromArray(require $cacheFilename);
        return BuildSchema::buildAST($document, $typeConfigDecorator);
    }
}
