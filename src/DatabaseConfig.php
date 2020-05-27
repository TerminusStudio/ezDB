<?php

namespace TS\ezDB;

use TS\ezDB\Exceptions\ConnectionException;

class DatabaseConfig
{
    private $config;

    private $host;

    private $port;

    private $database;

    private $username;

    private $password;

    /**
     * DatabaseConfig constructor.
     * @param array $config
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->host = $this->getValue("host", true);
        $this->port = $this->getValue("port");
        $this->database = $this->getValue("database", true);
        $this->username = $this->getValue("username", true);
        $this->password = $this->getValue("password", true);
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
}