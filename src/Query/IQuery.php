<?php

namespace TS\ezDB\Query;

use TS\ezDB\Query\Builder\QueryType;

interface IQuery
{
    public function getRawSql(): string;

    public function getBindings(): array;

    public function getType(): QueryType;

    public function getTypeString(): string;
}