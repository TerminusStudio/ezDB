<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB;

use TS\ezDB\Drivers\MySQLiDriver;
use TS\ezDB\Drivers\PDODriver;
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
     * @var bool Enable Query Logger on Connection Level
     */
    protected $enableLogging = false;

    /**
     * @var array Query Logs.
     */
    protected $queryLog = array();

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
            case "pgsql":
                $this->driver = new PDODriver($this->databaseConfig);
                break;
            case "mysqli":
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

    /**
     * Reset current connection
     * @return bool
     */
    public function reset()
    {
        if ($this->isConnected) {
            return $this->driver->reset();
        } else {
            return false;
        }
    }

    /**
     * Close connection
     * @return bool
     */
    public function close()
    {
        if ($this->isConnected) {
            if ($this->driver->close()) {
                $this->isConnected = false;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get the driver instance.
     * @return MySQLiDriver|PDODriver|DriverInterface
     * @throws ConnectionException
     */
    public function getDriver()
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver;
    }

    /**
     * Get the driver handle
     * @return mixed|object
     * @throws ConnectionException
     */
    public function getDriverHandle()
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver->handle();
    }

    /**
     * Get the specified builder class in config.
     * @return string
     */
    public function getBuilderClass()
    {
        return $this->databaseConfig->getBuilderClass();
    }

    /**
     * Check if the connection is already open.
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * Enable query logging.
     */
    public function enableQueryLog()
    {
        $this->enableLogging = true;
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLog()
    {
        $this->enableLogging = false;
    }

    /**
     * Get query log
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Flush query log.
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    public function table($tableName)
    {

    }

    /**
     * Execute a raw query in the database.
     * @param $rawSQL
     * @return array|bool|int|mixed|object
     * @throws ConnectionException
     * @throws Exceptions\DriverException|QueryException
     */
    public function raw($rawSQL)
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        return $this->executeQuery($rawSQL, [], function () use ($rawSQL) {
            return $this->getDriver()->query($rawSQL);
        });
    }

    public function insert($query, ...$params)
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        return $this->executeQuery($query, $params, function () use ($query, $params) {
            $stmt = $this->getDriver()->prepare($query);
            if (!empty($params)) {
                $this->getDriver()->bind($stmt, ...$params);
            }
            return $this->getDriver()->execute($stmt, true, false);
        });
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
        return $this->executeQuery($query, $params, function () use ($query, $params) {
            $stmt = $this->getDriver()->prepare($query);
            if (!empty($params)) {
                $this->getDriver()->bind($stmt, ...$params);
            }
            return $this->getDriver()->execute($stmt, true, true);
        });
    }

    public function delete($query, ...$params)
    {
        $r = $this->insert($query, ...$params);

        return $r;
    }

    /**
     * Execute callback while measuring time in milliseconds for logging.
     * @param string $query
     * @param array $bindings
     * @param \Closure $callback A closure containing the queries to execute.
     * @return mixed
     */
    protected function executeQuery($query, $bindings, $callback)
    {
        if ($this->enableLogging) { //If logging is enabled, track elapsed time.
            $start = microtime(true);
            $result = $callback($this);
            if ($this->enableLogging) {
                $this->queryLog[] = [
                    'query' => $query,
                    'bindings' => $bindings,
                    'time' => round((microtime(true) - $start) * 1000, 2) //milliseconds
                ];
            }
            return $result;
        } else { //If not just execute and return the closure.
            return $callback($this);
        }
    }
}