<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\EventStore\SqLite;

use DateTimeImmutable;
use Kepawni\Serge\Infrastructure\EventStore\NoEventsStored;
use Kepawni\Twilted\Basic\AggregateUuid;
use Kepawni\Twilted\Basic\SimpleDomainEvent;
use Kepawni\Twilted\Basic\SimpleEventStream;
use Kepawni\Twilted\DomainEvent;
use Kepawni\Twilted\DomainEvent as RecordedEvent;
use Kepawni\Twilted\EntityIdentifier;
use Kepawni\Twilted\EventPayload;
use Kepawni\Twilted\EventStore;
use Kepawni\Twilted\EventStream;
use PDO;
use PDOStatement;
use stdClass;

class SqLiteEventStore implements EventStore
{
    const EXPRESSION = '';
    const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    const QUERY_COLUMN = '';
    const RESULT_COLUMN = '';
    const TABLENAME = '';
    private $connection;
    /** @var SqLitePdo */
    private $database;
    private $dbStatement;
    private $dbWriteStatement;
    private $omitEventNamespacePrefix;

    public function __construct(SqLitePdo $database, string $omitEventNamespacePrefix = '')
    {
        $this->connection = $database;
        $this->dbStatement = $this->createDbStatement($this->connection);
        $this->dbWriteStatement = $this->createDbWriteStatement($this->connection);
        $this->database = $database;
        $this->omitEventNamespacePrefix = $omitEventNamespacePrefix;
    }

    public function append(EventStream $recordedEvents): void
    {
        array_map([$this, 'writeEvent'], iterator_to_array($recordedEvents));
    }

    public function retrieve(EntityIdentifier $id): EventStream
    {
        $dbResults = $this->getEventsFromStatement($this->dbStatement, $id);
        $this->guardAtLeastOneEvent($dbResults);
        return new SimpleEventStream(array_map([$this, 'restoreEventFromRecord'], $dbResults));
    }

    private function createDbStatement(PDO $connection)
    {
        $sql = <<<'SQL'
SELECT
  event_type,
  aggregate_id_string,
  date_string,
  serialized_event_data
FROM event_table
WHERE aggregate_id_string = :aggregate_id_string
SQL;
        $statement = $connection->prepare($sql);
        return $statement;
    }

    private function createDbWriteStatement(PDO $connection)
    {
        $sql = <<<SQL
INSERT INTO event_table (
  event_type,
  aggregate_id_string,
  date_string,
  serialized_event_data
)
VALUES (
  :eventType,
  :aggregateIdString,
  :dateString,
  :serializedEventData
);
SQL;
        $statement = $connection->prepare($sql);
        return $statement;
    }

    private function getEventsFromStatement(PDOStatement $statement, EntityIdentifier $aggregateId): array
    {
        $statement->execute([':aggregate_id_string' => $aggregateId->fold()]);
        return $statement->fetchAll();
    }

    private function guardAtLeastOneEvent(array $dbResults): void
    {
        if (empty($dbResults)) {
            throw new NoEventsStored();
        }
    }

    private function restoreEventFromRecord(stdClass $record): RecordedEvent
    {
        /** @var EventPayload $eventType */
        $eventType = $this->omitEventNamespacePrefix . $record->event_type;
        $idString = $record->aggregate_id_string;
        $dateString = $record->date_string;
        $serializedEventData = json_decode($record->serialized_event_data);
        return new SimpleDomainEvent(
            $eventType::unwind($serializedEventData),
            AggregateUuid::unfold($idString),
            new DateTimeImmutable($dateString)
        );
    }

    private function writeEvent(DomainEvent $recordedEvent): void
    {
        $eventType = preg_replace(
            sprintf('/^%s/', str_replace('\\', '\\\\', $this->omitEventNamespacePrefix)),
            '',
            $recordedEvent->getPayload()
        );
        $aggregateIdString = $recordedEvent->getId()->fold();
        $dateString = $recordedEvent->getRecordedOn()->format(DATE_ATOM);
        $serializedEventData = $recordedEvent->getPayload()->windUp();
        $this->dbWriteStatement->execute(
            [
                ':eventType' => $eventType,
                ':aggregateIdString' => $aggregateIdString,
                ':dateString' => $dateString,
                ':serializedEventData' => json_encode($serializedEventData, self::JSON_FLAGS),
            ]
        );
    }
}
