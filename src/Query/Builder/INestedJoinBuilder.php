<?php

namespace TS\ezDB\Query\Builder;

use Closure;

interface INestedJoinBuilder extends IBuilderInfo
{
    /**
     * @param string|Closure $condition1
     * @param string|null $operator
     * @param string|null $condition2
     * @param string $boolean
     * @return $this
     */
    public function on(string|Closure $condition1, ?string $operator = null, ?string $condition2 = null, string $boolean = 'AND'): static;

    /**
     * @param string|Closure $condition1
     * @param string|null $operator
     * @param string|null $condition2
     * @return $this
     */
    public function orOn(string|Closure $condition1, ?string $operator = null, ?string $condition2 = null): static;

    /**
     * @param INestedJoinBuilder $joinBuilder
     * @param string $boolean
     * @return $this
     */
    public function onNested(INestedJoinBuilder $joinBuilder, string $boolean = 'AND'): static;

    /**
     * @param INestedJoinBuilder $joinBuilder
     * @return $this
     */
    public function orOnNested(INestedJoinBuilder $joinBuilder): static;
}