<?php

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\Exception;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\Clauses\BasicClause;

class QueryBuilder implements IQueryBuilder
{
    use QueryBuilderWhere;

    protected ClauseCollection $clauses;

    protected QueryBuilderType $builderType = QueryBuilderType::Unknown;

    public function __construct(?string $tableName = null)
    {
        $this->clauses = new ClauseCollection();
        if (!is_null($tableName))
            $this->from($tableName);
    }

    public function getClauses(string $type): ClauseCollection
    {
        return $this->clauses->get($type);
    }

    protected function addClause(IClause $clause): void
    {
        $this->clauses->add($clause);
    }

    public function getType(): QueryBuilderType
    {
        return $this->builderType;
    }

    protected function setType(QueryBuilderType $type): void
    {
        if ($this->builderType != QueryBuilderType::Unknown && $this->builderType != $type) {
            throw new Exception("Can't change builder type once set");
        }
        $this->builderType = $type;
    }


    public function from(string $tableName): QueryBuilder
    {
        $this->addClause(new BasicClause('from', $tableName));
        return $this;
    }

    public function table(string $tableName): QueryBuilder
    {
        return $this->from($tableName);
    }

    public function insert(array $values): QueryBuilder
    {
        $this->setType(QueryBuilderType::Insert);

        if (is_array(current($values))) {
            foreach ($values as $value) {
                ksort($value);
                $this->insert($value);
            }
        } else {
            $this->addClause(new BasicClause('insert', $values));
        }

        return $this;
    }

    public function update(?array $values = null): QueryBuilder
    {
        $this->setType(QueryBuilderType::Update);

        if ($values != null) {
            if (!is_array($values)) {
                throw new Exception('Invalid update arguments');
            }

            foreach ($values as $column => $value) {
                $this->set($column, $value);
            }
        }

        return $this;
    }
}