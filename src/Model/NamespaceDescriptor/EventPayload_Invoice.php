<?php declare(strict_types=1);
namespace Kepawni\Serge\Model\NamespaceDescriptor;

use Kepawni\Serge\Infrastructure\DateTime;
use Kepawni\Twilted\Basic\AggregateUuid;

interface EventPayload_Invoice
{
    function DueDateWasOverridden(DateTime $dueDate);

    function InvoiceWasOpened(
        AggregateUuid $customerId,
        string $invoiceNumber,
        ?DateTime $invoiceDate = null
    );

    function LineItemWasAppended(int $position, LineItem $item);

    function LineItemWasRemoved(int $position);

    function MistypedInvoiceDateWasCorrected(DateTime $invoiceDate);
}
