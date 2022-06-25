<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\QueryException;

class NestedJoinBuilder extends BuilderInfo implements INestedJoinBuilder
{
    protected IBuilderInfo $parentBuilder;

    public function __construct(IBuilderInfo $parentBuilder)
    {
        $this->builder = $parentBuilder;
    }

    /**
     * @inheritDoc
     */
    public function on(string|Closure $condition1, ?string $operator = null, ?string $condition2 = null, string $boolean = 'AND'): static
    {
        /**
         * For basic join array will be -> [ [table condition etc] ]
         * for nested -> [ [table [ condition ] ]]
         * for double nested -> [ [ table [ [ condition ] ] ]]
         */
        if ($condition1 instanceof \Closure) {
            $condition1($query = new static($this));
            return $this->onNested($query);
        }

        if ($operator == null || $condition2 == null) {
            throw new QueryException("operator and condition2 must be set");
        }

        $type = 'basic';
        $this->addClause('join', [
            'condition1' => $condition1,
            'operator' => $operator,
            'condition2' => $condition2,
            'boolean' => $boolean,
            'type' => $type
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orOn(string|Closure $condition1, ?string $operator = null, ?string $condition2 = null): static
    {
        return $this->on($condition1, $operator, $condition2, 'OR');
    }

    /**
     * @inheritDoc
     */
    public function onNested(INestedJoinBuilder $joinBuilder, string $boolean = 'AND'): static
    {
        $nested = $joinBuilder->getClauses('join');
        $this->addClause('join', [
            'type' => 'nested',
            'boolean' => $boolean,
            'nested' => $nested
        ]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orOnNested(INestedJoinBuilder $joinBuilder): static
    {
        return $this->onNested($joinBuilder, 'OR');
    }


}
