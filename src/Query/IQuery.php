<?php

namespace TS\ezDB\Query;

interface IQuery
{
    public function getRawSql(): string;

    public function getBindings(): array;
}