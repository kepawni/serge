<?php declare(strict_types=1);
namespace Kepawni\Serge\Infrastructure\EventStore\SqLite;

use PDO;

class SqLitePdo extends PDO
{
    /**
     * @param string $filePath The path to the *.sqlite file holding the database.
     */
    public function __construct(string $filePath)
    {
        parent::__construct($this->createDataSourceName($filePath));
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    /**
     * @param string $filePath
     *
     * @return string The DSN.
     */
    private function createDataSourceName(string $filePath): string
    {
        return 'sqlite:' . realpath($filePath);
    }
}
