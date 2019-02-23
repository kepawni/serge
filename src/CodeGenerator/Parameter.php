<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

class Parameter
{
    const DT_CONSTANT = 2;
    const DT_NONE = 0;
    const DT_SCALAR = 1;
    private $defaultType = self::DT_NONE;
    private $defaultValue = null;
    private $name;
    private $type;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function __toString()
    {
        return sprintf(
            '%s$%s%s%s%s',
            $this->getType()->toParam(),
            $this->getName(),
            $this->defaultType !== self::DT_NONE ? ' = ' : '',
            $this->defaultType === self::DT_SCALAR ? json_encode($this->defaultValue) : '',
            $this->defaultType === self::DT_CONSTANT ? $this->defaultValue : ''
        );
    }

    public function dropDefaultValue(): self
    {
        $this->defaultValue = null;
        $this->defaultType = self::DT_NONE;
        return $this;
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

    public function specifyDefaultConstant(string $constantName): self
    {
        $this->defaultValue = $constantName;
        $this->defaultType = self::DT_CONSTANT;
        return $this;
    }

    public function specifyDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->defaultType = self::DT_SCALAR;
        return $this;
    }
}
