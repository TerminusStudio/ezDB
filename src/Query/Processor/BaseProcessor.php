<?php

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Exceptions\ProcessorException;
use TS\ezDB\Query\Builder\IAggregateQuery;
use TS\ezDB\Query\Builder\IBuilder;
use TS\ezDB\Query\Builder\IBuilderInfo;
use TS\ezDB\Query\Builder\QueryType;
use TS\ezDB\Query\DefaultQuery;
use TS\ezDB\Query\IQuery;
use TS\ezDB\Query\Raw;

class BaseProcessor implements IProcessor
{
    public function process(IBuilderInfo $builderInfo): IQuery
    {
        if ($builderInfo instanceof IAggregateQuery) {
            $ctx = new ProcessorContext($builderInfo->getParent(), isAggregateQuery: true);
            return $this->processQuery($ctx);
        } else if ($builderInfo instanceof IBuilder) {
            $ctx = new ProcessorContext($builderInfo);
            return $this->processQuery($ctx);
        }
        throw new ProcessorException('Invalid Query Type.');
    }

    protected function processQuery(ProcessorContext $context): IQuery
    {
        if ($context->isAggregateQuery())
            return $this->buildAggregateQuery($context);

        switch ($context->getBuilder()->getType()) {
            case QueryType::Select:
                return $this->buildSelectQuery($context);
            case QueryType::Insert:
                return $this->buildInsertQuery($context);
            case QueryType::Update:
                return $this->buildUpdateQuery($context);
            case QueryType::Delete:
                return $this->buildDeleteQuery($context);
            case QueryType::Truncate:
                return $this->buildTruncateQuery($context);
        }

        throw new ProcessorException('Query builder type is not supported.');
    }

    protected function buildInsertQuery(ProcessorContext $context): IQuery
    {
        $table = $this->processSingleTable($context);

        $insertClauses = $context->getClauses('insert');

        if (count($insertClauses) == 0) {
            throw new ProcessorException('No data to insert.');
        }

        $insertClause = current($insertClauses);
        $columns = array_keys($insertClause);
        //returns ?, ?, ?

        $finalValueString = '(' . $this->addParameters($context, array_values($insertClause)) . ')';

        if (count($insertClauses) > 1) {
            while ($insertClause = next($bindings['insert'])) {
                if (!count($insertClause) == count($columns)) {
                    throw new ProcessorException('Insert values does not match original columns. Please insert it as a separate query');
                }
                $finalValueString .= ', (' . $this->addParameters($context, array_values($insertClause)) . ')';
            }
        }


        $sql = $this->joinSqlParts(
            'INSERT INTO',
            $table,
            '(' . $this->buildCommaSeperatedList($columns, wrap: true) . ')',
            'VALUES',
            $finalValueString
        );

        return new DefaultQuery(QueryType::Insert, $sql, $context->getBindings());
    }

    protected function buildUpdateQuery(ProcessorContext $context): IQuery
    {
        $table = $this->processSingleTable($context);
        $updateClauses = $context->getClauses('update');

        if (count($updateClauses) == 0) {
            throw new ProcessorException('No data to update.');
        }

        $updateClause = current($updateClauses);

        $finalValueString = $this->wrap($updateClause['column']) . ' = ' . $this->addParameter($context, $updateClause['value']);

        while ($updateClause = next($updateClauses)) {
            $finalValueString .= ', ' . $this->wrap($updateClause['column']) . ' = ' . $this->addParameter($context, $updateClause['value']);
        }

        $sql = $this->joinSqlParts(
            'UPDATE',
            $table,
            'SET',
            $finalValueString
        );

        return new DefaultQuery(QueryType::Update, $sql, $context->getBindings());
    }

    protected function buildSelectQuery(ProcessorContext $context): IQuery
    {
        $sql = $this->joinSqlParts(
            $this->processColumns($context),
            $this->processFrom($context),
            $this->processWhere($context),
            $this->processGroupBy($context),
            $this->processHaving($context),
            $this->processOrderBy($context),
            $this->processLimit($context)
        );
        return new DefaultQuery(QueryType::Select, $sql, $context->getBindings());
    }

    protected function buildAggregateQuery(ProcessorContext $context): IQuery
    {
        $sql = $this->joinSqlParts(
            $this->processAggregateFunction($context),
            $this->processFrom($context),
            $this->processWhere($context)
        );
        return new DefaultQuery(QueryType::Select, $sql, $context->getBindings());
    }

    protected function buildDeleteQuery(ProcessorContext $context): IQuery
    {
        return new DefaultQuery(QueryType::Delete, '', $context->getBindings());
    }

