<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\QueryException;

class Builder
{
    protected $table;

    /**
     * @var Connection Instance of the current connection to database
     */
    protected $connection;

    /**
     * @var array[] Contains list of all bindings
     */
    protected $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'join' => [],
        'limit' => ['limit' => null, 'offset' => 0]
    ];

    /**
     * @var string[] Contains list of all allowed operators.
     */
    protected $operators = [
        '=' => '=',
        '<' => '<',
        '>' => '>',
        '<=' => '<=',
        '>=' => '>=',
        '<>' => '<>'
    ];

    /**
     * Builder constructor.
     * @param Connection|null $connection
     */
    public function __construct(Connection $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Get current connection
     * @return Connection
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function getConnection()
    {
        if ($this->connection == null) {
            $this->connection = Connections::connection();
        }
        return $this->connection;
    }

    /**
     * @param $binding
     * @param string $type
     */
    public function addBinding($binding, $type = 'where')
    {
        $this->bindings[$type][] = $binding;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getBindings($type = 'where')
    {
        return $this->bindings[$type];
    }

    /**
     * @param string $type
     * @return array
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function prepareBindings($type = "select")
    {
        return $this->getConnection()->getDriver()->getProcessor()->$type($this->bindings);
    }

    /**
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->addBinding($table, 'from');
        return $this;
    }

    /**
     * @param $table
     * @param $condition1
     * @param null $operator
     * @param null $condition2
     * @param string $joinType
     * @return $this
     * @throws QueryException
     */
    public function join(
        $table,
        $condition1,
        $operator = null,
        $condition2 = null,
        $joinType = 'INNER JOIN'
    ) {
        if ($condition1 instanceof \Closure) {
            $type = 'nested';
            $condition1($query = new JoinBuilder($this)); //call the function with new instance of join builder
            $nested = $query->getBindings();
            if ($operator != null) {
                //Assume operator is the joinType for nested statements
                $joinType = $operator;
            }
            $this->addBinding(compact('table', 'nested', 'joinType', 'type'), 'join');
            return $this;
        }
        if ($condition2 == null) {
            throw new QueryException('Invalid Condition');
        }
        if ($this->isInvalidOperator($operator)) {
            throw new QueryException('Invalid Operator');
        }

        $type = 'basic';
        $this->addBinding(
            compact('table', 'condition1', 'operator', 'condition2', 'joinType', 'type'),
            'join'
        );
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     * @throws QueryException
     */
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

    /**
     * @param $column
     * @param string $boolean
     * @param false $not
     * @return $this
     */
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

    /**
     * @param $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * @param $column
     * @param array|null $value
     * @param string $boolean
     * @param false $not
     * @return $this
     */
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

    /**
     * @param $column
     * @param array|null $value
     * @param string $boolean
     * @return $this
     */
    public function whereNotBetween($column, array $value = null, $boolean = 'AND')
    {
        return $this->whereBetween($column, $value, $boolean, true);
    }

    /**
     * @param $limit
     * @param null $offset
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->bindings['limit']['limit'] = $limit;
        if ($offset !== null) {
            return $this->offset($offset);
        }
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->bindings['limit']['offset'] = $offset;
        return $this;
    }

    /**
     * @param $operator
     * @return bool
     */
    public function isInvalidOperator($operator)
    {
        /*
         * isset search is faster than in_array
         * combining isset and for loop is still faster than in_array
         */
        return !isset($this->operators[$operator]);
    }

    /**
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function get($columns = ['*'])
    {
        foreach ($columns as $column) {
            $this->addBinding($column, 'select');
        }

        [$sql, $params] = $this->prepareBindings();

        return $this->connection->select($sql, ...$params);
    }
}