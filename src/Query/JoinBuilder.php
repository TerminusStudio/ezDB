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
    public function on($condition1, $operator = null, $condition2 = null, $boolean = 'AND')
    {
        /**
         * For basic join array will be -> [ [table condition etc] ]
         * for nested -> [ [table [ condition ] ]]
         * for double nested -> [ [ table [ [ condition ] ] ]]
         */
        if ($condition1 instanceof \Closure) {
            $type = 'nested';
            $condition1($query = new self($this->builder));
            $nested = $query->getBindings();
            $this->bindings[] = compact('nested', 'boolean', 'type');
            return $this;
        }

        if ($this->builder->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $type = 'basic';
        $this->bindings[] = compact('condition1', 'operator', 'condition2', 'boolean', 'type');

        return $this;
    }

    public function orOn($condition1, $operator = null, $condition2 = null)
    {
        return $this->on($condition1, $operator, $condition2, 'OR');
    }
}
