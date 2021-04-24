<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Query;

use TS\ezDB\Connections;
use TS\ezDB\Exceptions\ModelMethodException;
use TS\ezDB\Exceptions\QueryException;
use TS\ezDB\Query\Builder\RelationshipBuilder;
use TS\ezDB\Tests\TestCase;

class RelationshipBuilderTest extends TestCase
{
    protected $builder;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connections::connection('Connection1')->getDriver()->exec(self::$dummyData);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new RelationshipBuilder(Connections::connection('Connection1'));
    }

    public function testHasOne()
    {
        $result = $this->builder->hasOne('test_intermediate', '1', 'test_id')->get();

        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('intermediate_value', $result);
        $this->assertEquals('Value', $result->intermediate_value);
    }

    public function testHasMany()
    {
        $result = $this->builder->hasMany('test_intermediate', '1', 'test_id')->get();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Value', $result[0]->intermediate_value);
        $this->assertEquals('Value2', $result[1]->intermediate_value);
    }

    public function testBelongsTo()
    {
        $result = $this->builder->belongsTo('test', '1', 'id')->get();

        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('name', $result);
        $this->assertEquals('Program', $result->name);
        $this->assertEquals('1', $result->id);
    }

    /**
     * This method tests joinPivot() and get() at the same time as well.
     */
    public function testBelongsToMany()
    {
        $result = $this->builder
            ->belongsToMany('test_related', 'test_intermediate', 'test_id', 'test_related_id', 'id', 1)
            ->get();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals("Hello", $result[0]->value);
        $this->assertEquals("World", $result[1]->value);
        $this->assertEquals(1, $result[0]->pivot->test_related_id);
        $this->assertObjectNotHasAttribute('intermediate_value', $result[0]->pivot);
    }


    public function testWithPivot()
    {
        $result = $this->builder
            ->belongsToMany('test_related', 'test_intermediate', 'test_id', 'test_related_id', 'id', 1)
            ->withPivot('intermediate_value')
            ->get();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertObjectHasAttribute('intermediate_value', $result[0]->pivot);
        $this->assertEquals('Value', $result[0]->pivot->intermediate_value);
    }

    public function testWithPivotException()
    {
        $this->expectException(QueryException::class);
        $this->builder->belongsTo('test', '1', 'id')->withTimestamps()->get();
    }

    public function testWithTimestamps()
    {
        $this->expectException(QueryException::class);
        $this->builder->belongsTo('test', '1', 'id')->withTimestamps()->get();

        $this->expectException(ModelMethodException::class);
        $this->builder
            ->belongsToMany('test_related', 'test_intermediate', 'test_id', 'test_related_id', 'id', 1)
            ->withTimestamps()
            ->get();
    }

    public function testWherePivot()
    {
        $result = $this->builder
            ->belongsToMany('test_related', 'test_intermediate', 'test_id', 'test_related_id', 'id', 1)
            ->wherePivot('intermediate_value', 'Value2')
            ->withPivot('intermediate_value')
            ->get();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Value2', $result[0]->pivot->intermediate_value);
    }

    public function testWherePivotException()
    {
        $this->expectException(QueryException::class);
        $this->builder
            ->belongsTo('test', '1', 'id')
            ->wherePivot('intermediate_value', 'Value2')
            ->get();
    }

    public function testAs()
    {
        $result = $this->builder
            ->belongsToMany('test_related', 'test_intermediate', 'test_id', 'test_related_id', 'id', 1)
            ->as('intermediate')
            ->get();

        $this->assertIsArray($result);
        $this->assertObjectHasAttribute('intermediate', $result[0]);
    }
}