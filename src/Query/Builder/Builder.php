<?php
/*
 * Copyright (c) 2022 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Raw;

class Builder extends BuilderInfo implements IBuilder
{
    protected QueryBuilderType $type;

    /**
     * @var array[] Contains list of all bindings
     */
    protected $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'join' => [],
        'insert' => [],
        'update' => [],
        'order' => [],
        'limit' => null,
        'offset' => null,
        'aggregate' => [],
        'distinct' => false
    ];

    /**
     * @param $type
     * @return array
     * @deprecated use getClauses()
     */
    public function getBindings($type = 'where'): array
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
    public function table(string $tableName): static
    {
        return static::from($tableName);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $values): static
    {
        $this->setType(QueryBuilderType::Insert);
        if (!is_array($values)) {
            throw new QueryException('Invalid insert argument');
        }

        if (is_array(current($values))) {
            foreach ($values as $value) {
                ksort($value);
                static::insert($values);
            }
        } else {
            $this->addClause('insert', $values);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function update(?array $values = null): static
    {
        $this->setType(QueryBuilderType::Update);
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
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->setType(QueryBuilderType::Select);
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->addClause('select', $column);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(array|string $columns = ['*']): static
    {
        return $this->select($columns);
    }

    /**
     * @inheritDoc
     */
    public function first(array|string $columns = ['*']): static
    {
        return $this->limit(1)->select($columns);
    }

    /**
     * @inheritDoc
     */
    public function delete(): static
    {
        $this->setType(QueryBuilderType::Delete);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function truncate(): static
    {
        $this->setType(QueryBuilderType::Truncate);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $column, object $value): static
    {
        $this->addClause('update', ['column' => $column, 'value' => $value]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function where(string|Closure|array $column, ?string $operator = null, ?object $value = null, string $boolean = 'AND'): static
    {
        if (is_array($column)) {
            foreach ($column as $whereCondition) {
                if (!is_array($whereCondition)) {
                    throw new QueryException('Invalid Array of Values');
                }
                $this->where(...array_values($whereCondition));
            }
            return $this;
        } elseif ($column instanceof \Closure) {
            $type = 'nested';
            $column($query = new static()); //call the function with new static instance
            $nested = $query->getClauses('where');
            $this->addClause('where', ['nested' => $nested, 'boolean' => $boolean, 'type' => $type]);
            return $this;
        }

        if (is_null($value)) {
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value. Did you mean to call whereNull()');
            }

            $value = $operator;
            $operator = '=';
        } elseif ($this->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $type = 'basic';
        $this->addClause('where', ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean, 'type' => $type]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere(string|Closure|array $column, ?string $operator = null, ?object $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * @inheritDoc
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $this->addClause('where', [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * @inheritDoc
     */
    public function whereBetween(string $column, object $value1, object $value2, string $boolean = 'AND', bool $not = false): static
    {
        $this->addClause('where', [
            'type' => 'between',
            'column' => $column,
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $boolean,
            'not' => $not
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotBetween(string $column, object $value1, object $value2, string $boolean = 'AND'): static
    {
        return $this->whereBetween($column, $value1, $value2, $boolean, true);
    }

    /**
     * @inheritDoc
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $this->addClause('where', [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * @inheritDoc
     */
    public function whereRaw(string|Raw $raw, string $boolean = 'AND'): static
    {
        if (is_string($raw)) {
            $raw = new Raw($raw);
        } elseif (!$raw instanceof Raw) {
            throw new QueryException('$raw must be an instance of Raw class or a string,');
        }
        $this->addClause('where', [
            'type' => 'in',
            'raw' => $raw,
            'boolean' => $boolean
        ]);
        return $this;
    }

    /**
     * @inheritDoc
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
    public function count(array|string $columns = ['*']): IAggregateQuery
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        return $this->aggregate('count', $columns);
    }

    /**
     * @inheritDoc
     */
    public function sum(string $column)
    {
        return $this->aggregate('sum', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function avg(string $column)
    {
        return $this->aggregate('avg', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function max(string $column)
    {
        return $this->aggregate('max', [$column]);
    }

    /**
     * @inheritDoc
     */
    public function min(string $column)
    {
        return $this->aggregate('min', [$column]);
    }

    protected function aggregate(string $function, array $columns = ['*']): IAggregateQuery
    {
        $parent = $this->clone();
        $parent->addClause('aggregate', ['function' => strtoupper($function), 'alias' => $function, 'columns' => $columns]);
        return new AggregateQuery($parent);
    }

    protected function isInvalidOperator(string $operator): bool
    {
        /*
         * isset search is faster than in_array
         * combining isset and for loop is still faster than in_array
         */
        return !isset($this->operators[$operator]);
    }
}