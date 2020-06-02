<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\QueryException;

class Builder
{
    protected $table;

    /**
     * @var Connection
     */
    protected $connection;

    public $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'limit'
    ];

    protected $operators = [
        '=' => '=',
        '<' => '<',
        '>' => '>',
        '<=' => '<=',
        '>=' => '>=',
        '<>' => '<>'
    ];

    protected $nestedBindings = false;

    public function __construct(Connection $connection = null)
    {
        if ($connection == null) {
            $this->connection = Connections::connection();
        } else {
            $this->connection = $connection;
        }
    }

    public function addBinding($binding, $type = 'where')
    {
        if ($this->nestedBindings === true) {
            $this->bindings[$type][array_key_last($this->bindings[$type])][] = $binding;
        } else {
            $this->bindings[$type][] = $binding;
        }
    }

    public function prepareBindings($type = "select")
    {
        return $this->connection->getDriver()->getProcessor()->$type($this->bindings);
    }

    public function table($table)
    {
        $this->addBinding($table, 'from');
        return $this;
    }

    public function join($table)
    {

    }

    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if (is_array($column)) {
            foreach ($column as $value) {
                if (!is_array($value)) {
                    throw new QueryException('Invalid Array of Values');
                }
                return $this->where(...array_values($value));
            }
        } elseif ($column instanceof \Closure) {
            $this->nestedBindings(true, 'where');
            $column($this);
            $this->addBinding($boolean, 'where');
            $this->nestedBindings(false);
            return $this;
        }
        if (is_null($value)) {
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value');
            }

            $value = $operator;
            $operator = "=";
        } elseif ($this->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $this->addBinding([$column, $operator, $value, $boolean]);
        return $this;
    }

    protected function isInvalidOperator($operator)
    {
        /*
         * isset search is faster than in_array
         * combining isset and for loop is still faster than in_array
         */
        return !isset($this->operators[$operator]);
    }


    public function get($columns = ['*'])
    {
        foreach ($columns as $column) {
            $this->addBinding($column, 'select');
        }

        [$sql, $params] = $this->prepareBindings();
        return $this->connection->select($sql, ...$params);
    }

    private function nestedBindings($enable, $type = 'where')
    {
        if ($enable) {
            $this->addBinding([], $type);
        }

        $this->nestedBindings = $enable;
    }
}