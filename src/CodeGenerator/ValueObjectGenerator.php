<?php declare(strict_types=1);
namespace Kepawni\Serge\CodeGenerator;

/**
 * Generates a ValueObject class file for every method of a descriptor interface. This interface describes a whole
 * namespace of classes.
 *
 * Such a method might look like
 *     function LineItem(float $quantity = null, SubNs\Money $price, string $title = null);
 * and will become an immutable LineItem class with the properties quantity, price and title.
 */
class ValueObjectGenerator extends AbstractClassGenerator
{
    protected $inheritance = ' extends AbstractValueObjectBase';
    protected $initialUseStatements = [
        'use Kepawni\\Serge\\Infrastructure\\AbstractValueObjectBase;',
        'use Kepawni\\Twilted\\Windable;',
    ];
    protected $isMagicMethodDocTagEnabled = true;
    protected $namePrefixPattern = '<^ValueObject_?>';
}
