<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace TS\ezDB\Drivers;

use mysqli;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Exceptions\DriverException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Interfaces\DriverInterface;
use TS\ezDB\Query\Processor;

class MySQLiDriver implements DriverInterface
{

    /**
     * @var mysqli
     */
    protected $handle;

    /**
     * @var DatabaseConfig
     */
    protected $databaseConfig;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseConfig $databaseConfig)
    {
        $this->databaseConfig = $databaseConfig;
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); //Report errors
        $this->handle = new mysqli(
            $this->databaseConfig->getHost(),
            $this->databaseConfig->getUsername(),
            $this->databaseConfig->getPassword(),
            $this->databaseConfig->getDatabase()
        );

        if ($this->handle->connect_errno) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     * @return mysqli
     */
    public function handle()
    {
        if ($this->handle == null) {
            throw new DriverException('Driver Handle not found. Make sure you call connect() first.');
        }
        return $this->handle;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return $this->handle->close();
    }

    /**
     * @inheritDoc
     */
    public function reset()
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
    public function prepare(string $query)
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
    public function bind($stmt, &...$params)
    {
        $type = '';

        foreach ($params as $param) {
            if (is_string($param)) {
                $type .= 's';
            } elseif (is_int($param)) {
                $type .= 'i';
            } elseif (is_double($param)) {
                $type .= 'd';
            } else {
                $type .= 's';
            }
        }

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
    public function execute($stmt, $close = true, $fetch = false)
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
    public function query(string $query)
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
    public function exec(string $sql)
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
     * @return array|bool
     * @throws QueryException
     */
    protected function getResults($result)
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
    public function escape(string $value)
    {
        return $this->handle->real_escape_string($value);
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        return $this->handle->insert_id;
    }

    /**
     * @inheritDoc
     */
    public function getRowCount()
    {
        return $this->handle->affected_rows;
    }

    /**
     * @inheritDoc
     */
    public function getProcessor()
    {
        if ($this->processor === null) {
            $this->processor = new Processor();
        }
        return $this->processor;
    }


}