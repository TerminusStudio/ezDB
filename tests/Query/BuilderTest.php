<?php

namespace TS\ezDB\Tests\Query;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Tests\TestCase;

class BuilderTest extends TestCase
{
    protected $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new Builder(Connections::connection('Connection1'));
    }

    /**
     * TODO: hasMode, setModel
     */

    public function testConnection()
    {
        $connection = $this->builder->getConnection();

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testTable()
    {
        $this->builder->table('test');
        $bindings = $this->builder->getBindings('from');

        $this->assertNotEmpty($bindings);
        $this->assertContains('test', $bindings);
    }

    public function testInsert()
    {
        $result = $this->builder->table('test')->insert(['name' => 'ezDB']);
        $bindings = $this->builder->getBindings('insert');

        $this->assertEquals(1, $result);
        $this->assertNotEmpty($bindings);
    }

    public function testInsert2D()
    {
        //Test 2d array insert
        $result = $this->builder->table('test')->insert([['name' => 'ezDB1'], ['name' => 'ezDB2']]);
        $bindings = $this->builder->getBindings('insert');

        $this->assertEquals(2, $result);
        $this->assertNotEmpty($bindings);
        $this->assertCount(2, $bindings);
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testUpdate()
    {
        $result = $this->builder->table('test')->update(['updated_at' => date("Y-m-d H:i:s")]);
        $this->assertEquals(3, $result);
    }

    public function testJoin()
    {
        $this->builder->join('test_join', 'test.id', '=', 'test_join.test_id');
        $bindings = $this->builder->getBindings('join');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('test_join', $bindings[0]['table']);
        $this->assertEquals('test.id', $bindings[0]['condition1']);
        $this->assertEquals('=', $bindings[0]['operator']);
        $this->assertEquals('test_join.test_id', $bindings[0]['condition2']);
        $this->assertEquals('INNER JOIN', $bindings[0]['joinType']);
        $this->assertEquals('basic', $bindings[0]['type']);
    }

    public function testJoinNested()
    {
        $this->builder->join('test_join', function ($q) {
            $q->on('test.id', '=', 'test_join.test_id')
                ->on('test.other', '=', 'test_join.test_other');
        }, 'LEFT JOIN');
        $bindings = $this->builder->getBindings('join');

        $this->assertEquals('test_join', $bindings[0]['table']);
        $this->assertEquals('LEFT JOIN', $bindings[0]['joinType']);
        $this->assertEquals('nested', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['nested']);

        //These asserts Join Builder
        $this->assertCount(2, $bindings[0]['nested']);
        $this->assertEquals('test.id', $bindings[0]['nested'][0]['condition1']);
        $this->assertEquals('=', $bindings[0]['nested'][0]['operator']);
        $this->assertEquals('test_join.test_id', $bindings[0]['nested'][0]['condition2']);
        $this->assertEquals('AND', $bindings[0]['nested'][0]['boolean']);
    }

    public function testWhere()
    {
        $this->builder->where('name', '=', 'ezDB', 'AND');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('name', $bindings[0]['column']);
        $this->assertEquals('=', $bindings[0]['operator']);
        $this->assertEquals('ezDB', $bindings[0]['value']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals('basic', $bindings[0]['type']);
    }

    public function testWhereEquals()
    {
        $this->builder->where('name', 'ezDB');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('name', $bindings[0]['column']);
        $this->assertEquals('=', $bindings[0]['operator']);
        $this->assertEquals('ezDB', $bindings[0]['value']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals('basic', $bindings[0]['type']);
    }

    public function testWhereMultiple()
    {
        $this->builder->where([['name', 'ezDB'], ['name', 'ezDB1']]);
        $bindings = $this->builder->getBindings('where');

        $this->assertCount(2, $bindings);
    }

    public function testWhereNested()
    {
        $this->builder->where(function ($q) {
            $q->where('name', 'ezDB')
                ->whereNull('created_at');
        });
        $bindings = $this->builder->getBindings('where');

        $this->assertCount(1, $bindings);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals('nested', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['nested']);
        $this->assertCount(2, $bindings[0]['nested']);
    }

    public function testWhereValueNull()
    {
        $this->expectException(QueryException::class);
        $this->builder->where('name', null);
    }

    public function testOrWhere()
    {
        $this->builder->orWhere('name', '=', 'ezDB');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('name', $bindings[0]['column']);
        $this->assertEquals('=', $bindings[0]['operator']);
        $this->assertEquals('ezDB', $bindings[0]['value']);
        $this->assertEquals('OR', strtoupper($bindings[0]['boolean']));
        $this->assertEquals('basic', $bindings[0]['type']);
    }

    public function testWhereNull()
    {
        $this->builder->whereNull('created_at');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('created_at', $bindings[0]['column']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(false, $bindings[0]['not']);
        $this->assertEquals('isNull', $bindings[0]['type']);
    }

    public function testWhereNotNull()
    {
        $this->builder->whereNotNull('created_at', 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('created_at', $bindings[0]['column']);
        $this->assertEquals('OR', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(true, $bindings[0]['not']);
        $this->assertEquals('isNull', $bindings[0]['type']);
    }

    public function testWhereBetween()
    {
        $this->builder->whereBetween('id', [11, 15]);
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('between', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['value']);
        $this->assertCount(2, $bindings[0]['value']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(false, $bindings[0]['not']);
    }

    public function testWhereNotBetween()
    {
        $this->builder->whereNotBetween('id', [11, 15], 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('between', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['value']);
        $this->assertCount(2, $bindings[0]['value']);
        $this->assertEquals('OR', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(true, $bindings[0]['not']);
    }

    public function testWhereIn()
    {
        $this->builder->whereIn('id', [11, 12, 13]);
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('in', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['values']);
        $this->assertCount(3, $bindings[0]['values']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(false, $bindings[0]['not']);
    }

    public function testWhereNotIn()
    {
        $this->builder->whereNotIn('id', [11, 12, 13], 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('in', $bindings[0]['type']);
        $this->assertIsArray($bindings[0]['values']);
        $this->assertCount(3, $bindings[0]['values']);
        $this->assertEquals('OR', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(true, $bindings[0]['not']);
    }

    public function testInvalidOperator()
    {
        $this->expectException(QueryException::class);
        $this->builder->where('id', '<>>', '1');
    }

    public function testOrderBy()
    {
        $this->builder->orderBy('id', 'DESC');
        $bindings = $this->builder->getBindings('order');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('desc', $bindings[0]['direction']);
    }

    public function testLimit()
    {
        $this->builder->limit(10);
        $bindings = $this->builder->getBindings('limit');

        $this->assertEquals(10, $bindings['limit']);
    }

    public function testLimitAndOffset()
    {
        $this->builder->limit(10, 50);
        $bindings = $this->builder->getBindings('limit');

        $this->assertEquals(10, $bindings['limit']);
        $this->assertEquals(50, $bindings['offset']);
    }

    public function testOffset()
    {
        $this->builder->offset(50);
        $bindings = $this->builder->getBindings('limit');

        $this->assertEquals(50, $bindings['offset']);
    }

    public function testSet()
    {
        $this->builder->set('name', 'ezDB');
        $bindings = $this->builder->getBindings('update');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('name', $bindings[0]['column']);
        $this->assertEquals('ezDB', $bindings[0]['value']);
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testDistinct()
    {
        $results = $this->builder->table('test')->distinct()->get('name');
        $this->assertCount(3, $results);

        $this->setUp();
        $results = $this->builder->table('test')->distinct()->get('created_at');
        $this->assertCount(1, $results);
    }

    public function testWith()
    {
        $this->expectException(ModelMethodException::class);
        $this->builder->with('test')->get();
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testGet()
    {
        $results = $this->builder->table('test')->get();

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertCount(4, get_object_vars($results[0]));
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testGetSingleColumn()
    {
        $results = $this->builder->table('test')->get('id');

        $this->assertIsArray($results);
        $this->assertCount(1, get_object_vars($results[0])); //column count
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testFirst()
    {
        $results = $this->builder
            ->table('test')
            ->where('name', 'ezDB')
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertIsNotArray($results);
        $this->assertObjectHasAttribute('id', $results);
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testAggregate()
    {
        $this->builder->table('test');

        //Each aggregate makes a clone of builder. If that changes in the future, need to rewrite these.
        $count = $this->builder->count();
        $min = $this->builder->min('id');
        $max = $this->builder->max('id');
        $sum = $this->builder->sum('id');
        $avg = $this->builder->avg('id');

        $this->assertEquals(3, $count);
        $this->assertGreaterThan($min, $max);
        $this->assertEquals(($sum / $count), $avg);
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $result = $this->builder->table('test')->where('name', 'ezDB')->delete();

        $this->assertEquals(1, $result);
    }

    public function testDeleteAll()
    {
        $this->expectException(QueryException::class);
        $this->builder->table('test')->delete();
    }

    /**
     * @depends testInsert2D
     */
    public function testTruncate()
    {
        $result = $this->builder->table('test')->truncate();

        $this->assertTrue($result);
    }
}