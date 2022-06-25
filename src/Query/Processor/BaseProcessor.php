<?php

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Exceptions\ProcessorException;
use TS\ezDB\Query\Builder\IAggregateQuery;
use TS\ezDB\Query\Builder\IBuilder;
use TS\ezDB\Query\Builder\IBuilderInfo;
use TS\ezDB\Query\Builder\QueryBuilderType;
use TS\ezDB\Query\DefaultQuery;
use TS\ezDB\Query\IQuery;

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
        throw new ProcessorException("Invalid Query Type");
    }

    protected function processQuery(ProcessorContext $context): IQuery
    {
        switch ($context->getBuilder()->getType()) {
            case QueryBuilderType::Select:
                return $this->buildSelectQuery($context);
            case QueryBuilderType::Insert:
                return $this->buildInsertQuery($context);
            case QueryBuilderType::Update:
                return $this->buildUpdateQuery($context);
        }

        throw new ProcessorException("Query builder type is not supported");
    }

    protected function buildInsertQuery(ProcessorContext $context): IQuery
    {
        $fromClauses = $context->getClauses('from');

        if (count($fromClauses) != 1) {
            throw new ProcessorException('Table not set or multiple tables set.');
        }

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
                    throw new ProcessorException("Insert values does not match original columns. Please insert it as a separate query");
                }
                $finalValueString .= ', (' . $this->addParameters($context, array_values($insertClause)) . ')';
            }
        }


        $sql = $this->joinSqlParts(
            "INSERT INTO",
            $fromClauses[0],
            '(' . $this->buildCommaSeperatedList($columns, wrap: true) . ')',
            'VALUES',
            $finalValueString
        );

        return new DefaultQuery($sql, $context->getBindings());
    }

    protected function buildUpdateQuery(ProcessorContext $context): IQuery
    {
        return new DefaultQuery("", $context->getBindings());
    }

    protected function buildSelectQuery(ProcessorContext $context): IQuery
    {
        return new DefaultQuery("", $context->getBindings());
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

    protected function buildCommaSeperatedList(array &$str, bool $wrap = false)
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
        return implode(" ", $str);
    }
}