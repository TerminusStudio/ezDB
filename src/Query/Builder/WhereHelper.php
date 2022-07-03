<?php

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Raw;

class WhereHelper
{
    protected Closure $addClauseClosure;

    public function __construct(Closure $addClause)
    {
        $this->addClauseClosure = $addClause;
    }

    public function whereBasic(string|Closure|array $column, ?string $operator = null, object|string|int|bool|float|null $value = null, string $boolean = 'AND'): void
    {
        if (is_array($column)) {
            foreach ($column as $whereCondition) {
                if (!is_array($whereCondition)) {
                    throw new QueryException('Invalid Array of Values');
                }
                $this->whereBasic(...array_values($whereCondition));
            }
            return;
        } elseif ($column instanceof \Closure) {
            $this->whereNestedWithClosure($column, $boolean);
            return;
        }

        if (is_null($value)) {
            //TODO: this should be removed by release. Use named arg to pass value.
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value. Did you mean to call whereNull()');
            }
            $value = $operator;
            $operator = '=';
        }

        $type = 'basic';
        $this->addClause(['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean, 'type' => $type]);
    }

    public function whereNested(IWhereBuilder $builder, string $boolean): void
    {
        $type = 'nested';
        $nested = $builder->getClauses('where');
        $this->addClause(['nested' => $nested, 'boolean' => $boolean, 'type' => $type]);
    }

    public function whereNestedWithClosure(Closure $closure, string $boolean): void
    {
        $type = 'nested';
        $closure($builder = new WhereBuilder()); //call the function with new static instance
        $this->whereNested($builder, $boolean);
    }

    public function whereNull(string $column, string $boolean, bool $not): void
    {
        $this->addClause([
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not
        ]);
    }

    public function whereBetween(string $column, object|string|int|bool|float $value1, object|string|int|bool|float $value2, string $boolean, bool $not): void
    {
        $this->addClause([
            'type' => 'between',
            'column' => $column,
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $boolean,
            'not' => $not
        ]);
    }

    public function whereIn(string $column, array $values, string $boolean, bool $not): void
    {
        $this->addClause([
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ]);
    }

    public function whereRaw(string $raw, string $boolean): void
    {
        $this->addClause([
            'type' => 'raw',
            'raw' => $raw,
            'boolean' => $boolean
        ]);
    }

    protected function addClause(array $value): void
    {
        ($this->addClauseClosure)('where', $value);
    }
}