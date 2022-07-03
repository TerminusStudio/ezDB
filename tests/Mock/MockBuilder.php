<?php

namespace TS\ezDB\Tests\Mock;

use TS\ezDB\Query\Builder\Builder;

class MockBuilder extends Builder
{
    public function replaceClauses($array): void
    {
        foreach ($array as $key => $value) {
            $this->clauses[$key] = $value;
        }
    }
}