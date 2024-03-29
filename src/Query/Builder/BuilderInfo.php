<?php

namespace TS\ezDB\Query\Builder;

use TS\ezDB\Exceptions\QueryException;

abstract class BuilderInfo implements IBuilderInfo
{
    protected QueryType $type = QueryType::Unknown;

    protected array $clauses = [
        'select' => [],
        'from' => [],
        'where' => [],
        'join' => [],
        'insert' => [],
        'update' => [],
        'group' => [],
        'having' => [],
        'order' => [],
        'limit' => [null],
        'offset' => [null],
        'aggregate' => [],
        'distinct' => [false]
    ];

    /**
     * @inheritDoc
     */
    public function getType(): QueryType
    {
        if (!isset($this->type)) return QueryType::Unknown;
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function setType(QueryType $type, bool $force = false): void
    {
        if (!$force && isset($this->type) && $this->type != QueryType::Unknown && $this->type != $type) {
            throw new QueryException("Cannot change query type once set");
        }
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getClauses(string $type): array|null
    {
        if (isset($this->clauses[$type]))
            return $this->clauses[$type];

        return null;
    }

    public function addClause(string $type, string|array|int|null|bool|float $value, bool $replace = false)
    {
        if ($replace && $value != null && !is_array($value))
            $value = [$value];

        if ($replace)
            $this->clauses[$type] = $value;
        else
            $this->clauses[$type][] = $value;
    }

    public function clone(): static
    {
        $clone = new static();
        $clone->clauses = $this->clauses;
        $clone->type = $this->type ?? QueryType::Unknown;
        return $clone;
    }
}