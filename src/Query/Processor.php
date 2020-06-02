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
            reset($bindings['select']);
            $sql .= ' ' . current($bindings['select']);
            while ($select = next($bindings['select'])) {
                $sql .= ', ' . $select;
            }
        } else {
            $sql .= ' *';
        }


        $sql .= " FROM";

        if (count($bindings['from']) > 0) {
            reset($bindings['from']);
            $sql .= ' `' . current($bindings['from']) . '`';
            while ($select = next($bindings['from'])) {
                $sql .= ', `' . $select . '`';
            }
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

    public function where($whereBindings)
    {
        $sql = ' ';
        $boolean = '';
        $params = [];

        foreach ($whereBindings as $where) {
            if (is_array($where[0])) {
                $sql .= $boolean;

                $boolean = array_pop($where); //The last element in a nested where statement is the boolean

                $where = $this->where($where);
                $sql .= ' (' . $where[0] . ') ';
                $params = array_merge($params, $where[1]);
            } else {
                $sql .= $boolean;
                $sql .= ' ' . $where[0] . ' ' . $where[1] . ' ? ';
                $boolean = $where[3];
                $params[] = $where[2];
            }
        }
        return [$sql, $params];
    }

    public function wrap($string)
    {
        return '`' . $string . '`';
    }
}