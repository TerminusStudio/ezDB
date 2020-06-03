<?php

namespace TS\ezDB\Query;

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

    public function select($bindings)
    {
        $sql = "SELECT";
        $params = [];

        if (count($bindings['select']) > 0) {
            $sql .= $this->columns($bindings['select']);
        } else {
            $sql .= ' *';
        }

        if (count($bindings['from']) > 0) {
            $sql .= $this->from($bindings['from']);
        } else {
            throw new QueryException('Table not set.');
        }

        if (count($bindings['where']) > 0) {
            $sql .= " WHERE";
            [$whereSQL, $whereParams] = $this->where($bindings['where']);
            $sql .= $whereSQL;
            $params = array_merge($params, $whereParams);
        }

        if ($bindings['limit']['limit'] !== null) {
            $sql .= $this->limit($bindings['limit']);
        }

        return [$sql, $params];
    }

    protected function columns($columnBindings)
    {
        $sql = ' ' . current($columnBindings);
        while ($select = next($columnBindings)) {
            $sql .= ', ' . $select;
        }
        return $sql;
    }

    protected function from($fromBindings)
    {
        $sql = " FROM";
        $sql .= ' `' . current($fromBindings) . '`';
        while ($from = next($fromBindings)) {
            $sql .= ', `' . $from . '`';
        }
        return $sql;
    }

    protected function where($whereBindings)
    {
        $sql = ' ';
        $params = [];

        foreach ($whereBindings as $where) {
            //The first boolean will be removed before returning the sql
            $sql .= $where['boolean'];

            if ($where['type'] == 'basic') {
                $sql .= ' `' . $where['column'] . '` ' . $where['operator'] . ' ?';
                $params[] = $where['value'];
            } elseif ($where['type'] == 'nested') {
                //recursive function call will give us the conditions
                [$nestedSQL, $nestedParams] = $this->where($where['nested']);
                $sql .= ' (' . $nestedSQL . ')';
                $params = array_merge($params, $nestedParams);
            } elseif ($where['type'] == 'isNull') {
                $sql .= ' `' . $where['column'] . '` IS';
                $sql .= ($where['not']) ? 'NOT NULL ' : 'NULL';
            } elseif ($where['type'] == 'between') {
                $sql .= ' `' . $where['column'] . '`';
                $sql .= $where['not'] ? ' NOT ' : '';
                $sql .= 'BETWEEN  ? AND ?';
                $params = array_merge($params, $where['value']);
            }
            //add trailing space
            $sql .= ' ';
        }

        $sql = preg_replace('/and |or /i', '', $sql, 1); //remove leading boolean
        return [$sql, $params];
    }

    protected function join($joinBinding)
    {
        $sql = ' ';
        $params = [];

        foreach ($joinBinding as $join) {
            $sql .= ' JOIN ' . $join['table'] . ' ON ';
            foreach ($join['conditions'] as $condition) {

            }
        }

    }

    protected function joinConditions($conditions)
    {
        $sql = ' ';
        $params = [];
        $addBoolean = false;

    }

    protected function limit($limitBinding)
    {
        //TODO: Limit is not supported in SQL Server or Oracle. Extend Processor and overwrite this.
        $sql = "";
        if (!empty($limitBinding)) {
            $sql .= " LIMIT " . $limitBinding['offset'] . ', ' . $limitBinding['limit'];
        }
        return $sql;
    }

    public function wrap($string)
    {
        return '`' . $string . '`';
    }
}