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

interface IBuilder extends IBuilderInfo, IWhereBuilder
{
    /**
     * Set from table. Calling this multiple times will set multiple tables.
     * @param string $tableName
     * @return $this
     */
    public function from(string $tableName): static;

    /**
     * @param string $rawSql
     * @return $this
     */
    public function fromRaw(string $rawSql): static;

    /**
     * Set from table. Calling this multiple times will set multiple tables.
     * @param string $tableName
     * @return $this
     */
    public function table(string $tableName): static;

    /**
     * This function accepts 1d and 2d arrays to insert records.
     * 1D Array : ['name' => 'John', 'age' => 21];
     * 2D Array : [ 0 => ['name' => 'John', 'age' => 21], 1=> ['name' => 'Jane', 'age' => 22] ];
     * @param array $values
     * @return $this
     */
    public function asInsert(array $values): static;

    /**
     * Update a column with a given value
     * Update values can either be called using set method or passing an array to this method
     * @param array|null $values
     * @return $this
     */
    public function asUpdate(?array $values = null): static;

    /**
     * Create a select query with the selected columns.
     * @param string|array $columns
     * @return $this
     */
    public function asSelect(string|array $columns = ['*']): static;

    /**
     * Delete the selected rows. This will fail unless where condition is set.
     * @return $this
     */
    public function asDelete(): static;

    /**
     * Delete all rows.
     * @return $this
     */
    public function asTruncate(): static;

    /**
     * Set column/value for update
     * @param string $column
     * @param object|string|int|bool|float|null $value
     * @return $this
     */
    public function set(string $column, object|string|int|bool|float|null $value): static;

    /**
     * @param string $table
     * @param string|Closure $condition1
     * @param string|null $operator
     * @param string|null $condition2
     * @param string $joinType
     * @return $this
     */
    public function join(string $table, string|Closure $condition1, ?string $operator = null, ?string $condition2 = null, string $joinType = 'INNER JOIN'): static;

    /**
     * @param string $table
     * @param INestedJoinBuilder $nestedJoinBuilder
     * @param string $joinType
     * @return $this
     */
    public function joinNested(string $table, INestedJoinBuilder $nestedJoinBuilder, string $joinType = 'INNER JOIN'): static;

    /**
     * @param string $column
     * @param string $operator
     * @param object|string|bool|int|float|null $value
     * @param string $boolean
     * @return mixed
     */
    public function having(string $column, string $operator, object|string|bool|int|float $value = null, string $boolean = 'AND');

    /**
     * @param string $rawSql
     * @param string $boolean
     * @return $this
     */
    public function havingRaw(string $rawSql, string $boolean = 'AND'): static;

    /**
     * @param string|array $columns
     * @return $this
     */
    public function groupBy(string|array $columns): static;

    /**
     * @param string $rawSql
     * @return $this
     */
    public function groupByRaw(string $rawSql): static;

    /**
     * Order by column with optional direction that accepts "ASC" or "DESC".
     * Calling this multiple times will set multiple columns.
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): static;

    /**
     * Limit number of queries and set offset optionally.
     * Calling this multiple times will only use the last added value.
     * @param int $limit
     * @param int|null $offset
     * @return $this
     */
    public function limit(int $limit, ?int $offset = null): static;

    /**
     * Set offset number. Calling this multiple times will only use the last value.
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): static;

    /**
     * @param array|string $columns
     * @return IAggregateQuery
     */
    public function asCount(array|string $columns = ['*']): IAggregateQuery;

    /**
     * @param string $column
     * @return mixed
     */
    public function asSum(string $column): IAggregateQuery;

    /**
     * @param string $column
     * @return mixed
     */
    public function asAvg(string $column): IAggregateQuery;

    /**
     * @param string $column
     * @return mixed
     */
    public function asMax(string $column): IAggregateQuery;

    /**
     * @param string $column
     * @return mixed
     */
    public function asMin(string $column): IAggregateQuery;

    /**
     * @param $set
     * @return mixed
     */
    public function distinct() : static;

}