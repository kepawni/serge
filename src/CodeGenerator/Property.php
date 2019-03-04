<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class Property
{
    /** @var string */
    private $name;
    private $static;
    private $type;
    private $usedClasses;
    private $visibility;

    public function __construct(string $name, ?Type $type)
    {
        $this->name = $name;
        $this->type = $type ?: new Type(Type::MIXED);
        $this->static = false;
        $this->usedClasses = [];
        $this->visibility = 'public';
    }

    public function __toString(): string
    {
        return sprintf(
            "/** @var %s */\n%s%s \$%s",
            $this->type->toDocReturn(),
            $this->visibility,
            $this->isStatic() ? 'static ' : '',
            $this->getName()
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getUsedClasses(): array
    {
        return $this->usedClasses;
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

    public function makeStatic(bool $value = true): self
    {
        $this->static = $value;
        return $this;
    }

    public function setType(Type $type): self
    {
        $this->type = $type;
        if ($type->getNamespace() !== null) {
            $this->useClass($type->getFullName());
        }
        return $this;
    }

    public function useClass(string $fullyQualifiedClassName): self
    {
        $this->usedClasses[] = '\\' . trim($fullyQualifiedClassName, '\\');
        return $this;
    }
}
