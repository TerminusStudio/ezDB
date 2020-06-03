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
            $column($query = new self()); //call the function with new self instance
            $nestedWhere = $query->getBindings('where'); //get bindings
            //$nestedWhere['boolean'] = $boolean;
            $this->addBinding(['nested' => $nestedWhere, 'boolean' => $boolean], 'where');
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

        $this->addBinding(['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean]);
        return $this;
    }

    public function whereNull($column, $boolean = 'AND')
    {

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