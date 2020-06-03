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

    protected $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'join' => [],
        'limit' => ['limit' => null, 'offset' => 0]
    ];

    protected $operators = [
        '=' => '=',
        '<' => '<',
        '>' => '>',
        '<=' => '<=',
        '>=' => '>=',
        '<>' => '<>'
    ];

    public function __construct(Connection $connection = null)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        if ($this->connection == null) {
            $this->connection = Connections::connection();
        }
        return $this->connection;
    }

    public function addBinding($binding, $type = 'where')
    {
        $this->bindings[$type][] = $binding;
    }

    public function getBindings($type = 'where')
    {
        return $this->bindings[$type];
    }

    public function prepareBindings($type = "select")
    {
        return $this->getConnection()->getDriver()->getProcessor()->$type($this->bindings);
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
            $type = 'nested';
            $column($query = new self()); //call the function with new self instance
            $nested = $query->getBindings('where'); //get bindings
            $this->addBinding(compact('nested', 'boolean', 'type'), 'where');
            return $this;
        }
        if (is_null($value)) {
            if (is_null($operator)) {
                throw new QueryException('Null Operator and Value. Did you mean to call whereNull()');
            }

            $value = $operator;
            $operator = "=";
        } elseif ($this->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }
        $type = 'basic';
        $this->addBinding(compact('column', 'operator', 'value', 'boolean', 'type'), 'where');
        return $this;
    }

    public function whereNull($column, $boolean = 'AND', $not = false)
    {
        $type = 'isNull';
        if (is_array($column)) {
            foreach ($column as $c) {
                return $this->whereNull($c, $boolean, $not);
            }
        }
        $this->addBinding(compact('column', 'type', 'boolean', 'not'), 'where');
        return $this;
    }

    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function whereBetween($column, array $value = null, $boolean = 'AND', $not = false)
    {
        $type = 'between';
        if (is_array($column)) {
            foreach ($column as $c) {
                return $this->whereBetween($c, $value, $boolean, $not);
            }
        }
        $this->addBinding(compact('column', 'type', 'value', 'boolean', 'not'), 'where');
        return $this;
    }

    public function whereNotBetween($column, array $value = null, $boolean = 'AND')
    {
        return $this->whereBetween($column, $value, $boolean, true);
    }

    public function limit($limit, $offset = null)
    {
        $this->bindings['limit']['limit'] = $limit;
        if ($offset !== null) {
            return $this->offset($offset);
        }
        return $this;
    }

    public function offset($offset)
    {
        $this->bindings['limit']['offset'] = $offset;
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
}