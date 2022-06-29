<?php

namespace TS\ezDB\Query;

use TS\ezDB\Query\Builder\QueryType;

class DefaultQuery implements IQuery
{
    public string $rawSql;
    public array $bindings;
    public QueryType $type;

    public function __construct(QueryType $type, string $rawSql, array $bindings)
    {
        $this->rawSql = $rawSql;
        $this->bindings = $bindings;
        $this->type = $type;
    }

    public function getRawSql(): string
    {
        return $this->rawSql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getType(): QueryType
    {
        return $this->type;
    }

    public function getTypeString(): string
    {
        return match ($this->getType()) {
            QueryType::Insert => 'Insert',
            QueryType::Update => 'Update',
            QueryType::Select => 'Select',
            QueryType::Delete => 'Delete',
            QueryType::Truncate => 'Truncate',
            QueryType::Where => 'Where',
            QueryType::Aggregate => 'Aggregate',
            default => 'Unknown',
        };
    }

}