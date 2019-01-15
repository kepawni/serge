<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure;

use Kepawni\Twilted\Basic\ImmutableValue;
use Kepawni\Twilted\EventPayload;

abstract class AbstractEventPayloadBase extends ImmutableValue implements EventPayload
{
}
