<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Query\Processor;

use TS\ezDB\Query\Processor\Processor;
use TS\ezDB\Tests\TestCase;

class ProcessorTest extends TestCase
{
    protected $processor;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new Processor();
    }

    public function testInsert()
    {
        $bindings = [
            'from' => ['test'],
            'insert' => [['name' => 'ezDB']],
        ];
        $result = $this->processor->insert($bindings);
        $expected = sprintf(
            'INSERT INTO %s (%s) VALUES (?)',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result[0]);
        $this->assertCount(1, $result[1]);
        $this->assertEquals('ezDB', $result[1][0]);
    }

    public function testUpdate()
    {
        $bindings = [
            'from' => ['test'],
            'update' => [['column' => 'name', 'value' => 'ezDB']],
            'where' => []
        ];
        $result = $this->processor->update($bindings);
        $expected = sprintf(
            'UPDATE %s SET %s = ?',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result[0]);
        $this->assertEquals('ezDB', $result[1][0]);
    }

    public function testSelect()
    {
        $bindings = [
            'aggregate' => [],
            'select' => [],
            'from' => ['test'],
            'join' => [],
            'where' => [
                ['column' => 'name', 'operator' => '=', 'value' => 'ezDB', 'boolean' => 'AND', 'type' => 'basic']
            ],
            'order' => [],
            'limit' => ['limit' => 10, 'offset' => 50],
            'distinct' => false
        ];
        $result = $this->processor->select($bindings);
        $expected = sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 50, 10',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertCount(2, $result);
        $this->assertEquals($expected, trim($result[0]));
        $this->assertEquals('ezDB', $result[1][0]);
    }

    public function testDelete()
    {
        $bindings = [
            'from' => ['test'],
            'where' => [
                ['column' => 'name', 'operator' => '=', 'value' => 'ezDB', 'boolean' => 'AND', 'type' => 'basic']
            ]
        ];

        $result = $this->processor->delete($bindings);
        $expected = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->processor->wrap('test'),
            $this->processor->wrap('name')
        );

        $this->assertCount(2, $result);
        $this->assertEquals($expected, trim($result[0]));
    }

    public function testTruncate()
    {
        $bindings = [
            'from' => ['test']
        ];
        $result = $this->processor->truncate($bindings);
        $expected = sprintf(
            'TRUNCATE TABLE %s',
            $this->processor->wrap('test')
        );

        $this->assertIsString($result);
        $this->assertEquals($expected, trim($result));
    }

    public function testWrap()
    {
        $result = $this->processor->wrap('test');
        $this->assertEquals('"test"', $result);

        $result = $this->processor->wrap('test.name');
        $this->assertEquals('"test"."name"', $result);

        $result = $this->processor->wrap('test as t');
        $this->assertEquals('"test" as "t"', $result);
    }
}
