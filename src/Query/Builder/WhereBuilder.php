<?php

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Query\Raw;

class WhereBuilder extends BuilderInfo implements IWhereBuilder
{
    protected WhereHelper $whereHelper;

    public function __construct()
    {
        $this->setType(QueryType::Where);
        $this->whereHelper = new WhereHelper($this->addClause(...));
    }

    /**
     * @inheritDoc
     */
    public function where(array|string|Closure $column, ?string $operator = null, object|bool|int|float|string|null $value = null, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereBasic($column, $operator, $value, $boolean);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere(array|string|Closure $column, ?string $operator = null, object|bool|int|float|string|null $value = null): static
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
    public function whereRaw(string|Raw $raw, string $boolean = 'AND'): static
    {
        $this->whereHelper->whereRaw($raw, $boolean);
        return $this;
    }
}