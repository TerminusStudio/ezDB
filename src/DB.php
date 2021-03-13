<?php

namespace TS\ezDB;

use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Raw;

/**
 * Helper class
 *
 * Class DB
 * @package TS\ezDB
 */
class DB
{
    /**
     * Return builder instance with table set.
     *
     * @param string $table The database table to query.
     * @param Connection|string|null $connection
     * @return Builder
     * @throws Exceptions\ConnectionException
     */
    public static function table($table, $connection = null)
    {
        $connection = self::getConnection($connection);
        $builderClass = $connection->getBuilderClass();
        return (new $builderClass($connection))->from($table);
    }

    /**
     * Execute statement on database.
     *
     * @param string $sql SQL statement to execute.
     * @param Connection|string|null $connection
     * @return array|bool|int|mixed|object
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\DriverException
     */
    public static function statement($sql, $connection = null)
    {
        $connection = self::getConnection($connection);
        return $connection->raw($sql);
    }

    /**
     * Fetch results from database.
     *
     * @param string $sql Prepared SQL statement
     * @param array $params Array containing values to bind
     * @param Connection|string|null $connection
     * @return array|bool|int|mixed|object
     * @throws Exceptions\ConnectionException
     */
    public static function select($sql, $params = [], $connection = null)
    {
        $connection = self::getConnection($connection);
        return $connection->select($sql, ...$params);
    }

    /**
     * Return a Raw instance for given sql.
     *
     * @param string $sql The raw SQL statement
     * @param Connection|null $connection
     * @return Raw
     */
    public static function raw($sql, $connection = null)
    {
        return new Raw($sql, $connection);
    }

    /**
     * @param Connection|string|null $connection Connection Instance, Connection name, or leave empty for default
     * @return Connection
     * @throws Exceptions\ConnectionException
     */
    public static function getConnection($connection = null)
    {
        if ($connection instanceof Connection) {
            return $connection;
        } elseif ($connection == null) {
            return Connections::connection();
        } else {
            return Connections::connection($connection); //assume $connection contains connection name.
        }
    }
}