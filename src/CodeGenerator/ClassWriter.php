<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class ClassWriter
{
    /** @var string */
    private $namespaceRootDirectory;
    /** @var string */
    private $rootNamespace;

    /**
     * ClassWriter constructor.
     *
     * @param string $namespaceRootDirectory
     * @param string $rootNamespace
     */
    public function __construct(string $namespaceRootDirectory, string $rootNamespace)
    {
        $this->namespaceRootDirectory = $namespaceRootDirectory;
        $this->rootNamespace = $rootNamespace;
    }

    public function saveToFile(string $name, string $namespace, Classifier $class): void
    {
        file_put_contents(
            sprintf('%s/%s.php', $this->directoryForNamespace($namespace), $name),
            strval($class)
        );
    }

    private function directoryForNamespace(string $namespace): string
    {
        $trimmedNamespace = trim($namespace, '\\');
        $rootNsLength = strlen($this->rootNamespace . '\\');
        $dir = sprintf(
            '%s/%s',
            $this->namespaceRootDirectory,
            str_replace(
                '\\',
                '/',
                substr($trimmedNamespace, 0, $rootNsLength) === $this->rootNamespace . '\\'
                    ? substr($trimmedNamespace, $rootNsLength)
                    : $trimmedNamespace
            )
        );
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
}