    protected function buildTruncateQuery(ProcessorContext $context): IQuery
    {
        $table = $this->processSingleTable($context);
        $sql = $this->joinSqlParts(
            'TRUNCATE TABLE',
            $table
        );
        return new DefaultQuery(QueryType::Truncate, $sql, $context->getBindings());
    }

    protected function getTables(ProcessorContext $context, int $requiredCount = 0): array
    {
        $fromClauses = $context->getClauses('from');

        if ($requiredCount > 0 && count($fromClauses) != $requiredCount) {
            $count = count($fromClauses);
            throw new ProcessorException("Expected $requiredCount tables but found $count");
        }

        return $fromClauses;
    }

    protected function processSingleTable(ProcessorContext $context): string
    {
        return $this->wrap($this->getTables($context, 1)[0]);
    }

    protected function processTable(ProcessorContext $context, int $requiredCount = 0): string
    {
        $fromClauses = $this->getTables($context, $requiredCount);
        $count = count($fromClauses);
        if ($count == 0) {
            return '';
        }
        return $this->buildCommaSeperatedList($fromClauses, true);
    }

    protected function processFrom(ProcessorContext $context): string
    {
        return 'FROM ' . $this->processTable($context);
    }

    protected function processColumns(ProcessorContext $context): string
    {
        $selectClauses = $context->getClauses('select');
        $count = count($selectClauses);
        $sql = 'SELECT ';
        if (count($distinct = $context->getClauses('distinct')) > 0 && $distinct[0] === true) {
            $sql .= 'DISTINCT ';
        }
        if ($count == 0) {
            return $sql . '*';
        }
        return $sql . $this->buildCommaSeperatedList($selectClauses, true);
    }

    protected function processAggregateFunction(ProcessorContext $context): string
    {
        $aggregateClauses = $context->getClauses('aggregate');
        $count = count($aggregateClauses);
        if ($count != 1) {
            throw new ProcessorException("Excepted to find 1 aggregate clause. Found $count instead");
        }
        $clause = $aggregateClauses[0];

        return 'SELECT ' . $clause['function'] . '(' . $this->buildCommaSeperatedList($clause['columns'], true) . ') AS ' . $this->wrap($clause['alias']);
    }

    protected function processWhere(ProcessorContext $context): string
    {
        $whereClauses = $context->getClauses('where');

        if (empty($whereClauses))
            return '';

        return 'WHERE ' . $this->processWhereConditions($context, $whereClauses);
    }

    protected function processWhereConditions(ProcessorContext $context, array $whereClauses): string
    {
        $sql = '';

        for ($i = 0; $i < count($whereClauses); $i++) {
            $where = $whereClauses[$i];

            if ($i != 0) {
                $boolean = $where['boolean'] === 'OR' ? 'OR' : 'AND';
                $sql .= ' ' . $boolean . ' ';
            }
            if ($where['type'] == 'basic') {
                $sql .= $this->processWhereBasic($context, $where);
            } elseif ($where['type'] == 'nested') {
                $sql .= $this->processWhereNested($context, $where);
            } elseif ($where['type'] == 'null') {
                $sql .= $this->processWhereNull($context, $where);
            } elseif ($where['type'] == 'between') {
                $sql .= $this->processWhereBetween($context, $where);
            } elseif ($where['type'] == 'in') {
                $sql .= $this->processWhereIn($context, $where);
            } elseif ($where['type'] == 'raw') {
                $sql .= $this->processWhereRaw($context, $where);
            } else {
                throw new ProcessorException('Unexpected type found for where clause: ' . $where['type']);
            }
        }

        return $sql;
    }

    protected function processWhereBasic(ProcessorContext $context, array $whereClause): string
    {
        return $this->wrap($whereClause['column']) . ' ' . $whereClause['operator'] . ' ' . $this->addParameter($context, $whereClause['value']);
    }

    protected function processWhereNested(ProcessorContext $context, array $whereClause): string
    {
        //recursive function call will give us the conditions
        $nestedSQL = $this->processWhereConditions($context, $whereClause['nested']);
        //If empty where, then don't add it.
        if ($nestedSQL !== '') {
            return ' (' . $nestedSQL . ')';
        } else {
            return '';
        }
    }

    protected function processWhereNull(ProcessorContext $context, array $whereClause): string
    {
        $null = ($whereClause['not']) ? 'NOT NULL ' : 'NULL';
        return $this->wrap($whereClause['column']) . ' IS ' . $null;
    }

    protected function processWhereBetween(ProcessorContext $context, array $whereClause): string
    {
        $sql = $this->wrap($whereClause['column']);
        $sql .= $whereClause['not'] ? ' NOT' : '';
        $sql .= ' BETWEEN ' . $this->addParameter($context, $whereClause['value1']) . ' AND ' . $this->addParameter($context, $whereClause['value2']);
        return $sql;
    }

