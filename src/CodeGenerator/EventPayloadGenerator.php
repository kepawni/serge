<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

/**
 * Generates an EventPayload class file for every method of a descriptor interface. This interface describes a whole
 * namespace of classes.
 *
 * Such a method might look like
 *     function CustomerHasEngagedInBusiness(string $customerName, Address $billingAddress);
 * and will become an immutable CustomerHasEngagedInBusiness class with the properties customerName and billingAddress.
 */
class EventPayloadGenerator extends AbstractClassGenerator
{
    protected $inheritance = ' extends AbstractEventPayloadBase';
    protected $initialUseStatements = [
        'use Kepawni\Serge\Infrastructure\AbstractEventPayloadBase;',
        'use Kepawni\Twilted\Windable;',
    ];
    protected $isMagicMethodDocTagEnabled = false;
    protected $namePrefixPattern = '<^EventPayload_?>';
}
