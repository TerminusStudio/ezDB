<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Exceptions\QueryException;

/**
 * This class provides the support to execute partial raw statements using Builder or
 * to execute any statement directly on a connection.
 *
 * Class Raw
 * @package TS\ezDB\Query
 */
class Raw
{
    /**
     * @var string The sql statement.
     */
    protected $sql;

    /**
     * (optional) Connection to execute statement
     * @var Connection
     */
    protected $connection;

    /**
     * Raw constructor.
     *
     * @param string $sql
     * @param Connection $connection (optional) Connection to execute SQL.
     */
    public function __construct(string $sql, Connection $connection = null)
    {
        $this->sql = $sql;
        $this->connection = $connection;
    }

    /**
     * Set the connection.
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get the connection.
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @return array|bool|int|mixed|object
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException|DriverException
     */
    public function execute()
    {
        if ($this->connection == null) {
            throw new QueryException('Connection not set.');
        }
        return $this->connection->raw($this->getSQL());
    }
}