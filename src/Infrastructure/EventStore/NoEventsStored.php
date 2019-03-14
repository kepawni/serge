<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\EventStore;

use RuntimeException;

/** An event store has no events that belong to a given aggregate ID. */
class NoEventsStored extends RuntimeException
{
}
