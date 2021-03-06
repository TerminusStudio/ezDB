<?php

namespace TS\ezDB\Tests\Query;

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

        $this->assertCount(2, $result);
        $this->assertEquals('INSERT INTO `test` (`name`) VALUES (?)', $result[0]);
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

        $this->assertCount(2, $result);
        $this->assertEquals('UPDATE `test` SET name = ?', $result[0]);
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

        $this->assertCount(2, $result);
        $this->assertEquals('SELECT * FROM `test` WHERE `name` = ? LIMIT 50, 10', trim($result[0]));
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

        $this->assertCount(2, $result);
        $this->assertEquals('DELETE FROM `test` WHERE `name` = ?', trim($result[0]));
    }

    public function testTruncate()
    {
        $bindings = [
            'from' => ['test']
        ];
        $result = $this->processor->truncate($bindings);

        $this->assertIsString($result);
        $this->assertEquals('TRUNCATE TABLE `test`', trim($result));
    }
}
