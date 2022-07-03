<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Query\Processor;

use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Builder\QueryType;
use TS\ezDB\Query\DefaultQuery;
use TS\ezDB\Query\Processor\IProcessor;
use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Tests\Mock\MockBuilder;
use TS\ezDB\Tests\TestCase;

class ProcessorTest extends TestCase
{
    protected IProcessor $processor;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new MySQLProcessor();
    }

    public function testInsert()
    {
        $builder = new MockBuilder();
        $builder->setType(QueryType::Insert);
        $builder->replaceClauses([
            'from' => ['test'],
            'insert' => [['name' => 'ezDB']],
        ]);

        $result = $this->processor->process($builder);
        $expected = sprintf(
            'INSERT INTO %s (%s) VALUES (?)',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertInstanceOf(DefaultQuery::class, $result);
        $this->assertEquals($expected, $result->rawSql);
        $this->assertCount(1, $result->getBindings());
        $this->assertEquals('ezDB', $result->getBindings()[0]);
    }

    public function testUpdate()
    {
        $builder = new MockBuilder();
        $builder->setType(QueryType::Update);
        $builder->replaceClauses([
            'from' => ['test'],
            'update' => [['column' => 'name', 'value' => 'ezDB']],
            'where' => []
        ]);

        $result = $this->processor->process($builder);
        $expected = sprintf(
            'UPDATE %s SET %s = ?',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertInstanceOf(DefaultQuery::class, $result);
        $this->assertEquals($expected, $result->rawSql);
        $this->assertEquals('ezDB', $result->getBindings()[0]);
    }

    public function testSelect()
    {
        $builder = new MockBuilder();
        $builder->setType(QueryType::Select);
        $builder->replaceClauses([
            'aggregate' => [],
            'select' => [],
            'from' => ['test'],
            'join' => [],
            'where' => [
                ['column' => 'name', 'operator' => '=', 'value' => 'ezDB', 'boolean' => 'AND', 'type' => 'basic']
            ],
            'order' => [],
            'limit' => [50],
            'offset' => [10],
            'distinct' => [false]
        ]);

        $result = $this->processor->process($builder);
        $expected = sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 50 OFFSET 10',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertInstanceOf(DefaultQuery::class, $result);
        $this->assertEquals($expected, $result->rawSql);
        $this->assertEquals('ezDB', $result->getBindings()[0]);
    }

    public function testDelete()
    {
        $builder = new MockBuilder();
        $builder->setType(QueryType::Delete);
        $builder->replaceClauses([
            'from' => ['test'],
            'where' => [
                ['column' => 'name', 'operator' => '=', 'value' => 'ezDB', 'boolean' => 'AND', 'type' => 'basic']
            ]
        ]);

        $result = $this->processor->process($builder);
        $expected = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertInstanceOf(DefaultQuery::class, $result);
        $this->assertEquals($expected, $result->rawSql);
        $this->assertEquals('ezDB', $result->getBindings()[0]);
    }

    public function testTruncate()
    {
        $builder = new MockBuilder();
        $builder->setType(QueryType::Truncate);
        $builder->replaceClauses([
            'from' => ['test']
        ]);
        $result = $this->processor->process($builder);
        $expected = sprintf(
            'TRUNCATE TABLE %s',
            $this->processor->wrap('test')
        );

        $this->assertInstanceOf(DefaultQuery::class, $result);
        $this->assertEquals($expected, $result->rawSql);
    }
}
