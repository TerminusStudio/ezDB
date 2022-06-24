<?php

namespace TS\ezDB\Query\Builder\Clauses;

use TS\ezDB\Query\Builder\IClause;
use TS\ezDB\Query\Builder\IQueryBuilder;

class BasicClause implements IClause
{
    protected string $type;

    protected string $value;

    protected array $arrayValue;

    protected IQueryBuilder $queryValue;

    public function __construct(string $type, string|array|IQueryBuilder $value)
    {
        $this->type = $type;

        if (is_string($value)) {
            $this->value = $value;
        } else if (is_array($value)) {
            $this->arrayValue = $value;
        } else if ($value instanceof IQueryBuilder) {
            $this->queryValue = $value;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }
}