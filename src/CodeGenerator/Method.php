<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use ReflectionMethod;
use ReflectionParameter;

class Method extends IndentedMultilineBlock
{
    private $abstract;
    private $docComment;
    private $final;
    private $name;
    private $parameters;
    private $returnType;
    private $static;
    private $visibility;

    public function __construct(string $name)
    {
        parent::__construct();
        $this->abstract = false;
        $this->docComment = null;
        $this->final = false;
        $this->name = $name;
        $this->parameters = [];
        $this->returnType = new Type(Type::MIXED);
        $this->static = false;
        $this->visibility = 'public';
    }

    public function __toString(): string
    {
        return ($this->docComment ?: '') . $this->generateSignature() . (
            $this->isAbstract()
                ? ';'
                : PHP_EOL . $this->getPrefix() . $this->contentToString() . $this->getSuffix()
            );
    }

    public function addDocCommentLine(string $line): self
    {
        if (is_null($this->docComment)) {
            $this->docComment = new CodeBlock("/**\n * ", "\n */\n", "\n * ");
        }
        $this->docComment->addContentString($line);
        return $this;
    }

    public function appendParameter(Parameter $parameter): self
    {
        $this->parameters[] = $parameter;
        if ($parameter->getType()->getNamespace() !== null) {
            $this->useClass($parameter->getType()->getFullName());
        }
        return $this;
    }

    public function equalsSignatureOf(ReflectionMethod $existingMethod)
    {
        return $this->generateSignature() === $this->generateSignatureFromReflection($existingMethod);
    }

    public function generateSignature(): string
    {
        return sprintf(
            "%s%s%s %sfunction %s(%s)%s",
            $this->abstract ? 'abstract ' : '',
            $this->final ? 'final ' : '',
            $this->visibility,
            $this->static ? 'static ' : '',
            $this->name,
            implode(', ', $this->parameters),
            $this->returnType->toReturn()
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReturnType(): Type
    {
        return $this->returnType;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function isProtected(): bool
    {
        return $this->visibility === 'protected';
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function makeAbstract(bool $value = true): self
    {
        $this->abstract = $value;
        if ($value) {
            $this->final = false;
        }
        return $this;
    }

    public function makeFinal(bool $value = true): self
    {
        $this->final = $value;
        if ($value) {
            $this->abstract = false;
        }
        return $this;
    }

    public function makePrivate(): self
    {
        $this->visibility = 'private';
        return $this;
    }

    public function makeProtected(): self
    {
        $this->visibility = 'protected';
        return $this;
    }

    public function makePublic(): self
    {
        $this->visibility = 'public';
        return $this;
    }

    public function makeReturn(Type $returnType): self
    {
        $this->returnType = $returnType;
        if ($returnType->getNamespace() !== null) {
            $this->useClass($returnType->getFullName());
        }
        return $this;
    }

    public function makeStatic(bool $value = true): self
    {
        $this->static = $value;
        return $this;
    }

    private function generateParameterFromReflection(ReflectionParameter $parameter): string
    {
        return trim(
            sprintf(
                '%s%s $%s%s',
                $parameter->hasType() && $parameter->getType()->allowsNull() ? '?' : '',
                $parameter->hasType() ? array_reverse(explode('\\', $parameter->getType()->getName()))[0] : '',
                $parameter->getName(),
                $parameter->isDefaultValueAvailable()
                    ? ' = ' . ($parameter->isDefaultValueConstant()
                        ? $parameter->getDefaultValueConstantName()
                        : json_encode($parameter->getDefaultValue())
                    )
                    : ''
            )
        );
    }

    private function generateSignatureFromReflection(ReflectionMethod $method): string
    {
        return sprintf(
            '%s(%s)%s',
            implode(
                ' ',
                [
                    $method->isAbstract() ? 'abstract' : '',
                    $method->isFinal() ? 'final' : '',
                    $method->isPrivate() ? 'private' : '',
                    $method->isProtected() ? 'protected' : '',
                    $method->isPublic() ? 'public' : '',
                    $method->isStatic() ? 'static' : '',
                    'function',
                    $method->getName(),
                ]
            ),
            implode(', ', array_map([$this, 'generateParameterFromReflection'], $method->getParameters())),
            $method->hasReturnType()
                ? sprintf(
                ': %s%s',
                $method->getReturnType()->allowsNull() ? '?' : '',
                array_reverse(explode('\\', $method->getReturnType()->getName()))[0]
            )
                : ''
        );
    }
}
