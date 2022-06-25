<?php

namespace TS\ezDB\Query;

class DefaultQuery implements IQuery
{
    public string $rawSql;
    public array $bindings;

    public function __construct(string $rawSql, array $bindings)
    {
        $this->rawSql = $rawSql;
        $this->bindings = $bindings;
    }

    public function getRawSql(): string
    {
        return $this->rawSql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}