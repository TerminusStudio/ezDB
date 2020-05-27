<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace TS\ezDB\Drivers;

use mysqli;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Exceptions\Exception;
use TS\ezDB\Interfaces\DriverInterface;

class MySQLiDriver implements DriverInterface
{
    /**
     * @var mysqli
     */
    private $handle;

    /**
     * @var DatabaseConfig
     */
    private $databaseConfig;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseConfig $databaseConfig)
    {
        $this->databaseConfig = $databaseConfig;
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        $this->handle = new mysqli(
            $this->databaseConfig->getHost(),
            $this->databaseConfig->getUsername(),
            $this->databaseConfig->getPassword(),
            $this->databaseConfig->getDatabase()
        );

        if ($this->handle->connect_errno) {
            return false;
        }

        return true;
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
        return $this->handle->close();
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->handle = null;
        return $this->connect();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function query(string $query)
    {
        try {
            $result = $this->handle->query($query);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }


        if (is_bool($result)) {
            return $result;
        } elseif ($result instanceof \mysqli_result) {
            $fetchedResult = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $fetchedResult;
        }
        throw new Exception("Error executing query.");
    }

    /**
     * @inheritDoc
     */
    public function escape(string $value)
    {
        return $this->handle->real_escape_string($value);
    }


}