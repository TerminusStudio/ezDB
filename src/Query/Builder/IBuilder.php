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
use TS\ezDB\Query\Raw;

interface IBuilder extends IBuilderInfo
{
    /**
     * Set from table. Calling this multiple times will set multiple tables.
     * @param string $tableName
     * @return $this
     */
    public function from(string $tableName): static;

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
    public function insert(array $values): static;

    /**
     * Update a column with a given value
     * Update values can either be called using set method or passing an array to this method
     * @param array|null $values
     * @return $this
     */
    public function update(?array $values = null): static;

    /**
     * Create a select query with the selected columns.
     * @param string|array $columns
     * @return $this
     */
    public function select(string|array $columns = ['*']): static;

    /**
     * Create a select query with the selected columns.
     * @param string|array $columns
     * @return $this
     */
    public function get(string|array $columns = ['*']): static;

    /**
     * Select query that will automatically add a limit to the first row
     * @param string|array $columns
     * @return $this
     */
    public function first(string|array $columns = ['*']): static;

    /**
     * Delete the selected rows. This will fail unless where condition is set.
     * @return $this
     */
    public function delete(): static;

    /**
     * Delete all rows.
     * @return $this
     */
    public function truncate(): static;

    /**
     * Set column/value for update
     * @param string $column
     * @param object $value
     * @return $this
     */
    public function set(string $column, object $value): static;

    /**
     * @param string|Closure|array $column
     * @param string|null $operator
     * @param object|null $value
     * @param string $boolean
     * @return $this
     */
    public function where(string|Closure|array $column, ?string $operator = null, ?object $value = null, string $boolean = 'AND'): static;

    /**
     * @param string|Closure|array $column
     * @param string|null $operator
     * @param object|null $value
     * @return $this
     */
    public function orWhere(string|Closure|array $column, ?string $operator = null, ?object $value = null): static;

    /**
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static;

    /**
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static;

    /**
     * @param string $column
     * @param object $value1
     * @param object $value2
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween(string $column, object $value1, object $value2, string $boolean = 'AND', bool $not = false): static;

    /**
     * @param string $column
     * @param object $value1
     * @param object $value2
     * @param string $boolean
     * @return $this
     */
    public function whereNotBetween(string $column, object $value1, object $value2, string $boolean = 'AND'): static;

    /**
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static;

    /**
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static;

    /**
     * @param string|Raw $raw
     * @param string $boolean
     * @return $this
     */
    public function whereRaw(string|Raw $raw, string $boolean = 'AND'): static;

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
    public function count(array|string $columns = ['*']): IAggregateQuery;

    /**
     * @param string $column
     * @return mixed
     */
    public function sum(string $column);

    /**
     * @param string $column
     * @return mixed
     */
    public function avg(string $column);

    /**
     * @param string $column
     * @return mixed
     */
    public function max(string $column);

    /**
     * @param string $column
     * @return mixed
     */
    public function min(string $column);
}