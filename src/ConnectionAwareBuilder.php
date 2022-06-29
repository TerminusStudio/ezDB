<?php

namespace TS\ezDB;

use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Builder\QueryType;
use TS\ezDB\Query\Processor\IProcessor;

class ConnectionAwareBuilder extends Builder
{
    protected Connection $connection;

    public function __construct(?Connection $connection = null, ?string $tableName = null)
    {
        $this->connection = $connection ?? Connections::connection();
        parent::__construct($tableName);
    }

    /***
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param array|null $values
     * @return bool
     * @throws QueryException
     */
    public function executeInsert(array|null $values = null): bool
    {
        $this->setType(QueryType::Insert);
        if ($values != null) {
            $this->insert($values);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->insert($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @param array|null $values
     * @return array|bool|mixed
     * @throws QueryException
     */
    public function executeUpdate(array|null $values = null): mixed
    {
        $this->setType(QueryType::Update);
        if ($values != null) {
            $this->update($values);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->update($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @param array|null $columns
     * @return array|bool|mixed
     * @throws QueryException
     */
    public function executeSelect(array|null $columns = null): mixed
    {
        $this->setType(QueryType::Select);
        if ($columns != null) {
            $this->select($columns);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->select($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @param array|null $columns
     * @return array|bool|mixed
     * @throws QueryException
     */
    public function get(array|null $columns = null): mixed
    {
        return $this->executeSelect($columns);
    }

    /**
     * Select first row
     * @param array|null $columns
     * @return string[]
     * @throws QueryException
     */
    public function first(array|null $columns = null): array
    {
        $this->limit(1, 0);
        $r = $this->executeSelect($columns);
        return $r[0] ?? $r;
    }

    /**
     * @return bool
     * @throws QueryException
     */
    public function executeDelete(): bool
    {
        $this->setType(QueryType::Delete);
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->delete($query, ...$query->getBindings());
    }

    /**
     * @return bool
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\DriverException
     * @throws QueryException
     */
    public function executeTruncate(): bool
    {
        $this->setType(QueryType::Truncate);
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->raw($query);
    }

    /**
     * @return mixed
     * @throws QueryException
     */
    public function executeAggregate(): mixed
    {
        $this->setType(QueryType::Aggregate);
        $query = $this->getProcessor()->process($this);
        $results = $this->getConnection()->select($query->getRawSql(), ...$query->getBindings());
        if (empty($results))
            return null;
        //return first key of the first row
        return reset($results[0]);
    }

    /**
     * @return mixed
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\DriverException
     * @throws QueryException
     */
    public function execute(): mixed
    {
        $query = $this->getProcessor()->process($this);
        switch ($query->getType()) {
            case QueryType::Insert:
                return $this->executeInsert();
            case QueryType::Update:
                return $this->executeUpdate();
            case QueryType::Select:
                return $this->executeSelect();
            case QueryType::Delete:
                return $this->executeDelete();
            case QueryType::Truncate:
                return $this->executeTruncate();
            case QueryType::Aggregate:
                return $this->executeAggregate();
        }
        throw new QueryException("Query type is not supported to be executed: " . $query->getTypeString());
    }

    /**
     * @return IProcessor
     */
    protected function getProcessor(): IProcessor
    {
        return $this->getConnection()->getDriver()->getProcessor();
    }
}