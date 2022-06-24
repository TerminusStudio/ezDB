<?php

namespace TS\ezDB\Query\Builder;

interface IClause
{
    public function getType() : string;
}