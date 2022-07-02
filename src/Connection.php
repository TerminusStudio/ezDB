<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB;

use ReflectionClass;
use TS\ezDB\Connections\Builder\ConnectionAwareBuilder;
use TS\ezDB\Connections\Builder\IConnectionAwareBuilder;
use TS\ezDB\Drivers\IDriver;
use TS\ezDB\Drivers\MySqlIDriver;
use TS\ezDB\Drivers\PdoDriver;
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Exceptions\QueryException;

class Connection
{
    /**
     * @var DatabaseConfig
     */
    protected DatabaseConfig $databaseConfig;

    /**
     * @var IDriver
     */
    protected IDriver $driver;

    /**
     * @var bool
     */
    protected bool $isConnected;

    /**
     * @var bool Enable Query Logger on Connection Level
     */
    protected bool $enableLogging = false;

    /**
     * @var array Query Logs.
     */
    protected array $queryLog = array();

    /**
     * Connection constructor.
     * @param DatabaseConfig $databaseConfig
     */
    public function __construct(DatabaseConfig $databaseConfig)
    {
        $this->databaseConfig = $databaseConfig;
        $this->driver = $this->databaseConfig->getDriver();
        $this->isConnected = false;
    }

    /**
     * Create a connection
     * @throws ConnectionException
     */
    public function connect(): bool
    {
        if ($this->driver->connect() === false) {
            throw new ConnectionException("Database connection could not be established");
        } else {
            $this->isConnected = true;
        }
        return true;
    }

    /**
     * Reset current connection
     * @return bool
     */
    public function reset(): bool
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
    public function close(): bool
    {
        if ($this->isConnected) {
            if ($this->driver->close()) {
                $this->isConnected = false;
                return true;
            }
        }
        return false;
    }

    /**
     * Get the driver instance.
     * @return MySqlIDriver|PdoDriver|IDriver
     * @throws ConnectionException
     */
    public function getDriver(): IDriver
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver;
    }

    /**
     * Get the driver handle
     * @return object
     * @throws ConnectionException
     */
    public function getDriverHandle(): object
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->driver->handle();
    }

    /**
     * @return IConnectionAwareBuilder
     * @throws QueryException
     * @throws \ReflectionException
     */
    public function getNewBuilder(): IConnectionAwareBuilder
    {
        //TODO: use closure, or default class instead of ReflectionClass
        return new ConnectionAwareBuilder($this);
        $builderClass = new ReflectionClass($this->databaseConfig->getBuilderClass());
        if (!$builderClass->implementsInterface(IConnectionAwareBuilder::class)) {
            throw new QueryException('Provided builder type is not supported. Make sure builder implements IConnectionAwareBuilder interface');
        }
        /**
         * @var IConnectionAwareBuilder $builder
         */
        $builder = $builderClass->newInstanceArgs([$this]);
        return $builder;
    }

    /**
     * Check if the connection is already open.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Enable query logging.
     */
    public function enableQueryLog(): void
    {
        $this->enableLogging = true;
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLog(): void
    {
        $this->enableLogging = false;
    }

    /**
     * Get query log
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Flush query log.
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Execute a raw query in the database.
     * @param $rawSQL
     * @return array|bool|int|mixed|object
     * @throws ConnectionException
     * @throws Exceptions\DriverException|QueryException
     */
    public function raw($rawSQL): mixed
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        return $this->executeQuery($rawSQL, [], function () use ($rawSQL) {
            return $this->getDriver()->query($rawSQL);
        });
    }

    public function insert($query, ...$params): mixed
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

    public function update($query, ...$params): mixed
    {
        return $this->insert($query, ...$params);
    }

    public function select($query, ...$params): mixed
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

    public function delete($query, ...$params): mixed
    {
        return $this->insert($query, ...$params);
    }

    /**
     * Execute callback while measuring time in milliseconds for logging.
     * @param string $query
     * @param array $bindings
     * @param \Closure $callback A closure containing the queries to execute.
     * @return mixed
     */
    protected function executeQuery($query, $bindings, $callback): mixed
    {
        if ($this->enableLogging) { //If logging is enabled, track elapsed time.
            $start = microtime(true);
            try {
                $result = $callback($this);
            } finally {
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