<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure;

use Kepawni\Twilted\Basic\ImmutableValue;
use Kepawni\Twilted\Windable;

abstract class AbstractValueObjectBase extends ImmutableValue implements Windable
{
    abstract public static function fromHashMap(array $mutationArgs): self;
}
