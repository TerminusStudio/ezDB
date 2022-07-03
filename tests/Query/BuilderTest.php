<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Query;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\DB;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Query\Builder\IAggregateQuery;
use TS\ezDB\Query\Builder\QueryType;
use TS\ezDB\Query\Raw;
use TS\ezDB\Tests\TestCase;
use Webmozart\Assert\Assert;

class BuilderTest extends TestCase
{
    protected $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new Builder();
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
        $this->builder->table('test')->asInsert(['name' => 'ezDB']);
        $bindings = $this->builder->getBindings('insert');
        
        $this->assertEquals(QueryType::Insert, $this->builder->getType());
        
        $this->assertNotEmpty($bindings);
    }

    public function testInsert2D()
    {
        //Test 2d array insert
        $this->builder->table('test')->asInsert([['name' => 'ezDB1'], ['name' => 'ezDB2']]);
        $bindings = $this->builder->getBindings('insert');

        $this->assertEquals(QueryType::Insert, $this->builder->getType());
        
        $this->assertNotEmpty($bindings);
        $this->assertCount(2, $bindings);
    }

    public function testUpdate()
    {
        $this->builder->table('test')->asUpdate(['updated_at' => date("Y-m-d H:i:s")]);
        $bindings = $this->builder->getBindings('update');


        $this->assertEquals(QueryType::Update, $this->builder->getType());
        
        $this->assertNotEmpty($bindings);
        $this->assertCount(1, $bindings);
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

    public function testWhereRawInstance()
    {
        $this->builder->whereRaw('YEAR(created_at) > 2021', boolean: 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('raw', $bindings[0]['type']);
        $this->assertEquals('OR', $bindings[0]['boolean']);
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
        $this->assertEquals('null', $bindings[0]['type']);
    }

    public function testWhereNotNull()
    {
        $this->builder->whereNotNull('created_at', 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('created_at', $bindings[0]['column']);
        $this->assertEquals('OR', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(true, $bindings[0]['not']);
        $this->assertEquals('null', $bindings[0]['type']);
    }

    public function testWhereBetween()
    {
        $this->builder->whereBetween('id', 11, 15);
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('between', $bindings[0]['type']);
        $this->assertEquals(11, $bindings[0]['value1']);
        $this->assertEquals(15, $bindings[0]['value2']);
        $this->assertEquals('AND', strtoupper($bindings[0]['boolean']));
        $this->assertEquals(false, $bindings[0]['not']);
    }

    public function testWhereNotBetween()
    {
        $this->builder->whereNotBetween('id', 11, 15, 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('between', $bindings[0]['type']);
        $this->assertEquals(11, $bindings[0]['value1']);
        $this->assertEquals(15, $bindings[0]['value2']);
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

    public function testWhereRaw()
    {
        $this->builder->whereRaw('YEAR(created_at) > ?', 'OR');
        $bindings = $this->builder->getBindings('where');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('raw', $bindings[0]['type']);
        $this->assertEquals('OR', $bindings[0]['boolean']);
    }

    public function testOrderBy()
    {
        $this->builder->orderBy('id', 'desc');
        $bindings = $this->builder->getBindings('order');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('id', $bindings[0]['column']);
        $this->assertEquals('DESC', $bindings[0]['direction']);
    }

    public function testLimit()
    {
        $this->builder->limit(10);
        $bindings = $this->builder->getBindings('limit');

        $this->assertEquals(10, $bindings[0]);
    }

    public function testLimitAndOffset()
    {
        $this->builder->limit(10, 50);
        $bindings = $this->builder->getBindings('limit');

        $this->assertEquals(10, $bindings[0]);

        $bindings = $this->builder->getBindings('offset');
        $this->assertEquals(50, $bindings[0]);
    }

    public function testOffset()
    {
        $this->builder->offset(50);
        $bindings = $this->builder->getBindings('offset');

        $this->assertEquals(50, $bindings[0]);
    }

    public function testSet()
    {
        $this->builder->set('name', 'ezDB');
        $bindings = $this->builder->getBindings('update');

        $this->assertNotEmpty($bindings);
        $this->assertEquals('name', $bindings[0]['column']);
        $this->assertEquals('ezDB', $bindings[0]['value']);
    }

    public function testDistinct()
    {
        $results = $this->builder->table('test')->distinct();
        $this->assertTrue($this->builder->getClauses('distinct')[0]);
    }

    public function testAsSelect()
    {
        $this->builder->table('test')->asSelect(['a', 'b']);
        $clauses = $this->builder->getClauses('select');

        $this->assertEquals(QueryType::Select, $this->builder->getType());
        $this->assertIsArray($clauses);
        $this->assertCount(2, $clauses);
    }

    /**
     * @depends testInsert
     * @depends testInsert2D
     */
    public function testAggregate()
    {
        $this->builder->table('test');

        //Each aggregate makes a clone of builder. If that changes in the future, need to rewrite these.
        $count = $this->builder->asCount();
        $min = $this->builder->asMin('id');
        $max = $this->builder->asMax('id');
        $sum = $this->builder->asSum('id');
        $avg = $this->builder->asAvg('id');

        $this->assertInstanceOf(IAggregateQuery::class, $count);
        $this->assertInstanceOf(IAggregateQuery::class, $min);
        $this->assertInstanceOf(IAggregateQuery::class, $max);
        $this->assertInstanceOf(IAggregateQuery::class, $sum);
        $this->assertInstanceOf(IAggregateQuery::class, $avg);
        //TODO:
    }

    public function testDelete()
    {
        $this->builder->table('test')->asDelete();
        $this->assertEquals(QueryType::Delete, $this->builder->getType());
    }


    public function testTruncate()
    {
      $this->builder->table('test')->asTruncate();
        $this->assertEquals(QueryType::Truncate, $this->builder->getType());
    }
}