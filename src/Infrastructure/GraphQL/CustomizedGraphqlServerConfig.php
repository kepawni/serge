<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\GraphQL;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Throwable;

/**
 * Supports description directives in a GraphQL Type Language schema. Example:
 *     type SomeObjectType {
 *         # the space after the @ can be dropped and is here solely to
 *         # prevent the doc comment parser from thinking it is a doc tag
 *         someField: [SomeOtherType]  @ description (
 *             _: "Whatever argument names"
 *             a: "you use (even duplicate),"
 *             x: "every single of them makes up"
 *             _: 1
 *             c: "line of the field description"
 *         )
 *     }
 */
class CustomizedGraphqlServerConfig extends ServerConfig
{
    /**
     * @param Schema $schema
     * @param mixed $context
     * @param mixed $rootValue
     */
    public function __construct(Schema $schema, $context, $rootValue = null)
    {
        $this
            ->setSchema($this->processDescriptionDirectives($schema))
            ->setErrorFormatter([$this, 'formatError'])
            ->setRootValue($rootValue)
            ->setContext($context);
    }

    /**
     * @param Throwable $error
     *
     * @return array
     */
    public function formatError(Throwable $error): array
    {
        return [
            get_class($error) => [
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => preg_split('<\n|\r\n?>', $error->getTraceAsString()),
                'previous' => $error->getPrevious() ? $this->formatError($error->getPrevious()) : null
            ]
        ];
    }

    private function processDescriptionDirectives(Schema $schema): Schema
    {
        $valueFromArg = function (ArgumentNode $arg) {
            return $arg->value->value;
        };
        /** @var Type $type */
        foreach ($schema->getTypeMap() as $type) {
            if ($type instanceof ObjectType || $type instanceof InterfaceType || $type instanceof InputObjectType) {
                /** @var DirectiveDefinitionNode $directive */
                foreach ($type->astNode->directives ?? [] as $directive) {
                    if ($directive->name->value == 'description') {
                        $type->description = implode(
                            PHP_EOL,
                            array_map($valueFromArg, iterator_to_array($directive->arguments))
                        );
                    }
                }
                /** @var FieldDefinition|InputObjectField $field */
                try {
                    foreach ($type->getFields() as $field) {
                        /** @var DirectiveDefinitionNode $directive */
                        foreach ($field->astNode->directives ?? [] as $directive) {
                            if ($directive->name->value == 'description') {
                                $field->description = implode(
                                    PHP_EOL,
                                    array_map($valueFromArg, iterator_to_array($directive->arguments))
                                );
                            }
                        }
                    }
                } catch (InvariantViolation $e) {
                }
            }
        }
        return $schema;
    }
}
