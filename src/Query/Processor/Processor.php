<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\QueryException;

/**
 * Class Processor
 * This class is used for compiling the the built queries to language specific drivers.
 * @package TS\ezDB\Query
 */
class Processor
{
    public function insert($bindings)
    {
        $sql = 'INSERT INTO';
        $params = [];

        if (count($bindings['from']) == 1) {
            $sql .= ' ' . $this->wrap(current($bindings['from']));
        } else {
            throw new QueryException('Table not set or multiple tables set.');
        }

        $sql .= ' (';
        $columns = array_keys(current($bindings['insert']));

        $sql .= $this->wrap(current($columns));
        while ($from = next($columns)) {
            $sql .= ', ' . $this->wrap($from);
        }

        $sql .= ') VALUES';

        if (count($bindings['insert']) <= 0) {
            throw new QueryException('No data to insert.');
        }

        $columns = implode(',', array_fill(0, count($columns), '?'));

        $sql .= ' (' . $columns . ')';
        $params = array_values(current($bindings['insert']));
        while ($insert = next($bindings['insert'])) {
            $sql .= ', (' . $columns . ')';
            $params = array_merge($params, array_values($insert));
        }

        return [$sql, $params];
    }

    public function update($bindings)
    {
        $sql = 'UPDATE';
        $params = [];

        if (count($bindings['from']) == 1) {
            $sql .= ' ' . $this->wrap(current($bindings['from']));
        } else {
            throw new QueryException('Table not set or multiple tables set.');
        }

        $sql .= ' SET';

        if (count($bindings['update']) <= 0) {
            throw new QueryException('No data to update.');
        }

        $sql .= ' ' . $this->wrap(current($bindings['update'])['column']) . ' = ?';
        $params[] = current($bindings['update'])['value'];

        while ($set = next($bindings['update'])) {
            $sql .= ', ' . $this->wrap($set['column']) . ' = ?';
            $params[] = $set['value'];
        }

        if (count($bindings['where']) > 0) {
            $sql .= " WHERE";
            [$whereSQL, $whereParams] = $this->where($bindings['where']);
            $sql .= $whereSQL;
            $params = array_merge($params, $whereParams);
        }

        return [$sql, $params];
    }

    /**
     * @param $bindings
     * @return array
     * @throws QueryException
     */
    public function select($bindings)
    {
        $sql = 'SELECT';
        $params = [];

        if (count($bindings['aggregate']) > 0) {
            $sql .= ' ' . $bindings['aggregate']['function'] . '(';
            $sql .= $this->distinct($bindings['distinct']);
            $sql .= $this->columns($bindings['aggregate']['columns']);
            $sql .= ') AS ' . $bindings['aggregate']['function'];
        } else {
            $sql .= $this->distinct($bindings['distinct']);
            if (count($bindings['select']) > 0) {
                $sql .= $this->columns($bindings['select']);
            } else {
                $sql .= ' *';
            }
        }
        if (count($bindings['from']) > 0) {
            $sql .= $this->from($bindings['from']);
        } else {
            throw new QueryException('Table not set.');
        }

        if (count($bindings['join']) > 0) {
            $sql .= $this->join($bindings['join']);
        }

        if (count($bindings['where']) > 0) {
            $sql .= ' WHERE';
            [$whereSQL, $whereParams] = $this->where($bindings['where']);
            $sql .= $whereSQL;
            $params = array_merge($params, $whereParams);
        }

        if (count($bindings['order']) > 0) {
            $sql .= ' ORDER BY';
            $sql .= $this->orderBy($bindings['order']);
        }

        if ($bindings['limit']['limit'] !== null) {
            $sql .= $this->limit($bindings['limit']);
        }

        return [$sql, $params];
    }

    /**
     * @param $bindings
     * @return array
     * @throws QueryException
     */
    public function delete($bindings)
    {
        $sql = 'DELETE FROM';
        $params = [];

        if (count($bindings['from']) == 1) {
            $sql .= ' ' . $this->wrap(current($bindings['from']));
        } else {
            throw new QueryException('Table not set or multiple tables set.');
        }

        if (count($bindings['where']) > 0) {
            $sql .= " WHERE";
            [$whereSQL, $whereParams] = $this->where($bindings['where']);
            $sql .= $whereSQL;
            $params = array_merge($params, $whereParams);
        }

        return [$sql, $params];
    }

    /**
     * @param $bindings
     * @return string
     * @throws QueryException
     */
    public function truncate($bindings)
    {
        $sql = 'TRUNCATE TABLE';

        if (count($bindings['from']) == 1) {
            $sql .= ' ' . $this->wrap(current($bindings['from']));
        } else {
            throw new QueryException('Table not set or multiple tables set.');
        }

        return $sql;
    }

    /**
     * @param $distinctBinding
     * @return string
     */
    protected function distinct($distinctBinding)
    {
        if ($distinctBinding === true) {
            return ' DISTINCT';
        }
        return '';
    }

    /**
     * @param $columnBindings
     * @return string
     */
    protected function columns($columnBindings)
    {
        $sql = ' ' . $this->wrap(current($columnBindings));
        while ($select = next($columnBindings)) {
            $sql .= ', ' . $this->wrap($select);
        }
        return $sql;
    }

