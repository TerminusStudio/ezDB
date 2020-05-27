<?php

namespace TS\ezDB\Interfaces;

use TS\ezDB\DatabaseConfig;

interface DriverInterface
{
    /**
     * DriverInterface constructor.
     * @param DatabaseConfig $databaseConfig
     */
    public function __construct(DatabaseConfig $databaseConfig);

    /**
     * @return boolean
     */
    public function connect();

    /**
     * @return object
     */
    public function handle();

    /**
     * Close current connection
     * @return boolean
     */
    public function close();

    /**
     * Refresh current connection
     * @return boolean
     */
    public function reset();

    /**
     * Execute query
     * @param string $query
     * @return mixed|boolean|object
     */
    public function query(string $query);

    /**
     * Escape special characters in string
     * @param string $value
     * @return string
     */
    public function escape(string $value);

    //public function showProfiles(int $limit = 0);
}