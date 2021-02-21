<?php

namespace TS\ezDB\Interfaces;

use TS\ezDB\DatabaseConfig;
use TS\ezDB\Query\Processor;

interface DriverInterface
{
    /**
     * DriverInterface constructor.
     * @param DatabaseConfig $databaseConfig
     */
    public function __construct(DatabaseConfig $databaseConfig);

    /**
     * Connect to the database.
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
     * SQL Prepare Statement
     * @param string $query
     * @return mixed statement object
     */
    public function prepare(string $query);

    /**
     * Bind Parameters
     * @param $stmt
     * @param mixed ...$params
     * @return mixed
     */
    public function bind($stmt, &...$params);

    /**
     *  Execute prepared statement
     * @param object $stmt
     * @param bool $close
     * @param bool $fetch
     * @return mixed
     */
    public function execute($stmt, $close = true, $fetch = false);

    /**
     * Execute query
     * @param string $query
     * @return mixed|boolean|object
     */
    public function query(string $query);

    /**
     * Execute raw SQL including multiline. Avoid using this if possible.
     *
     * @param string $sql
     * @return mixed
     */
    public function exec(string $sql);

    /**
     * Escape special characters in string
     * @param string $value
     * @return string
     */
    public function escape(string $value);

    /**
     * Get the last insert id
     * @return mixed
     */
    public function getLastInsertId();

    /**
     * Get the number of affected rows
     * @return mixed
     */
    public function getRowCount();

    /**
     * Get the processor object to process builder queries
     * @return Processor
     */
    public function getProcessor();
}