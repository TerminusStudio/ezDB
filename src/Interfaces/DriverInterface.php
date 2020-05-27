<?php

namespace TS\ezDB\Interfaces;

use TS\ezDB\DatabaseConfig;

interface DriverInterface
{
    /**
     * @param DatabaseConfig $databaseConfig
     * @return boolean
     */
    public function connect(DatabaseConfig $databaseConfig);

    /**
     * Get connection handle
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
     * Execute query, and support prepared statements if $params is passed.
     * @param string $query
     * @param array|null $params
     * @return mixed
     */
    public function query(string $query, array $params = null);

    //public function showProfiles(int $limit = 0);
}