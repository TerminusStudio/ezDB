<?php

namespace TS\ezDB\Query\Builder;

use Closure;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Raw;

class WhereHelper
{
    protected Closure $addClauseClosure;

    /**
     * @var string[] Contains list of all allowed operators.
     */
    protected $operators = [
        '=' => '=',
        '<' => '<',
        '>' => '>',
        '<=' => '<=',
        '>=' => '>=',
        '<>' => '<>',
        'LIKE' => 'LIKE'
    ];

    public function __construct(Closure $addClause)
    {
        $this->addClauseClosure = $addClause;
    }

    public function whereBasic(string|Closure|array $column, ?string $operator, ?object $value, string $boolean): void
    {
        if (is_array($column)) {
            foreach ($column as $whereCondition) {
                if (!is_array($whereCondition)) {
                    throw new QueryException('Invalid Array of Values');
                }
                $this->where(...array_values($whereCondition));
            }
        } elseif ($column instanceof \Closure) {
            $this->whereNestedWithClosure($column, $boolean);
        }

        if (is_null($value)) {
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value. Did you mean to call whereNull()');
            }
            $value = $operator;
            $operator = '=';
        } elseif ($this->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
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

    public function whereBetween(string $column, object $value1, object $value2, string $boolean, bool $not): void
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

    public function whereRaw(string|Raw $raw, string $boolean): void
    {
        if (is_string($raw)) {
            $raw = new Raw($raw);
        } elseif (!$raw instanceof Raw) {
            throw new QueryException('$raw must be an instance of Raw class or a string,');
        }

        $this->addClause([
            'type' => 'in',
            'raw' => $raw,
            'boolean' => $boolean
        ]);
    }

    protected function addClause(array $value): void
    {
        ($this->addClauseClosure)('where', $value);
    }

    protected function isInvalidOperator(string $operator): bool
    {
        /*
         * isset search is faster than in_array
         * combining isset and for loop is still faster than in_array
         */
        return !isset($this->operators[$operator]);
    }
}