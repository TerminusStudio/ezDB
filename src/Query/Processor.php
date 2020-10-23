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

        if (count($bindings['join']) > 0) {
            $sql .= $this->join($bindings['join']);
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
        $sql = ' ' . $this->wrap(current($columnBindings));
        while ($select = next($columnBindings)) {
            $sql .= ', ' . $this->wrap($select);
        }
        return $sql;
    }

    protected function from($fromBindings)
    {
        $sql = " FROM";
        $sql .= ' ' . $this->wrap(current($fromBindings));
        while ($from = next($fromBindings)) {
            $sql .= ', ' . $this->wrap($from);
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
                $sql .= ' ' . $this->wrap($where['column']) . ' ' . $where['operator'] . ' ?';
                $params[] = $where['value'];
            } elseif ($where['type'] == 'nested') {
                //recursive function call will give us the conditions
                [$nestedSQL, $nestedParams] = $this->where($where['nested']);
                $sql .= ' (' . $nestedSQL . ')';
                $params = array_merge($params, $nestedParams);
            } elseif ($where['type'] == 'isNull') {
                $sql .= ' ' .  $this->wrap($where['column']) . ' IS';
                $sql .= ($where['not']) ? 'NOT NULL ' : 'NULL';
            } elseif ($where['type'] == 'between') {
                $sql .= ' ' . $this->wrap($where['column']);
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

        foreach ($joinBinding as $join) {
            $sql .= $join['joinType'] . ' ' . $this->wrap($join['table']) . ' ON ';

            if ($join['type'] == "basic") {
                $sql .= ' ' . $this->wrap($join['condition1']) . ' ' . $join['operator'] . ' '. $this->wrap($join['condition2']) .' ';
            } elseif ($join['type'] == "nested") {
                $onSql = '';
                foreach ($join['nested'] as $on) {
                    $onSql .= $on['boolean'];
                    $onSql .= ' ' . $this->wrap($on['condition1']) . ' ' . $on['operator'] . ' ' . $this->wrap($on['condition2']) . ' ';
                }
                $sql .= preg_replace('/and |or /i', '', $onSql, 1);
            }
        }
        return $sql;
    }

    protected function limit($limitBinding)
    {
        //TODO: Limit is not supported in SQL Server or Oracle. Extend Processor and overwrite this.
        $sql = "";
        if (!empty($limitBinding)) {
            $sql .= 'LIMIT ' . $limitBinding['offset'] . ', ' . $limitBinding['limit'] . ' ';
        }
        return $sql;
    }


    public function wrap($value)
    {
        if(stripos($value, ' AS ') !== FALSE) {
            //Has an alias.
            $values = preg_split('/\s+as\s+/i', $value);
            return $this->wrap($values[0]) . " as " . $this->wrap($values[1]);
        }

        return implode('.', array_map(function($value) {
            return ($value == '*') ? $value :  '`' . $value . '`';
        }, explode('.', $value)));


    }
}