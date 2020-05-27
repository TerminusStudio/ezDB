<?php

namespace TS\ezDB\Drivers;

use TS\ezDB\DatabaseConfig;
use TS\ezDB\Interfaces\DriverInterface;

class MySQLi implements DriverInterface
{

    /**
     * @inheritDoc
     */
    public function connect(DatabaseConfig $databaseConfig)
    {
        // TODO: Implement connect() method.
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        // TODO: Implement handle() method.
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        // TODO: Implement reset() method.
    }

    /**
     * @inheritDoc
     */
    public function query(string $query, array $params = null)
    {
        // TODO: Implement query() method.
    }
}