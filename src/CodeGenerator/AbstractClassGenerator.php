<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Implementations can create a class file for every method of a descriptor interface. This interface describes a whole
 * namespace of classes. The methods will be turned into classes and the method parameters become the members of that
 * class. The purpose of the generated classes is specific to the implementation of this abstract generator.
 */
abstract class AbstractClassGenerator
{
    protected $inheritance = '';
    protected $initialUseStatements = [];
    protected $isMagicMethodDocTagEnabled = false;
    protected $namePrefixPattern = '<^_>';
    private $constructorBody;
    private $constructorSignature;
    private $methodTags;
    private $propertyTags;
    private $unwindItems;
    private $useStatements;
    private $windUpItems;

    public function processClassDescriptorMethod(
        ReflectionMethod $classDescriptor,
        string $namespaceRootDir,
        string $rootNamespace,
        string $subNamespace,
        ?string $propertyRootNamespace = null
    ): void {
        $this->methodTags = [];
        $this->propertyTags = [];
        $this->constructorBody = [];
        $this->constructorSignature = [];
        $this->unwindItems = [];
        $this->windUpItems = [];
        $this->useStatements = $this->initialUseStatements;
        foreach ($classDescriptor->getParameters() as $propertyDescriptor) {
            $this->processProperties(
                $propertyDescriptor,
                $classDescriptor->getDeclaringClass()->getNamespaceName(),
                $propertyRootNamespace ?: $rootNamespace
            );
        }
        sort($this->useStatements);
        $this->savePhpFile(
            $namespaceRootDir,
            $rootNamespace,
            $subNamespace,
            $classDescriptor->getName()
        );
    }

    public function processNamespaceDescriptorClass(
        ReflectionClass $namespaceDescriptor,
        string $namespaceRootDir,
        string $rootNamespace,
        ?string $propertyRootNamespace = null
    ): void {
        $subNamespace = str_replace(
            '_',
            '\\',
            preg_replace($this->namePrefixPattern, '', $namespaceDescriptor->getShortName())
        );
        foreach ($namespaceDescriptor->getMethods() as $classDescriptor) {
            $this->processClassDescriptorMethod(
                $classDescriptor,
                $namespaceRootDir,
                $rootNamespace,
                $subNamespace,
                $propertyRootNamespace
            );
        }
    }

    protected function addConstructorParam(
        string $constructorParam,
        ?string $typeName,
        ReflectionParameter $propertyDescriptor
    ): void {
        $this->constructorSignature[] = sprintf(
            '%s%s%s',
            $typeName && $propertyDescriptor->getType()->allowsNull() ? '?' : '',
            $constructorParam,
            $propertyDescriptor->isOptional() && $propertyDescriptor->isDefaultValueAvailable()
                ? ' = '
                . ($propertyDescriptor->isDefaultValueConstant()
                    ? $this->shortClassName($propertyDescriptor->getDefaultValueConstantName())
                    : json_encode(
                        $propertyDescriptor->getDefaultValue(),
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    )
                )
                : ''
        );
        $this->constructorBody[] = sprintf('        $this->init(\'%s\', $%1$s);', $propertyDescriptor->name);
    }

    protected function addFoldableParam(
        ?string $classType,
        ?string $shortClassType,
        ReflectionParameter $propertyDescriptor
    ): void {
        $this->useStatements[] = sprintf('use %s;', ltrim($classType, '\\'));
        $this->unwindItems[] = sprintf(
            '            %s::unfold($spool[%d])',
            $shortClassType,
            count($this->unwindItems)
        );
        $this->windUpItems[] = sprintf('            $this->%s->fold()', $propertyDescriptor->name);
    }

    protected function addMagicAccessorDocTags(string $typeReference, ReflectionParameter $propertyDescriptor): void
    {
        $this->propertyTags[] = sprintf(
            ' * @property-read %s%s $%s',
            $typeReference,
            $propertyDescriptor->allowsNull() ? '|null' : '',
            $propertyDescriptor->name
        );
        $this->methodTags[] = sprintf(
            ' * @method self with%s(%s%s $v)',
            ucfirst($propertyDescriptor->name),
            $propertyDescriptor->allowsNull() ? '?' : '',
            $typeReference
        );
    }

