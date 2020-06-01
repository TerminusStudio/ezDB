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
        'where' => []
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
        if ($connection == null) {
            $this->connection = Connections::connection();
        } else {
            $this->connection = $connection;
        }
    }

    public function addBinding($binding, $type = 'where')
    {
        $this->bindings[$type][] = $binding;
    }

    public function prepareBindings()
    {
        $sql = "SELECT";
        $params = [];

        if (count($this->bindings['select']) > 0) {
            reset($this->bindings['select']);
            $sql .= ' ' . current($this->bindings['select']);
            while ($select = next($this->bindings['select'])) {
                $sql .= ', ' . $select;
            }
        } else {
            $sql .= ' *';
        }


        $sql .= " FROM";

        if (count($this->bindings['from']) > 0) {
            reset($this->bindings['from']);
            $sql .= ' `' . current($this->bindings['from']) . '`';
            while ($select = next($this->bindings['from'])) {
                $sql .= ', `' . $select . '`';
            }
        } else {
            throw new QueryException('Table not set.');
        }

        if (count($this->bindings['where']) > 0) {
            $sql .= " WHERE";
            $boolean = '';
            foreach ($this->bindings['where'] as $where) {
                $sql .= $boolean;
                $sql .= ' ' . $where[0] . ' ' . $where[1] . ' ? ';
                $boolean = $where[3];
                $params[] = $where[2];
            }
        }

        return [$sql, $params];
    }

    public function table($table)
    {
        $this->addBinding($table, 'from');
        return $this;
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
        $stmt = $this->connection->getDriver()->prepare($sql);
        $this->connection->getDriver()->bind($stmt, ...$params);
        return $this->connection->getDriver()->execute($stmt, true, true);
    }


}