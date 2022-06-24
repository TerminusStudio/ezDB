<?php

namespace TS\ezDB\Query\Builder;

use Closure;

interface IQueryBuilder
{
    public function getType() : QueryBuilderType;

    public function getClauses(string $type);

    public function from(string $tableName): IQueryBuilder;

    public function table(string $tableName): IQueryBuilder;

    public function insert(array $values): IQueryBuilder;

    public function update(?array $values = null): IQueryBuilder;

    public function where(string|Closure $column, ?string $operator = null, ?string $value = null, string $boolean = "AND") : IQueryBuilder;

    public function orWhere(string|Closure $column, ?string $operator = null, ?string $value = null) : IQueryBuilder;
}