    protected function addScalarParam(ReflectionParameter $propertyDescriptor, ?string $typeFunction): void
    {
        $this->unwindItems[] = sprintf(
            '            %s%s($spool[%d])',
            $propertyDescriptor->allowsNull()
                ? sprintf('is_null($spool[%d]) ? null : ', count($this->unwindItems))
                : '',
            $typeFunction ?: 'unserialize',
            count($this->unwindItems)
        );
        $this->windUpItems[] = sprintf(
            $typeFunction ? '            $this->%s' : '            serialize($this->%s)',
            $propertyDescriptor->name
        );
    }

    protected function processProperties(
        ReflectionParameter $propertyDescriptor,
        string $codeGenNamespace,
        string $rootNamespace
    ): void {
        $typeName = $propertyDescriptor->hasType() ? $propertyDescriptor->getType()->getName() : null;
        $classType = $typeName && !$propertyDescriptor->getType()->isBuiltin()
            ? (substr($typeName, 0, strlen($codeGenNamespace)) === $codeGenNamespace
                ? $rootNamespace . '\\' . trim(
                    str_replace('_', '\\', substr($typeName, strlen($codeGenNamespace))),
                    '\\'
                )
                : $typeName
            )
            : null;
        $shortClassType = $this->shortClassName($classType);
        $typeReference = $shortClassType ?: $typeName ?: 'mixed';
        if ($classType) {
            $this->addFoldableParam($classType, $shortClassType, $propertyDescriptor);
            $constructorParam = sprintf('%s $%s', $shortClassType, $propertyDescriptor->name);
        } else {
            $this->addScalarParam($propertyDescriptor, $this->typeToFunction($typeName));
            $constructorParam = sprintf('%s$%s', $typeName ? $typeName . ' ' : '', $propertyDescriptor->name);
        }
        $this->addConstructorParam($constructorParam, $typeName, $propertyDescriptor);
        $this->addMagicAccessorDocTags($typeReference, $propertyDescriptor);
    }

    protected function savePhpFile(
        string $namespaceRootDir,
        string $rootNamespace,
        string $subNamespace,
        string $className
    ): void {
        $namespace = trim($rootNamespace . '\\' . trim($subNamespace, '\\'), '\\');
        $outputDir = str_replace(
            [trim($rootNamespace, '\\') . '\\', '\\'],
            [rtrim($namespaceRootDir, '/\\') . '/', '/'],
            $namespace . '\\'
        );
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        file_put_contents(
            sprintf('%s/%s.php', $outputDir, $className),
            sprintf(
                <<<'EOD'
<?php declare(strict_types=1);
namespace %s;

%s

/**
%s%s
 */
class %s%s
{
    public function __construct(%s)
    {
%s
    }

    /**
     * @param array $spool
     *
     * @return static
     */
    public static function unwind(array $spool): Windable
    {
        return new self(
%s
        );
    }

    public function windUp(): array
    {
        return [
%s
        ];
    }
}

EOD
                ,
                $namespace,
                implode("\n", $this->useStatements),
                implode("\n", $this->propertyTags),
                $this->isMagicMethodDocTagEnabled ? "\n" . implode("\n", $this->methodTags) : '',
                $className,
                $this->inheritance,
                implode(", ", $this->constructorSignature),
                implode("\n", $this->constructorBody),
                implode(",\n", $this->unwindItems),
                implode(",\n", $this->windUpItems)
            )
        );
    }

    protected function shortClassName(?string $namespacedClassName): ?string
    {
        return $namespacedClassName ? strval(array_reverse(explode('\\', $namespacedClassName))[0]) : null;
    }

    protected function typeToFunction(?string $typeName): ?string
    {
        switch ($typeName) {
            case 'bool':
                return 'boolval';
            case 'int':
                return 'intval';
            case 'float':
                return 'floatval';
            case 'string':
                return 'strval';
            default:
                return null;
        }
    }
}
