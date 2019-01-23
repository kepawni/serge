<?php declare(strict_types=1);
namespace Kepawni\Serge\Model\NamespaceDescriptor;

interface ValueObject
{
    function Address(
        string $countryCode,
        string $postalCode,
        string $city,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $addressLine3 = null
    );

    function LineItem(float $quantity = null, Money $price, string $title = null);

    function Money(float $amount, string $currency);
}
