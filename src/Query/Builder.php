<?php

namespace TS\ezDB\Query;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Models\Model;

class Builder
{
    protected $table;

    /**
     * @var Connection Instance of the current connection to database
     */
    protected $connection;

    /**
     * @var Model Model class that contains related information of the table being accessed.
     */
    protected $model;

    /**
     * @var array[] Contains list of all bindings
     */
    protected $bindings = [
        'select' => [],
        'from' => [],
        'where' => [],
        'join' => [],
        'insert' => [],
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
     * set Model class
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->table($model->getTable());
        return $this;
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
     * This function accepts 1d and 2d arrays to insert records.
     * 1D Array : ['name' => 'John', 'age' => 21];
     * 2D Array : [ 0 => ['name' => 'John', 'age' => 21], 1=> ['name' => 'Jane', 'age' => 22] ];
     * Calling this function through the model will update created_at and updated_at timestamps.
     * @param array $values
     * @return bool true/false
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function insert(array $values)
    {
        if (!is_array($values)) {
            throw new QueryException("Invalid insert argument");
        }

        if (is_array(current($values))) {
            foreach ($values as $value) {
                ksort($value);

                //TODO: Check if all the arrays contain the same keys.

                $this->addBinding($value, 'insert');
            }
        } else {
            $this->addBinding($values, 'insert');
        }

        [$sql, $params] = $this->prepareBindings('insert');

        return $this->connection->insert($sql, ...$params);
    }

    /**
     * Update a column with a given value
     * Update values can either be called using set method or passing a array to this method
     * @param array|null $values Accepts any key value array
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function update(array $values = null)
    {
        if ($values != null) {
            if (!is_array($values)) {
                throw new QueryException("Invalid update arguments");
            }

            foreach ($values as $column => $value) {
                $this->set($column, $value);
            }
        }

        [$sql, $params] = $this->prepareBindings('update');

        return $this->connection->update($sql, ...$params);
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
    )
    {
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
     * This method is used for the update. Each column and value can be set separately. Use update method itself for setting using arrays
     * @param $column
     * @param $value
     * @return $this
     */
    public function set($column, $value)
    {
        //TODO: Maybe check whether the column was already set?
        $this->addBinding(compact('column', 'value'), 'update');
        return $this;
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

    /**
     * Get the first row of result
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function first($columns = ['*'])
    {
        $this->limit(1, 0);
        return $this->get($columns);
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
}