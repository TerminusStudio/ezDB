<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Drivers;

use TS\ezDB\DatabaseConfig;
use TS\ezDB\Query\Processor\IProcessor;

interface IDriver
{
    /**
     * DriverInterface constructor.
     * @param DatabaseConfig $databaseConfig
     * @param IProcessor $processor The processor instance to use
     */
    public function __construct(DatabaseConfig $databaseConfig, IProcessor $processor);

    /**
     * Connect to the database.
     * @return boolean
     */
    public function connect(): bool;

    /**
     * @return object
     */
    public function handle(): object;

    /**
     * Close current connection
     * @return boolean
     */
    public function close(): bool;

    /**
     * Refresh current connection
     * @return boolean
     */
    public function reset(): bool;

    /**
     * SQL Prepare Statement
     * @param string $query
     * @return mixed statement object
     */
    public function prepare(string $query): object;

    /**
     * Bind Parameters
     * @param $stmt
     * @param mixed ...$params
     * @return mixed
     */
    public function bind($stmt, &...$params): object;

    /**
     *  Execute prepared statement
     * @param object $stmt
     * @param bool $close
     * @param bool $fetch
     * @return mixed
     */
    public function execute(object $stmt, bool $close = true, bool $fetch = false): mixed;

    /**
     * Execute query
     * @param string $query
     * @return mixed|boolean|object
     */
    public function query(string $query): mixed;

    /**
     * Execute raw SQL including multiline. Avoid using this if possible.
     *
     * @param string $sql
     * @return mixed
     */
    public function exec(string $sql): mixed;

    /**
     * Escape special characters in string
     * @param string $value
     * @return string
     */
    public function escape(string $value): string;

    /**
     * Get the last insert id
     * @return mixed
     */
    public function getLastInsertId(): mixed;

    /**
     * Get the processor object to process builder queries
     * @return IProcessor
     */
    public function getProcessor(): IProcessor;
}