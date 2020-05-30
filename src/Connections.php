<?php

namespace TS\ezDB;

use TS\ezDB\Exceptions\ConnectionException;

/**
 * Class Connections
 * @package TS\ezDB
 *
 * This class manages all active connections and provides a static way to access.
 * Usage of this class is optional if you are not using models.
 */
class Connections
{
    /**
     * @var Connection[] A list of all active connections
     */
    protected static $connections;

    /**
     * @param DatabaseConfig $databaseConfig
     * @param string $name The name of the connection. Default connection name is "default"
     * @throws ConnectionException
     */
    public static function addConnection(DatabaseConfig $databaseConfig, string $name = "default")
    {
        self::$connections[$name] = new Connection($databaseConfig);
    }

    /**
     * @param string $name
     * @return Connection
     * @throws ConnectionException
     */
    public static function connection(string $name = "default")
    {
        if (!isset(self::$connections[$name])) {
            throw new ConnectionException("Connection $name not found.");
        }

        return self::$connections[$name];
    }
}