<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class Parameter
{
    private $name;
    private $type;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function __toString()
    {
        return $this->getType()->toParam() . '$' . $this->getName();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }
}
