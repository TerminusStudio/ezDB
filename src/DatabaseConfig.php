<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB;

use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Models\Model;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Query\Processor\PostgresProcessor;
use TS\ezDB\Query\Processor\Processor;

class DatabaseConfig
{
    private $config;

    private $driver;

    private $host;

    private $port;

    private $database;

    private $username;

    private $password;

    private $charset;

    private $collation;

    private $processorClass;

    private $builderClass;

    private $modelClass;

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

        $this->charset = $this->getValue("charset", false, 'utf8');
        $this->collation = $this->getValue("collation", false, 'utf8_unicode_ci');

        $this->processorClass = $this->getValue("processor", false);

        $this->builderClass = $this->getValue("builder", false, Builder::class);
        $this->modelClass = $this->getValue("model", false, Model::class);
    }

    /**
     * A function to easily read the config array.
     * @param $key
     * @param bool $required
     * @param string $default
     * @return string
     * @throws ConnectionException
     */
    protected function getValue($key, $required = false, $default = '')
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
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return ($this->port != "") ? $this->port : null;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * Get processor class that needs to be used.
     * If value is not set in config, it will automatically return a processor based on driver.
     * @return string
     */
    public function getProcessorClass()
    {
        if ($this->processorClass != "") {
            return $this->processorClass;
        }

        switch ($this->driver) {
            case "mysql":
            case "mysqli":
                return MySQLProcessor::class;
            default:
                return Processor::class;
        }
    }

    /**
     * @return string
     */
    public function getBuilderClass()
    {
        return $this->builderClass;
    }

    /**
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }
}