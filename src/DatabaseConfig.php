<?php

namespace TS\ezDB;

use TS\ezDB\Exceptions\ConnectionException;
use TS\ezDB\Query\Builder;

class DatabaseConfig
{
    private $config;

    private $driver;

    private $host;

    private $port;

    private $database;

    private $username;

    private $password;

    private $builderClass;

    /**
     * DatabaseConfig constructor.
     * @param array $config
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = $this->getValue("driver", true);
        $this->host = $this->getValue("host", true);
        $this->port = $this->getValue("port");
        $this->database = $this->getValue("database", true);
        $this->username = $this->getValue("username", true);
        $this->password = $this->getValue("password", true);

        $this->builderClass = $this->getValue("builder", false, Builder::class);
    }

    /**
     * A function to easily read the config array.
     * @param $key
     * @param bool $required
     * @param string $default
     * @return string
     * @throws ConnectionException
     */
    private function getValue($key, $required = false, $default = "")
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
        return $this->host . (($this->port != "") ? ":" . $this->port : "");
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
    public function getBuilderClass()
    {
        return $this->builderClass;
    }

    public function __get($key)
    {
        return $this->$key;
    }
}