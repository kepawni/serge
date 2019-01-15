<?php declare(strict_types=1);
namespace Kepawni\Serge\Model\NamespaceDescriptor;

interface EventPayload_Customer
{
    function CustomerAddressWasChanged(Address $newAddress);

    function CustomerHasEngagedInBusiness(string $customerName, Address $billingAddress);

    function CustomerNameWasChanged(string $newName);
}
