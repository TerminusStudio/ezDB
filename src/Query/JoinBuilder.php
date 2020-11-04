<?php

namespace TS\ezDB\Query;

use TS\ezDB\Exceptions\QueryException;

class JoinBuilder
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var array
     */
    protected $bindings = [
    ];

    /**
     * JoinBuilder constructor.
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * @param $condition1
     * @param $operator
     * @param $condition2
     * @param string $boolean
     * @return $this
     * @throws QueryException
     */
    public function on($condition1, $operator, $condition2, $boolean = 'AND')
    {
        if ($this->builder->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $this->bindings[] = compact('condition1', 'operator', 'condition2', 'boolean');

        return $this;
    }
}
