<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure;

use DateTimeImmutable;
use Exception;
use Kepawni\Twilted\Foldable;

class DateTime extends DateTimeImmutable implements Foldable
{
    /**
     * @param string $leaflet The compact representation of this instance.
     *
     * @return static The instance reconstituted from unfolding the leaflet.
     * @throws Exception
     */
    public static function unfold(string $leaflet): Foldable
    {
        list($time, $zone) = explode('@', $leaflet, 2);
        return new self($time, new \DateTimeZone($zone ?: 'UTC'));
    }

    /**
     * @return string A compact representation (like a leaflet) of this instance.
     */
    public function fold(): string
    {
        return sprintf('%s@%s', $this->format('Ymd\\THis'), $this->getTimezone()->getName());
    }
}