    protected function processWhereIn(ProcessorContext $context, array $whereClause): string
    {
        $sql = $this->wrap($whereClause['column']) . ($whereClause['not'] ? ' NOT IN' : ' IN') . '(';
        $count = count($whereClause['values']);
        for ($i = 0; $i < $count; $i++) {
            $sql .= $this->addParameter($context, $whereClause['values'][$i]);
            if ($i != $count - 1) {
                $sql .= ', '; //Don't add for the last one.
            }
        }
        return $sql;
    }

    protected function processWhereRaw(ProcessorContext $context, array $whereClause): string
    {
        $raw = $whereClause['raw'];
        if ($raw instanceof Raw)
            return $raw->getSQL();
        throw new ProcessorException('Unexpected value. Expected instance of Raw');
    }

    protected function processHaving(ProcessorContext $context): string
    {
        $havingClauses = $context->getClauses('having');

        if (empty($havingClauses))
            return '';

        return 'HAVING ' . $this->processHavingConditions($context, $havingClauses);
    }

    protected function processHavingConditions(ProcessorContext $context, array $havingClauses): string
    {
        $sql = '';

        for ($i = 0; $i < count($havingClauses); $i++) {
            $havingClause = $havingClauses[$i];

            if ($i != 0) {
                $boolean = $havingClause['boolean'] === 'OR' ? 'OR' : 'AND';
                $sql .= ' ' . $boolean . ' ';
            }
            //Reuse where methods where possible
            if ($havingClause['type'] == 'basic') {
                $sql .= $this->processWhereBasic($context, $havingClause);
            } elseif ($havingClause['type'] == 'raw') {
                $sql .= $this->processWhereRaw($context, $havingClause);
            } else {
                throw new ProcessorException('Unexpected type found for having clause: ' . $havingClause['type']);
            }
        }

        return $sql;
    }

    protected function processGroupBy(ProcessorContext $context): string
    {
        $groupClauses = $context->getClauses('group');
        if (empty($groupClauses)) {
            return '';
        }

        $count = count($groupClauses);
        $sql = '';
        for ($i = 0; $i < $count; $i++) {
            $group = $groupClauses[$i];

            if (isset($group['raw'])) {
                $sql .= trim($group['raw']);
            } else {
                $sql .= $this->buildCommaSeperatedList($group['columns']
                    ?? throw new ProcessorException("Unexpected error in groupBy clause"),
                    true);
            }

            if ($i != $count - 1)
                $sql .= ', ';
        }
        return 'GROUP BY ' . $sql;
    }


    protected function processOrderBy(ProcessorContext $context): string
    {
        $orderClauses = $context->getClauses('order');
        if (empty($orderClauses)) {
            return '';
        }

        $count = count($orderClauses);
        $sql = '';
        for ($i = 0; $i < $count; $i++) {
            if (isset($orderClauses[$i]['raw'])) {
                $sql .= trim($orderClauses[$i]['raw']);
            } else {
                $sql .= $this->wrap($orderClauses[$i]['column']) . ' ' . $orderClauses[$i]['direction'];
            }
            if ($i != $count - 1)
                $sql .= ', ';
        }
        return 'ORDER BY ' . $sql;
    }

    protected function processLimit(ProcessorContext $context): string
    {
        $limitClause = $context->getClauses('limit');
        $offsetClause = $context->getClauses('offset');
        if (empty($limitClause) && empty($offsetClause)) {
            return '';
        }
        $limit = $limitClause[0] ?? null;
        $offset = $offsetClause[0] ?? null;

        $sql = 'LIMIT ';
        $sql .= ($limit !== null) ? $limit : '18446744073709551610';
        $sql .= ($offset !== null) ? ' OFFSET ' . $offset : '';
        return $sql;
    }

    protected function addParameter(ProcessorContext $context, object|string|int|bool|float $value): string
    {
        $context->addBinding($value);
        return '?';
    }

    protected function addParameters(ProcessorContext $context, array $values): string
    {
        $str = [];
        foreach ($values as $value) {
            $str[] = $this->addParameter($context, $value);
        }
        return $this->buildCommaSeperatedList($str);
    }

    /**
     * Helper function to wrap column names and values.
     * @param $value
     * @return string
     *
     * TODO: Add prefix support for table names
     */
    protected function wrap($value)
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
        return '`' . $value . '`';
        return '"' . str_replace('"', '""', $value) . '"';
    }

    protected function buildCommaSeperatedList(array $str, bool $wrap = false)
    {
        reset($str);
        $sql = $wrap ? $this->wrap(current($str)) : current($str);
        while ($from = next($str)) {
            $sql .= ', ' . ($wrap ? $this->wrap($from) : $from);
        }
        return $sql;
    }

    protected function joinSqlParts(string ...$str)
    {
        return implode(' ', $str);
    }
}