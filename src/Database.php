<?php

namespace TS\ezDB;

use TS\ezDB\Drivers\MySQLiDriver;
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Interfaces\DriverInterface;

class Database
{
    /**
     * @var DatabaseConfig
     */
    private $databaseConfig;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * Database constructor.
     * @param array $config
     * @throws Exceptions\ConnectionException
     */
    public function __construct(array $config)
    {
        $this->databaseConfig = new DatabaseConfig($config);
        $this->connect();
    }

    /**
     * Create a connection
     * @throws ConnectionException
     */
    private function connect()
    {
        switch ($this->databaseConfig->getDriver()) {
            case "mysql":
            case "MySQL":
            case "mysqli":
            case "MySQLi":
                $this->driver = new MySQLiDriver($this->databaseConfig);
                break;
            case "":
            default:
                throw new ConnectionException("Driver provided is not valid - " . $this->databaseConfig->getDriver());
        }

        if ($this->driver->connect() === false) {
            throw new ConnectionException("Database connection could not be established");
        }
    }

    public function reset()
    {
        return $this->driver->reset();
    }

    public function close()
    {
        return $this->driver->close();
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function getDriverHandle()
    {
        return $this->driver->handle();
    }
}