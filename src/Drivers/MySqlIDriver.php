<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

/** @noinspection PhpComposerExtensionStubsInspection */

namespace TS\ezDB\Drivers;

use mysqli;
use mysqli_sql_exception;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Exceptions\DriverException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Processor\IProcessor;

class MySqlIDriver implements IDriver
{

    /**
     * @var ?mysqli
     */
    protected ?mysqli $handle;

    /**
     * @var DatabaseConfig
     */
    protected DatabaseConfig $databaseConfig;

    /**
     * @var \TS\ezDB\Query\Processor\IProcessor
     */
    protected IProcessor $processor;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseConfig $databaseConfig, IProcessor $processor)
    {
        $this->databaseConfig = $databaseConfig;
        $this->processor = $processor;
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); //Report errors

        try {
            $this->handle = new mysqli(
                $this->databaseConfig->getHost(),
                $this->databaseConfig->getUsername(),
                $this->databaseConfig->getPassword(),
                $this->databaseConfig->getDatabase(),
                port: $this->databaseConfig->getPort()
            );
        } catch (mysqli_sql_exception $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        if ($this->handle->connect_errno) {
            return false;
        }

        $this->handle->set_charset($this->databaseConfig->getCharset());
        $this->handle->query(sprintf("SET collation_connection=%s", $this->databaseConfig->getCollation()));

        return true;
    }

    /**
     * @inheritDoc
     * @return mysqli
     */
    public function handle(): mysqli
    {
        if ($this->handle == null) {
            throw new DriverException('Driver Handle not found. Make sure you call connect() first.');
        }
        return $this->handle;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return $this->handle->close();
    }

    /**
     * @inheritDoc
     */
    public function reset(): bool
    {
        $this->handle = null;
        return $this->connect();
    }

    /**
     * @inheritDoc
     * @param string $query
     * @return false|\mysqli_stmt
     * @throws QueryException
     */
    public function prepare(string $query): \mysqli_stmt
    {
        $stmt = $this->handle->prepare($query);
        if ($stmt === false) {
            throw new QueryException("Error trying to prepare statement - " . $this->handle->error);
        }
        return $stmt;
    }

    /**
     * @inheritDoc
     * @param \mysqli_stmt $stmt
     */
    public function bind($stmt, &...$params): \mysqli_stmt
    {
        $type = str_repeat('s', count($params));
        $stmt->bind_param($type, ...$params);
        return $stmt;
    }

    /**
     * @inheritDoc
     * @param \mysqli_stmt $stmt
     * @param bool $close Close Connection
     * @param bool $fetch Fetch Results
     * @throws QueryException
     */
    public function execute(object $stmt, bool $close = true, bool $fetch = false): int|bool|array
    {
        try {
            $result = $stmt->execute();
            if ($fetch) {
                $result = $stmt->get_result();
            } else {
                $result = $stmt->affected_rows;
            }

            if ($close) {
                $stmt->close();
            }
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this->getResults($result);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function query(string $query): int|bool|array
    {
        try {
            $result = $this->handle->query($query);
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this->getResults($result);
    }

    /**
     * MySQLi uses multi query to execute raw statement. Then uses a while loop to make sure there is no errors.
     * Avoid using this.
     *
     * This returns an array containing result for each separate query. Check the size of array to make sure all queries
     * executed without errors.
     *
     * @inheritDoc
     */
    public function exec(string $sql): array
    {
        try {
            $this->handle->multi_query($sql);
            $result = [];
            do {
                if ($this->handle->errno !== 0) {
                    $result[] = false;
                } else {
                    $result[] = $this->getResults($this->handle->store_result());
                }

            } while ($this->handle->more_results() && $this->handle->next_result());
        } catch (\Exception $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
        return $result;
    }


    /**
     * @param bool|\mysqli_result $result
     * @return array|bool|int
     * @throws QueryException
     */
    protected function getResults(int|bool|\mysqli_result $result): array|bool|int
    {
        if (is_bool($result)) {
            return $result;
        } elseif ($result instanceof \mysqli_result) {
            $fetchedResult = [];
            while ($obj = $result->fetch_object()) {
                $fetchedResult[] = $obj;
            }
            $result->free();
            return $fetchedResult;
        } elseif (is_int($result)) {
            return $result;
        }
        throw new QueryException("Error executing query.");
    }

    /**
     * @inheritDoc
     */
    public function escape(string $value): string
    {
        return $this->handle->real_escape_string($value);
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId(): int|string
    {
        return $this->handle->insert_id;
    }

    /**
     * @inheritDoc
     */
    public function getProcessor(): IProcessor
    {
        return $this->processor;
    }
}