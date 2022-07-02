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
use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Models\Model;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Processor\IProcessor;
use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Query\Processor\PostgresProcessor;
use TS\ezDB\Query\Processor\Processor;

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

    private string $builderClass;

    /**
     * DatabaseConfig constructor.
     * @param array $config
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = strtolower($this->getValue("driver", true));
        $this->host = $this->getValue("host", true);
        $this->port = $this->getValue("port");
        $this->database = $this->getValue("database", true);
        $this->username = $this->getValue("username", true);
        $this->password = $this->getValue("password", true);

        //TODO: Load default charset and collation based on driver.
        $this->charset = $this->getValue("charset", false, 'utf8mb4');
        $this->collation = $this->getValue("collation", false, 'utf8mb4_unicode_ci');

        $this->processorInstance = $this->loadProcessor($this->getValue("processor", false));

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
     * @return string
     */
    public function getBuilderClass(): string
    {
        return $this->builderClass;
    }


    protected function loadProcessor(mixed $processorValue): IProcessor
    {
        if ($processorValue == "") {
            return match ($this->driver) {
                "pgsql" => new PostgresProcessor(),
                default => new MySQLProcessor(),
            };
        }

        $processor = new $processorValue();
        if ($processor instanceof IProcessor) {
            return $processor;
        }

        throw new ConnectionException("provided processor class is of unknown type.");
    }
}