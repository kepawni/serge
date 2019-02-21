<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class Classifier
{
    private $abstract;
    private $constants;
    private $docComment;
    private $final;
    private $interface;
    private $interfaces;
    private $methods;
    private $name;
    private $namespace;
    private $parent;
    private $properties;
    private $usedClasses;

    public function __construct(string $name, string $namespace = '')
    {
        $this->abstract = false;
        $this->constants = [];
        $this->docComment = null;
        $this->final = false;
        $this->interface = false;
        $this->interfaces = [];
        $this->methods = [];
        $this->name = $name;
        $this->namespace = $namespace;
        $this->parent = null;
        $this->properties = [];
        $this->usedClasses = [];
    }

    public function __toString(): string
    {
        sort($this->usedClasses);
        sort($this->interfaces);
        ksort($this->constants);
        usort($this->properties, [$this, 'compareProperties']);
        usort($this->methods, [$this, 'compareMethods']);
        $classBlock = new IndentedMultilineBlock('{', '}', "\n");
        if ($this->constants) {
            $classBlock->addContentString(
                implode(
                    "\n",
                    array_map([$this, 'formatConstant'], array_keys($this->constants), array_values($this->constants))
                )
            );
        }
        if ($this->properties) {
            $classBlock->addContentString(implode(";\n", $this->properties) . ';');
        }
        $classBlock->addContentString(
            implode(
                "\n\n",
                $this->interface
                    ? array_map([$this, 'methodSignature'], $this->methods)
                    : $this->methods
            )
        );
        return sprintf(
            "<?php declare(strict_types=1);\n%s%s\n%s%s%s%s%s%s%s\n%s\n",
            $this->namespace ? 'namespace ' . trim($this->namespace, '\\') . ";\n" : '',
            $this->usedClasses ? "\nuse " . implode(";\nuse ", array_unique($this->usedClasses)) . ";\n" : '',
            $this->docComment ?: '',
            $this->abstract ? 'abstract ' : '',
            $this->final ? 'final ' : '',
            $this->interface ? 'interface ' : 'class ',
            $this->name,
            $this->parent ? ' extends ' . $this->parent : '',
            $this->interfaces
                ? ($this->isInterface() ? ' extends ' : ' implements ') . implode(', ', array_unique($this->interfaces))
                : '',
            $classBlock
        );
    }

    public function addConstant(string $name, $value): self
    {
        $this->constants[$name] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function addDocCommentLine(string $line): self
    {
        if (is_null($this->docComment)) {
            $this->docComment = new CodeBlock("/**\n * ", "\n */\n", "\n * ");
        }
        $this->docComment->addContentString($line);
        return $this;
    }

    public function addMethod(Method $method): self
    {
        $this->methods[] = $method;
        $this->usedClasses = array_merge($this->usedClasses, $method->getUsedClasses());
        return $this;
    }

    public function addProperty(Property $property): self
    {
        $this->properties[] = $property;
        if ($property->getType()->getNamespace() !== null) {
            $this->usedClasses[] = trim($property->getType()->getFullName(), '\\');
        }
        return $this;
    }

    public function extend(Classifier $classifier): self
    {
        if ($this->isInterface()) {
            $this->interfaces[] = $classifier->getName();
        } else {
            $this->parent = $classifier->getName();
        }
        $this->usedClasses[] = trim($classifier->getFullName(), '\\');
        return $this;
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function getFullName()
    {
        return '\\' . trim($this->namespace, '\\') . '\\' . $this->name;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function implement(Classifier $interface): self
    {
        $this->interfaces[] = $interface->getName();
        $this->usedClasses[] = trim($interface->getFullName(), '\\');
        return $this;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isInterface(): bool
    {
        return $this->interface;
    }

    public function makeAbstract(bool $value = true): self
    {
        $this->abstract = $value;
        if ($value) {
            $this->final = false;
            $this->interface = false;
        }
        return $this;
    }

    public function makeFinal(bool $value = true): self
    {
        $this->final = $value;
        if ($value) {
            $this->abstract = false;
            $this->interface = false;
        }
        return $this;
    }

    public function makeInterface(bool $value = true): self
    {
        $this->interface = $value;
        if ($value) {
            $this->abstract = false;
            $this->final = false;
        }
        return $this;
    }

    private function compareMethods(Method $a, Method $b): int
    {
        return intval($b->isStatic()) - intval($a->isStatic())
            ?: (
                intval($a->isProtected()) + 2 * intval($a->isPublic())
                - intval($b->isProtected()) - 2 * intval($a->isPublic())
            )
                ?: strcmp($a->getName(), $b->getName());
    }

    private function compareProperties(Property $a, Property $b): int
    {
        return intval($b->isStatic()) - intval($a->isStatic())
            ?: intval($b->getName() === '__construct') - intval($a->getName() === '__construct')
                ?: (
                    intval($a->isProtected()) + 2 * intval($a->isPublic())
                    - intval($b->isProtected()) - 2 * intval($a->isPublic())
                )
                    ?: strcmp($a->getName(), $b->getName());
    }

    private function formatConstant(string $name, string $value): string
    {
        return sprintf('const %s = %s;', $name, $value);
    }

    private function methodSignature(Method $method): string
    {
        return $method->generateSignature() . ';';
    }
}
