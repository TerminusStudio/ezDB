<?php
/*
 * Copyright (c) 2022 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Raw;

class Builder extends BuilderInfo implements IBuilder
{
    protected QueryType $type;

    protected WhereHelper $whereHelper;

    public function __construct(?string $tableName = null)
    {
        $this->whereHelper = new WhereHelper(Closure::fromCallable([$this, 'addClause']));
        if ($tableName != null) {
            $this->from($tableName);
        }
    }

    /**
     * @param string $type
     * @return array
     * @deprecated use getClauses()
     */
    public function getBindings(string $type = 'where'): array
    {
        return $this->getClauses($type);
    }

    /**
     * @inheritDoc
     */
    public function from(string $tableName): static
    {
        $this->addClause('from', $tableName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fromRaw(string $rawSql): static
    {
        $this->addClause('from', ['raw' => $rawSql]);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function table(string $tableName): static
    {
        return $this->from($tableName);
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function asInsert(array $values): static
    {
        $this->setType(QueryType::Insert);
        if (!is_array($values)) {
            throw new QueryException('Invalid insert argument');
        }

        if (is_array(current($values))) {
            foreach ($values as $value) {
                ksort($value);
                $this->asInsert($value);
            }
        } else {
            $this->addClause('insert', $values);
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function asUpdate(?array $values = null): static
    {
        $this->setType(QueryType::Update);
        if ($values != null) {
            if (!is_array($values)) {
                throw new QueryException('Invalid update arguments');
            }

            foreach ($values as $column => $value) {
                $this->set($column, $value);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function asSelect(array|string $columns = ['*']): static
    {
        $this->setType(QueryType::Select);
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if ($columns != ['*'] || empty($this->getClauses('select'))) {
            foreach ($columns as $column) {
                $this->addClause('select', $column);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function asDelete(): static
    {
        $this->setType(QueryType::Delete);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function asTruncate(): static
    {
        $this->setType(QueryType::Truncate);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $column, object|string|int|bool|float|null $value): static
    {
        $this->addClause('update', ['column' => $column, 'value' => $value]);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function where(string|Closure|array $column, ?string $operator = null, object|string|int|bool|float|null $value = null, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereBasic($column, $operator, $value, $boolean);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function orWhere(string|Closure|array $column, ?string $operator = null, object|string|int|bool|float|null $value = null): static
    {
        $this->whereHelper->whereBasic($column, $operator, $value, 'OR');
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $this->whereHelper->whereNull($column, $boolean, $not);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereNull($column, $boolean, true);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereBetween(string $column, object|bool|int|float|string $value1, object|bool|int|float|string $value2, string $boolean = 'AND', bool $not = false): static
    {
        $this->whereHelper->whereBetween($column, $value1, $value2, $boolean, $not);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotBetween(string $column, object|bool|int|float|string $value1, object|bool|int|float|string $value2, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereBetween($column, $value1, $value2, $boolean, true);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $this->whereHelper->whereIn($column, $values, $boolean, $not);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereIn($column, $values, $boolean, true);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereRaw(string $raw, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereRaw($raw, $boolean);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function join(string $table, string|Closure $condition1, ?string $operator = null, ?string $condition2 = null, string $joinType = 'INNER JOIN'): static
    {
        if ($condition1 instanceof Closure) {
            $condition1($query = new NestedJoinBuilder($this)); //call the function with new instance of join builder

            if ($operator != null && str_contains($operator, 'JOIN')) { //TODO: remove this
                $joinType = $operator;
            }

            return $this->joinNested($table, $query, $joinType);
        }

        if ($operator == null || $condition2 == null) {
            throw new QueryException("operator and condition2 must be set");
        }

        $this->addClause('join', [
            'table' => $table,
            'type' => 'basic',
            'condition1' => $condition1,
            'condition2' => $condition2,
            'operator' => $operator,
            'joinType' => $joinType
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function joinNested(string $table, INestedJoinBuilder $nestedJoinBuilder, string $joinType = 'INNER JOIN'): static
    {
        $nested = $nestedJoinBuilder->getClauses('join');
        $this->addClause('join', [
            'type' => 'nested',
            'nested' => $nested,
            'table' => $table,
            'joinType' => $joinType
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function having(string $column, string $operator, float|object|bool|int|string $value = null, string $boolean = 'AND')
    {
        if (is_null($value)) {
            //TODO: this should be removed by release. Use named arg to pass value.
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value.');
            }
            $value = $operator;
            $operator = '=';
        }

        $this->addClause('having', ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean, 'type' => 'basic']);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function havingRaw(string $rawSql, string $boolean = 'AND'): static
    {
        $this->addClause('having', ['raw' => $rawSql, 'boolean' => $boolean, 'type' => 'raw']);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groupBy(array|string $columns): static
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->addClause('group', [
            'columns' => $columns
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function groupByRaw(string $rawSql): static
    {
        $this->addClause('group', [
            'raw' => $rawSql
        ]);
        return $this;
    }


    /**
     * @inheritDoc
     * @throws QueryException
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new QueryException("Order Direction must be ASC or DESC");
        }

        $this->addClause('order', ['column' => $column, 'direction' => $direction]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function limit(int $limit, ?int $offset = null): static
    {
        $this->addClause('limit', $limit, replace: true);
        if ($offset != null)
            $this->addClause('offset', $offset, replace: true);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offset(int $offset): static
    {
        $this->addClause('offset', $offset, replace: true);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function asCount(array|string $columns = ['*']): IAggregateQuery
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        return $this->asAggregate('count', $columns);
    }

    /**
     * @inheritDoc
     */
    public function asSum(string $column): IAggregateQuery
    {
        return $this->asAggregate('sum', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function asAvg(string $column): IAggregateQuery
    {
        return $this->asAggregate('avg', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function asMax(string $column): IAggregateQuery
    {
        return $this->asAggregate('max', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function asMin(string $column): IAggregateQuery
    {
        return $this->asAggregate('min', [$column]);
    }

    protected function asAggregate(string $function, array $columns = ['*']): IAggregateQuery
    {
        $parent = $this->clone();
        $parent->addClause('aggregate', ['function' => strtoupper($function), 'alias' => $function, 'columns' => $columns]);
        return new AggregateQuery($parent);
    }

    /**
     * @inheritDoc
     */
    public function distinct(): static
    {
        $this->addClause('distinct', true, replace: true);
        return $this;
    }
}