<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

use Kepawni\Twilted\Basic\ImmutableValue;

/**
 * @property-read string $name
 * @property-read Type $type
 * @method self withName(string $v)
 * @method self withType(Type $v)
 */
class Parameter extends ImmutableValue
{
    public function __construct(string $name, Type $type)
    {
        $this->init('name', $name);
        $this->init('type', $type);
    }

    public function __toString()
    {
        return $this->type->toParam() . '$' . $this->name;
    }
}
