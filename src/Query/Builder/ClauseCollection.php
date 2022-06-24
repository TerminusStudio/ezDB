<?php

namespace TS\ezDB\Query\Builder;

class ClauseCollection
{
    /**
     * @var IClause[]
     */
    private array $clauses;

    public function __construct(IClause ...$clause)
    {
        $this->clauses = $clause;
    }

    public function add(IClause $clause): void
    {
        $this->clauses[] = $clause;
    }

    public function get(string $type): ClauseCollection
    {
        $newClauses = [];

        foreach ($this->clauses as $clause) {
            if ($clause->getType() == $type) {
                $newClauses[] = $clause;
            }
        }
        return new ClauseCollection(...$newClauses);
    }

    /**
     * @return IClause[]
     */
    public function all() : array {
        return $this->clauses;
    }
}