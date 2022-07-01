<?php

namespace TS\ezDB\Connections\Builder;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Builder\IAggregateQuery;
use TS\ezDB\Query\Builder\QueryType;
use TS\ezDB\Query\Processor\IProcessor;

class ConnectionAwareBuilder extends Builder implements IConnectionAwareBuilder
{
    /**
     * @var Connection The connection against which queries will be executed.
     */
    protected Connection $connection;

    /**
     * @param Connection|string|null $connection
     * @param string|null $tableName
     * @throws Exceptions\ConnectionException
     */
    public function __construct(Connection|string|null $connection = null, ?string $tableName = null)
    {
        if (is_string($connection)) {
            $connection = Connections::connection($connection);
        }

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
     * @inheritDoc
     * @throws QueryException
     */
    public function insert(?array $values = null): bool
    {
        $this->setType(QueryType::Insert);
        if ($values != null) {
            $this->asInsert($values);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->insert($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function update(array|null $values = null): mixed
    {
        $this->setType(QueryType::Update);
        if ($values != null) {
            $this->asUpdate($values);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->update($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function select(array|null $columns = null): mixed
    {
        $this->setType(QueryType::Select);
        if ($columns != null) {
            $this->asSelect($columns);
        }
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->select($query->getRawSql(), ...$query->getBindings());
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function get(array|null $columns = null): mixed
    {
        return $this->select($columns);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function first(array|null $columns = null): array|object
    {
        $this->limit(1, 0);
        $r = $this->select($columns);
        return $r[0] ?? $r;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function delete(): bool
    {
        $this->setType(QueryType::Delete);
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->delete($query, ...$query->getBindings());
    }

    /**
     * @inheritDoc
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\DriverException
     * @throws QueryException
     */
    public function truncate(): bool
    {
        $this->setType(QueryType::Truncate);
        $query = $this->getProcessor()->process($this);
        return $this->getConnection()->raw($query);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function count(array|string $columns = ['*']): mixed
    {
        $q = $this->asCount($columns);
        return $this->executeAggregate($q);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function sum(string $column): mixed
    {
        $q = $this->asSum($column);
        return $this->executeAggregate($q);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function avg(string $column): mixed
    {
        $q = $this->asAvg($column);
        return $this->executeAggregate($q);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function max(string $column): mixed
    {
        $q = $this->asMax($column);
        return $this->executeAggregate($q);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function min(string $column): mixed
    {
        $q = $this->asMin($column);
        return $this->executeAggregate($q);
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
        }
        throw new QueryException("Query type is not supported to be executed: " . $query->getTypeString());
    }

    /**
     * @param IAggregateQuery $aggregateQuery
     * @return mixed
     */
    protected function executeAggregate(IAggregateQuery $aggregateQuery): mixed
    {
        $query = $this->getProcessor()->process($aggregateQuery);
        $results = $this->getConnection()->select($query->getRawSql(), ...$query->getBindings());
        if (empty($results))
            return null;
        //return first key of the first row
        return reset($results[0]);
    }

    /**
     * @return IProcessor
     * @throws Exceptions\ConnectionException
     */
    protected function getProcessor(): IProcessor
    {
        return $this->getConnection()->getDriver()->getProcessor();
    }
}