    /**
     * @param $fromBindings
     * @return string
     */
    protected function from($fromBindings)
    {
        $sql = ' FROM';
        $sql .= ' ' . $this->wrap(current($fromBindings));
        while ($from = next($fromBindings)) {
            $sql .= ', ' . $this->wrap($from);
        }
        return $sql;
    }

    /**
     * @param $whereBindings
     * @return array
     * TODO: Break this into seperate functions so child classes can override.
     */
    protected function where($whereBindings)
    {
        $sql = ' ';
        $params = [];

        foreach ($whereBindings as $where) {
            //The first boolean will be removed before returning the sql
            $sql .= ' ' . $where['boolean'];

            if ($where['type'] == 'basic') {
                $sql .= ' ' . $this->wrap($where['column']) . ' ' . $where['operator'] . ' ?';
                $params[] = $where['value'];
            } elseif ($where['type'] == 'nested') {
                //recursive function call will give us the conditions
                [$nestedSQL, $nestedParams] = $this->where($where['nested']);
                //If empty where, then don't add it.
                if ($nestedSQL !== ' ') {
                    $sql .= ' (' . $nestedSQL . ')';
                    $params = array_merge($params, $nestedParams);
                } else {
                    //remove the added boolean for this statement.
                    $sql = preg_replace('/ and$| or$/i', '', $sql, 1);
                }
            } elseif ($where['type'] == 'isNull') {
                $sql .= ' ' . $this->wrap($where['column']) . ' IS';
                $sql .= ($where['not']) ? ' NOT NULL ' : ' NULL';
            } elseif ($where['type'] == 'between') {
                $sql .= ' ' . $this->wrap($where['column']);
                $sql .= $where['not'] ? ' NOT' : '';
                $sql .= ' BETWEEN  ? AND ?';
                $params = array_merge($params, $where['value']);
            } elseif ($where['type'] == 'in') {
                $sql .= ' ' . $this->wrap($where['column']);
                $sql .= $where['not'] ? ' NOT IN' : ' IN';
                $sql .= ' (' . implode(',', array_fill(0, count($where['values']), '?')) . ')';
                $params = array_merge($params, $where['values']);
            } elseif ($where['type'] == 'raw') {
                $sql .= ' ' . $where['raw']->getSQL();
                $params = array_merge($params, $where['values']);
            }
        }

        $sql = preg_replace('/ and | or /i', '', $sql, 1); //remove leading boolean and space
        return [$sql, $params];
    }

    /**
     * @param $joinBinding
     * @return string
     */
    protected function join($joinBinding)
    {
        $sql = ' ';

        foreach ($joinBinding as $join) {
            $sql .= $join['joinType'] . ' ' . $this->wrap($join['table']) . ' ON';
            if ($join['type'] == "basic") {
                $sql .= ' ' . $this->wrap($join['condition1']) . ' ' . $join['operator'] . ' ' . $this->wrap($join['condition2']);
            } elseif ($join['type'] == "nested") {
                $onSql = ' (' . $this->on($join['nested']) . ')';
                $sql .= preg_replace('/ and |or / i', '', $onSql, 1);
            }
        }
        return $sql;
    }

    /**
     * @param $onBindings
     * @return string
     */
    protected function on($onBindings)
    {
        $sql = '';

        foreach ($onBindings as $on) {
            $sql .= ' ' . $on['boolean'];
            if ($on['type'] == 'basic') {
                $sql .= ' ' . $this->wrap($on['condition1']) . ' ' . $on['operator'] . ' ' . $this->wrap($on['condition2']);
            } elseif ($on['type'] == 'nested') {
                $onSql = ' (';
                $onSql .= $this->on($on['nested']);
                $onSql .= ')';
                $sql .= preg_replace('/ and |or / i', '', $onSql, 1);
            }
        }
        return $sql;
    }

    /**
     * Order By
     * @param $orderBinding
     * @return string|string[]|null
     */
    protected function orderBy($orderBinding)
    {
        $sql = ' ';
        foreach ($orderBinding as $order) {
            $sql .= ', ' . $this->wrap($order['column']) . ' ' . $order['direction'];
        }
        $sql = preg_replace('/, /i', '', $sql, 1); //remove leading comma
        return $sql;
    }

    /**
     * Set limit and offset for the SQL query.
     * @param $limitBinding
     * @return string
     */
    protected function limit($limitBinding)
    {
        //TODO: Limit is not supported in SQL Server or Oracle. Extend Processor and overwrite this.
        $sql = ' ';
        if (!empty($limitBinding)) {
            $sql .= 'LIMIT ' . $limitBinding['offset'] . ', ' . $limitBinding['limit'];
        }
        return $sql;
    }

    /**
     * Helper function to wrap column names and values.
     * @param $value
     * @return string
     *
     * TODO: Add prefix support for table names
     */
    public function wrap($value)
    {
        if (stripos($value, ' as ') !== false) {
            //Has an alias.
            $values = preg_split('/\s+as\s+/i', $value);
            return $this->wrap($values[0]) . ' as ' . $this->wrapValue($values[1]);
        }

        return $this->wrapSegments(explode('.', $value));
    }

    /**
     * Wrap segments
     * @param $segments
     * @return string
     */
    protected function wrapSegments($segments)
    {
        return implode('.', array_map([$this, 'wrapValue'], $segments));
    }

    /**
     * Wrap and return a value.
     *
     * @param string $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value == '*') {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}