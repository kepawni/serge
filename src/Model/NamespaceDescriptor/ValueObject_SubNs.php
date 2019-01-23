<?php declare(strict_types=1);
namespace Kepawni\Serge\Model\NamespaceDescriptor;

interface ValueObject_SubNs
{
    function Bag(
        Bool $bool,
        ?Int $int = null,
        ?Float $float,
        String $string = null,
        Array $array,
        Callable $callable,
        LineItem $item,
        Object $object,
        $mixed = PHP_EOL
    );
}
