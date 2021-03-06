<?php

namespace TS\ezDB\Drivers;

use PDO;
use PDOException;
use PDOStatement;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Exceptions\DriverException;
use TS\ezDB\Interfaces\DriverInterface;
use TS\ezDB\Query\Processor\Processor;

class PDODriver implements DriverInterface
{
    /**
     * @var PDO
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
        try {
            $serverName = sprintf(
                "%s:host=%s;dbname=%s",
                $this->databaseConfig->getDriver(),
                $this->databaseConfig->getHost(),
                $this->databaseConfig->getDatabase()
            );

            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );

            if ($this->databaseConfig->getDriver() == 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = sprintf(
                    "SET NAMES %s COLLATE %s",
                    $this->databaseConfig->getCharset(),
                    $this->databaseConfig->getCollation()
                );
            } elseif ($this->databaseConfig->getDriver() == 'pgsql') {
                $serverName .= sprintf(";options='--client_encoding=%s'", $this->databaseConfig->getCharset());
            }

            $this->handle = new PDO(
                $serverName,
                $this->databaseConfig->getUsername(),
                $this->databaseConfig->getPassword(),
                $options
            );

            return true;
        } catch (PDOException $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
        return false;
    }

    /**
     * @inheritDoc
     * @return PDO
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
        $this->handle = null;
        return true;
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
     * @return false|PDOStatement
     * @throws DriverException
     */
    public function prepare(string $query)
    {
        $stmt = $this->handle->prepare($query);
        if ($stmt === false) {
            throw new DriverException("Error trying to prepare statement - " . $this->handle->error);
        }
        return $stmt;
    }

    /**
     * @inheritDoc
     * @param PDOStatement $stmt
     */
    public function bind($stmt, &...$params)
    {
        for ($i = 0; $i < count($params); $i++) {
            if (is_string($params[$i])) {
                $type = PDO::PARAM_STR;
            } elseif (is_int($params[$i])) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($params[$i])) {
                $type = PDO::PARAM_BOOL;
            } else {
                $type = PDO::PARAM_STR;
            }

            $stmt->bindValue($i + 1, $params[$i], $type);
        }
        return $stmt;
    }

    /**
     * @inheritDoc
     * @param PDOStatement $stmt
     * @param bool $close Close Connection
     * @param bool $fetch Fetch Results
     * @throws DriverException
     */
    public function execute($stmt, $close = true, $fetch = false)
    {
        try {
            $stmt->execute();
            if ($fetch) {
                $result = $stmt->fetchAll(PDO::FETCH_CLASS);
            } else {
                $result = $stmt->rowCount();
            }

            if ($close) {
                $stmt->closeCursor();
            }
        } catch (\Exception $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this->getResults($result);
    }

    /**
     * @inheritDoc
     * @throws DriverException
     */
    public function query(string $query)
    {
        try {
            $stmt = $this->handle->query($query);

            try {
                //Try to fetch results
                $result = $stmt->fetchAll(PDO::FETCH_CLASS);
                $stmt->closeCursor();
            } catch (PDOException $PDOException) {
                //if there is error then check if the statement is instance of PDO statement.
                // Queries that don't return anything (like INSERT, DELETE, TRUNCATE) will throw error when we try to
                // fetchAll()
                if ($stmt instanceof PDOStatement) {
                    $result = true;
                } else {
                    throw new DriverException(
                        $PDOException->getMessage(),
                        $PDOException->getCode(),
                        $PDOException->getPrevious()
                    );
                }
            }
        } catch (\Exception $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this->getResults($result);
    }

    /**
     * PDO uses exec to execute this.
     * Avoid using this.
     *
     * This returns an bool which indicates the status of the first query. Subsequent queries could have failed.
     *
     * @inheritDoc
     */
    public function exec(string $sql)
    {
        try {
            $stmt = $this->handle->prepare($sql);
            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        } catch (\Exception $e) {
            //  $this->handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            throw new DriverException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }


    /**
     * @param bool|int|array $result
     * @return array|bool
     * @throws DriverException
     */
    protected function getResults($result)
    {
        if (is_bool($result) || is_int($result) || is_array($result)) {
            return $result;
        }
        throw new DriverException("Error getting results.");
    }

    /**
     * @inheritDoc
     */
    public function escape(string $value)
    {
        $escaped = $this->handle->quote($value);
        return preg_replace('/^\'(.*)\'$/', '$1', $escaped); //remove surrounding quote
    }

    /**
     * @inheritDoc
     */
    public function getLastInsertId()
    {
        return $this->handle->lastInsertId();
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