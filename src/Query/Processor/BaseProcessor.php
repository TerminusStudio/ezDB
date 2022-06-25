<?php

namespace TS\ezDB\Query\Processor;

use TS\ezDB\Exceptions\ProcessorException;
use TS\ezDB\Query\Builder\IAggregateQuery;
use TS\ezDB\Query\Builder\IBuilder;
use TS\ezDB\Query\Builder\IBuilderInfo;
use TS\ezDB\Query\Builder\QueryType;
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

        throw new ProcessorException("Query builder type is not supported");
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
                    throw new ProcessorException("Insert values does not match original columns. Please insert it as a separate query");
                }
                $finalValueString .= ', (' . $this->addParameters($context, array_values($insertClause)) . ')';
            }
        }


        $sql = $this->joinSqlParts(
            "INSERT INTO",
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
            $finalValueString .= ", " . $this->wrap($updateClause['column']) . ' = ' . $this->addParameter($context, $updateClause['value']);
        }

        $sql = $this->joinSqlParts(
            "UPDATE",
            $table,
            'SET',
            $finalValueString
        );

        return new DefaultQuery(QueryType::Update, $sql, $context->getBindings());
    }

    protected function buildSelectQuery(ProcessorContext $context): IQuery
    {
        return new DefaultQuery(QueryType::Select, "", $context->getBindings());
    }

    protected function buildDeleteQuery(ProcessorContext $context): IQuery
    {
        return new DefaultQuery(QueryType::Delete, "", $context->getBindings());
    }

    protected function buildTruncateQuery(ProcessorContext $context): IQuery
    {
        $table = $this->processSingleTable($context);
        $sql = $this->joinSqlParts(
            "TRUNCATE TABLE",
            $table
        );
        return new DefaultQuery(QueryType::Truncate, $sql, $context->getBindings());
    }

    protected function getTables(ProcessorContext $context, int $requiredCount = 1): array
    {
        $fromClauses = $context->getClauses('from');

        if (count($fromClauses) != 1) {
            $count = count($fromClauses);
            throw new ProcessorException("Expected $requiredCount tables but found $count");
        }

        return $fromClauses;
    }

    protected function processSingleTable(ProcessorContext $context): string
    {
        return $this->wrap($this->getTables($context, 1)[0]);
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