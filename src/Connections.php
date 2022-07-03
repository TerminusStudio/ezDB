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
    protected static array $connections;

    /**
     * @param DatabaseConfig $databaseConfig
     * @param string $name The name of the connection. Default connection name is "default"
     * @throws ConnectionException
     */
    public static function addConnection(DatabaseConfig $databaseConfig, string $name = "default"): Connection
    {
        static::$connections[$name] = new Connection($databaseConfig);
        return static::$connections[$name];
    }

    /**
     * Get connection by name
     * @param string $name
     * @return Connection
     * @throws ConnectionException
     */
    public static function connection(string $name = "default"): Connection
    {
        if (!isset(static::$connections[$name])) {
            throw new ConnectionException("Connection $name not found.");
        }

        return static::$connections[$name];
    }
}