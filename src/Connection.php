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
        if ($this->isConnected) {
            return $this->driver->reset();
        } else {
            return false;
        }
    }

    public function close()
    {
        if ($this->isConnected) {
            return $this->driver->close();
        }
    }

    public function getDriver()
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver;
    }

    public function getDriverHandle()
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver->handle();
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    public function table($tableName)
    {

    }

    public function raw($rawSQL)
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        return $this->getDriver()->query($rawSQL);
    }

    public function insert($query, ...$params)
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        $stmt = $this->getDriver()->prepare($query);

        if (!empty($params)) {
            $this->getDriver()->bind($stmt, ...$params);
        }

        return $this->getDriver()->execute($stmt, true, false);
    }

    public function update($query, ...$params)
    {
        return $this->insert($query, ...$params);
    }

    public function select($query, ...$params)
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        $stmt = $this->getDriver()->prepare($query);
        if (!empty($params)) {
            $this->getDriver()->bind($stmt, ...$params);
        }
        return $this->getDriver()->execute($stmt, true, true);
    }
}