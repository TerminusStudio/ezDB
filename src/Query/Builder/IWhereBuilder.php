<?php

namespace TS\ezDB\Query\Builder;

use TS\ezDB\Query\Raw;
use Closure;

interface IWhereBuilder
{
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
}