<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Builder;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\DB;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Models\Model;
use TS\ezDB\Query\Raw;

class Builder
{

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
        'update' => [],
        'order' => [],
        'limit' => ['limit' => null, 'offset' => 0],
        'aggregate' => [],
        'distinct' => false
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
        '<>' => '<>',
        'LIKE' => 'LIKE'
    ];

    /**
     * @var array Contains a list of relationships that needs to be eager loaded
     */
    protected $eagerLoad = [];

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
    public function setModel(Model $model = null)
    {
        $this->model = $model;
        if ($model != null) {
            $this->table($model->getTable());
        }
        return $this;
    }

    /**
     * Check if the model class is set
     * @return bool
     */
    public function hasModel()
    {
        return ($this->model != null);
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
     * This function adds to a binding.
     * @param $binding
     * @param string $type
     */
    public function addBinding($binding, $type = 'where')
    {
        $this->bindings[$type][] = $binding;
        return $this;
    }

    /**
     * This function sets a binding. Overwrites any previous bindings completely.
     * @param $binding
     * @param string $type
     */
    protected function setBinding($binding, $type = 'where')
    {
        $this->bindings[$type] = $binding;
        return $this;
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
    public function prepareBindings($type = 'select')
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
     * @return $this
     */
    public function from($table)
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
    public function insert($values)
    {
        if (!is_array($values)) {
            throw new QueryException('Invalid insert argument');
        }

        if (is_array(current($values))) {
            foreach ($values as $value) {
                if ($this->hasModel() && $this->model->hasTimestamps()) {
                    $value[$this->model->getCreatedAt()] = $this->now();
                    $value[$this->model->getUpdatedAt()] = $this->now();
                }

                ksort($value);
                //TODO: Check if all the arrays contain the same key

                $this->addBinding($value, 'insert');
            }
        } else {
            if ($this->hasModel() && $this->model->hasTimestamps()) {
                $values[$this->model->getCreatedAt()] = $this->now();
                $values[$this->model->getUpdatedAt()] = $this->now();
            }

            $this->addBinding($values, 'insert');
        }

        [$sql, $params] = $this->prepareBindings('insert');

        return $this->connection->insert($sql, ...$params);
    }

    /**
     * Update a column with a given value
     * Update values can either be called using set method or passing a array to this method
     * @param array $values Accepts any key value array
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function update($values = null)
    {
        if ($values != null) {
            if (!is_array($values)) {
                throw new QueryException('Invalid update arguments');
            }

            foreach ($values as $column => $value) {
                $this->set($column, $value);
            }
        }

        if ($this->hasModel() && $this->model->hasTimestamps()) {
            $this->set($this->model->getUpdatedAt(), $this->now());
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
     * @param string|\Closure|Raw $column
     * @param string|array|null $operator
     * @param string|null $value
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
                $this->where(...array_values($value));
            }
            return $this;
        } elseif ($column instanceof \Closure) {
            $type = 'nested';
            $column($query = new static()); //call the function with new static instance
            $nested = $query->getBindings('where'); //get bindings
            $this->addBinding(compact('nested', 'boolean', 'type'), 'where');
            return $this;
        } elseif ($column instanceof Raw) {
            $operator = $operator ?? []; //Oprator contains bindings.
            return $this->whereRaw($column, $operator, $boolean);
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
     * @param null $operator
     * @param null $value
     * @return $this
     * @throws QueryException
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
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
     * @param array $value
     * @param string $boolean
     * @param false $not
     * @return $this
     */
    public function whereBetween($column, array $value, $boolean = 'AND', $not = false)
    {
        $type = 'between';
        /*if (is_array($column)) {
            foreach ($column as $c) {
                return $this->whereBetween($c, $value, $boolean, $not);
            }
        }*/

        if (count($value) !== 2) {
            throw new QueryException("Value array length must be 2.");
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
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param false $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'AND', $not = false)
    {
        $type = 'in';

        $this->addBinding(compact('column', 'type', 'values', 'boolean', 'not'), 'where');

        return $this;
    }

    /**
     * @param $column
     * @param $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'AND')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Execute raw where statements.
     *
     * @param string|Raw $raw
     * @param array $values
     * @param string $boolean
     * @return $this
     * @throws QueryException
     */
    public function whereRaw($raw, $values = [], $boolean = 'AND')
    {
        $type = 'raw';
        $values = (array)$values;

        if (is_string($raw)) {
            $raw = new Raw($raw);
        } elseif (!$raw instanceof Raw) {
            throw new QueryException('$raw must be an instance of Raw class or a string,');
        }

        $this->addBinding(compact('raw', 'values', 'boolean', 'type'), 'where');

        return $this;
    }

    /**
     * @param $column
     * @param string $direction
     * @throws QueryException
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction);
        if ($direction !== 'asc' && $direction !== 'desc') {
            throw new QueryException("Order Direction must be ASC or DESC");
        }

        $this->addBinding(compact('column', 'direction'), 'order');
        return $this;
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
     * This method is used for the update. Each column and value can be set separately.
     * Use update method itself for setting using arrays
     *
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
     * This function should be used with the model for eager loading.
     *
     * TODO: Support loading relations in a single query.
     * SELECT users.*, '' as `with`, api.user_id is null as `exists`, api.* FROM users
     * LEFT JOIN api ON users.id = api.user_id
     *
     * @param string|array $relations
     * @return Builder
     * @throws ModelMethodException
     */
    public function with($relations)
    {
        if (!$this->hasModel()) {
            throw new ModelMethodException("with() method is only accessible when using Models.");
        }

        if (is_array($relations)) {
            array_merge($this->eagerLoad, $relations);
        } else {
            $this->eagerLoad[] = $relations;
        }

        return $this;
    }

    public function distinct($set = true)
    {
        $this->bindings['distinct'] = $set;
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\QueryException
     * @throws ModelMethodException
     */
    public function get($columns = ['*'])
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->addBinding($column, 'select');
        }

        [$sql, $params] = $this->prepareBindings('select');

        $r = $this->connection->select($sql, ...$params);

        if (!$this->hasModel()) {
            return $r;
        }

        return $this->model::createFromResult($r, $this->eagerLoad);
    }

    /**
     * Delete from table. Only works if conditions are set, to delete all rows use truncate().
     *
     * @return array|bool|int|mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\QueryException
     */
    public function delete()
    {
        //For safety make sure there is some conditions set.
        //TODO: Maybe let user specify a force delete all behaviour
        if (empty($this->getBindings('where'))) {
            throw new QueryException(
                'delete() method was called without any conditions. ' .
                'If you want to delete all the rows, use truncate() instead.'
            );
        }

        [$sql, $params] = $this->prepareBindings('delete');

        return $this->connection->delete($sql, ...$params);
    }

    /**
     * Truncate a table.
     *
     * @return array|bool|int|mixed|object
     * @throws \TS\ezDB\Exceptions\ConnectionException|\TS\ezDB\Exceptions\QueryException
     */
    public function truncate()
    {
        $sql = $this->prepareBindings('truncate');

        $result = $this->connection->raw($sql);
        return ($result || empty($result));
    }

    /**
     * Get the first row of result
     *
     * @param string[] $columns
     * @return array|bool|mixed
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function first($columns = ['*'])
    {
        $this->limit(1, 0);
        $r = $this->get($columns);
        //Select the first object from the array and return.
        return $r[0] ?? $r;
    }

    /**
     * Get the count result of the query.
     *
     * @param string $columns
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function count($columns = '*')
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        return intval($this->aggregate('count', $columns));
    }

    /**
     * Find the sum of the given column.
     *
     * @param $column
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function sum($column)
    {
        return $this->aggregate('sum', [$column]);
    }

    /**
     * Find the avg of a given column.
     *
     * @param $column
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function avg($column)
    {
        return $this->aggregate('avg', [$column]);
    }

    /**
     * Find the max of a given column.
     *
     * @param $column
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function max($column)
    {
        return $this->aggregate('max', [$column]);
    }

    /**
     *Find the min of a given column.
     *
     * @param $column
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function min($column)
    {
        return $this->aggregate('min', [$column]);
    }

    /**
     * Aggregate Functions. This function clones the current builder and returns the result of function when called.
     *
     * TODO: Allow chaining of aggregation methods.
     *
     * @param $function
     * @param string[] $columns
     * @return mixed
     * @throws QueryException
     * @throws \TS\ezDB\Exceptions\ConnectionException
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = (clone $this)
            ->setModel(null)
            ->setBinding(compact('function', 'columns'), 'aggregate')
            ->get($columns);

        return $results[0]->$function;
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
     * Return current datetime (to be used with mysql)
     * It returns the current time in PHP's timezone.
     * Make sure the timezone between the php server and the mysql server match.
     *
     * TODO: Maybe develop a way to execute MySQL NOW()
     *
     * @return string
     */
    protected function now()
    {
        return date("Y-m-d H:i:s");
    }
}