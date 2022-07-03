<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB;

use TS\ezDB\Connections\Builder\ConnectionAwareBuilder;
use TS\ezDB\Drivers\IDriver;
use TS\ezDB\Drivers\MySqlIDriver;
use TS\ezDB\Drivers\PdoDriver;
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Exceptions\DriverException;
use TS\ezDB\Query\Processor\IProcessor;
use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Query\Processor\PostgresProcessor;

class DatabaseConfig
{
    private array $config;

    private string $driver;

    private string $host;

    private ?string $port;

    private string $database;

    private string $username;

    private string $password;

    private string $charset;

    private string $collation;

    private IProcessor $processorInstance;

    private IDriver $driverInstance;

    private string $builderClass;

    /**
     * DatabaseConfig constructor.
     * @param array $config
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = strtolower($this->getValue('driver', true));
        $this->host = $this->getValue('host', true);
        $this->port = $this->getValue('port');
        $this->database = $this->getValue('database', true);
        $this->username = $this->getValue('username', true);
        $this->password = $this->getValue('password', true);

        //TODO: Load default charset and collation based on driver.
        $this->charset = $this->getValue('charset', false, 'utf8mb4');
        $this->collation = $this->getValue('collation', false, 'utf8mb4_unicode_ci');

        $this->processorInstance = $this->loadProcessor($this->getValue('processor'));

        $this->driverInstance = $this->loadDriver($this->getValue('driverInstance'));

        $this->builderClass = $this->getValue("builder", false, ConnectionAwareBuilder::class);
    }

    /**
     * A function to easily read the config array.
     * @param string|int $key
     * @param bool $required
     * @param mixed $default
     * @return mixed
     * @throws ConnectionException
     */
    protected function getValue(string|int $key, bool $required = false, mixed $default = null): mixed
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } elseif ($required === false) {
            return $default;
        } else {
            throw new ConnectionException("Config $key is required but is not provided.");
        }
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string|null
     */
    public function getPort(): ?string
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getCollation(): string
    {
        return $this->collation;
    }

    /**
     * Get processor instance
     * @return IProcessor
     */
    public function getProcessor(): IProcessor
    {
        return $this->processorInstance;
    }

    /**
     * Get the driver instance
     * @return IDriver
     */
    public function getDriver(): IDriver
    {
        return $this->driverInstance;
    }

    /**
     * @return string
     */
    public function getBuilderClass(): string
    {
        return $this->builderClass;
    }


    protected function loadProcessor(mixed $processorValue): IProcessor
    {
        if ($processorValue instanceof IProcessor) {
            return $processorValue;
        }

        if ($processorValue instanceof \Closure) {
            return $processorValue();
        }

        if (is_string($processorValue)) {
            $processor = new $processorValue();
            if ($processor instanceof IProcessor) {
                return $processor;
            }
        }

        if ($processorValue == null || $processorValue == "") {
            return match ($this->driver) {
                "pgsql" => new PostgresProcessor(),
                default => new MySQLProcessor(),
            };
        }
        throw new ConnectionException("provided processor class is of unknown type.");
    }

    protected function loadDriver(mixed $driverValue): IDriver
    {
        if ($driverValue instanceof IDriver) {
            return $driverValue;
        }

        if ($driverValue instanceof \Closure) {
            return $driverValue();
        }

        if (is_string($driverValue)) {
            $driver = new $driverValue();
            if ($driver instanceof IDriver) {
                return $driver;
            }
        }

        return match ($this->driver) {
            "mysqli" => new MySqlIDriver($this, $this->processorInstance),
            "pgsql", "mysql" => new PdoDriver($this, $this->processorInstance),
            default => throw new DriverException("Provider driver name is not valid - " . $this->getDriverName())
        };
    }
}