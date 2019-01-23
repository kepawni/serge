<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure;

use Kepawni\Twilted\Basic\ImmutableValue;
use Kepawni\Twilted\Foldable;
use Kepawni\Twilted\Windable;

abstract class AbstractValueObjectBase extends ImmutableValue implements Foldable, Windable
{
    const JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param string $leaflet
     *
     * @return static
     */
    public static function unfold(string $leaflet): Foldable
    {
        return static::unwind(json_decode($leaflet));
    }

    public function fold(): string
    {
        return json_encode($this->windUp(), self::JSON_OPTIONS);
    }
}
