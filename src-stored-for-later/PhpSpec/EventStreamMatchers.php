<?php declare(strict_types=1);
namespace Kepawni\Serge\PhpSpec;

use Kepawni\Twilted\DomainEvent;
use Kepawni\Twilted\EventPayload;
use PhpSpec\Exception\Example\FailureException;

trait EventStreamMatchers
{
    public function getMatchers(): array
    {
        return [
            'iterateWithPayloads' => function ($subject, array $values) {
                if (!is_iterable($subject)) {
                    throw new FailureException('Subject is not iterable.');
                }
                $count = 0;
                foreach ($subject as $index => $item) {
                    $count++;
                    $payloadClass = get_class($item->getPayload());
                    if (!isset($values[$index])) {
                        throw new FailureException(
                            sprintf(
                                'Event at index %d is a %s, but nothing was expected.',
                                $index,
                                $payloadClass
                            )
                        );
                    } elseif (!($values[$index] instanceof EventPayload)) {
                        throw new FailureException(
                            sprintf(
                                'Payload at index %d is a %s, but expected a %s.',
                                $index,
                                get_class($values[$index]),
                                EventPayload::class
                            )
                        );
                    } elseif ($item instanceof DomainEvent) {
                        if ($values[$index] instanceof $payloadClass) {
                            if ($values[$index]->windUp() !== $item->getPayload()->windUp()) {
                                throw new FailureException(
                                    sprintf(
                                        'Event at index %d differs from expected.',
                                        $index
                                    )
                                );
                            }
                        } else {
                            throw new FailureException(
                                sprintf(
                                    'Event at index %d is a %s, but expected a %s.',
                                    $index,
                                    $payloadClass,
                                    get_class($values[$index])
                                )
                            );
                        }
                    } else {
                        throw new FailureException('Subject must contain only DomainEvents.');
                    }
                }
                return $count === count($values);
            },
        ];
    }
}
