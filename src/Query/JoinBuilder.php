<?php

namespace TS\ezDB\Query;

use TS\ezDB\Exceptions\QueryException;

class JoinBuilder
{
    protected $builder;

    protected $bindings = [
        'on' => []
    ];

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function getBindings()
    {
        return $this->bindings;
    }
    
    public function on($condition1, $operator, $condition2, $boolean = 'AND')
    {
        if ($this->builder->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $this->bindings[] = compact('condition1', 'operator', 'condition2', 'boolean');

        return $this;
    }
}
