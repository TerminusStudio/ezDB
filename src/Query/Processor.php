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
        $addBoolean = false;
        $params = [];

        foreach ($whereBindings as $where) {
            if (isset($where['nested'])) {
                $boolean = $where['boolean'];
                if ($addBoolean) {
                    $sql .= $boolean;
                } else {
                    $addBoolean = true;
                }
                $where = $this->where($where['nested']);
                $sql .= ' (' . $where[0] . ') ';
                $params = array_merge($params, $where[1]);
            } else {
                if ($addBoolean) {
                    $sql .= $where['boolean'];
                } else {
                    $addBoolean = true;
                }
                $sql .= ' ' . $where['column'] . ' ' . $where['operator'] . ' ? ';
                $params[] = $where['value'];
            }
        }
        return [$sql, $params];
    }

    public function wrap($string)
    {
        return '`' . $string . '`';
    }
}