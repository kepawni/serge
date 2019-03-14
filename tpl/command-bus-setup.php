<?php declare(strict_types=1);
//62e4ff8f-5d04-4a70-a3f7-fadcc0605c27

function addCommandHandlersToCommandBus(CqrsCommandBus $commandBus): void
{
    $eventStore = new MyInMemoryEventStore();
    $eventBus = new MyNoOpEventBus();
    //e8742818-5e5f-41c8-acbe-4cfed1d2b07e
    // call append to add all your command handlers
    //f9ec0699-6d69-4937-9642-ba1b67416808
}

class MyInMemoryEventStore implements EventStore
{
    private $events = [];

    public function append(EventStream $events): void
    {
        /** @var DomainEvent $recordedEvent */
        foreach ($events as $recordedEvent) {
            $eventType = get_class($recordedEvent->getPayload());
            $aggregateIdString = $recordedEvent->getId()->fold();
            $dateString = $recordedEvent->getRecordedOn()->format(DATE_ATOM);
            $serializedEventData = $recordedEvent->getPayload()->windUp();
            // naively store the details in an array
            $this->events[$aggregateIdString][] = [$eventType, $dateString, $serializedEventData];
        }
    }

    public function retrieve(EntityIdentifier $id): EventStream
    {
        /** @var DomainEvent[] $domainEvents */
        $domainEvents = [];
        // load the domain events from somewhere
        /** @var EventPayload $eventType */
        foreach ($this->events[$id->fold()] as [$eventType, $dateString, $serializedEventData]) {
            $domainEvents[] = new SimpleDomainEvent(
                $eventType::unwind($serializedEventData),
                $id,
                new DateTimeImmutable($dateString)
            );
        }
        return new SimpleEventStream($domainEvents);
    }
}

class MyNoOpEventBus implements EventBus
{
    function dispatch(EventStream $events): void
    {
        /** @var DomainEvent $domainEvent */
        foreach ($events as $domainEvent) {
            // do something with the $domainEvent
            // e. g. hand it off to a projector
        }
    }
}
