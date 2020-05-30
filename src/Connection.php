<?php

namespace TS\ezDB;

use TS\ezDB\Drivers\MySQLiDriver;
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Interfaces\DriverInterface;

class Connection
{
    /**
     * @var DatabaseConfig
     */
    protected $databaseConfig;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var bool
     */
    protected $isConnected;

    /**
     * Connection constructor.
     * @param DatabaseConfig $databaseConfig
     * @throws ConnectionException
     */
    public function __construct(DatabaseConfig $databaseConfig)
    {
        $this->databaseConfig = $databaseConfig;

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

        $this->isConnected = false;
    }

    /**
     * Create a connection
     * @throws ConnectionException
     */
    public function connect()
    {
        if ($this->driver->connect() === false) {
            throw new ConnectionException("Database connection could not be established");
        } else {
            $this->isConnected = true;
        }
        return $this;
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

    public function table($tableName)
    {

    }

    public function raw($rawSQL)
    {

    }

    public function select($query)
    {

    }
}