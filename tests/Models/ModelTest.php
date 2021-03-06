<?php

namespace TS\ezDB\Tests;

use TS\ezDB\Connections;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Query\Builder\Builder;
use TS\ezDB\Tests\Data\TestModel;

class ModelTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connections::connection('Connection1')->getDriver()->exec(self::$dummyData);
    }

    public function testFind()
    {
        $m = TestModel::find(1);

        $this->assertInstanceOf(TestModel::class, $m);
    }

    public function testCallStatic()
    {
        $result = TestModel::where('id', 2);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testCallNonStatic()
    {
        $result = (new TestModel())->where('id', 2);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testMagicGetSetIsset()
    {
        $data = ['name' => 'test', 'id' => 1];

        $m = new TestModel();
        $m->setData($data);

        $this->assertTrue(isset($m->name));
        $this->assertFalse(isset($m->something));

        $this->assertEquals('test', $m->name);

        $m->name = "hello";
        $this->assertEquals('hello', $m->name);
    }

    public function testExists()
    {
        $m = new TestModel();
        $this->assertFalse($m->exists());

        $m = TestModel::find(1);
        $this->assertTrue($m->exists());
    }

    public function testIsDirty()
    {
        $m = TestModel::find(1);

        $this->assertFalse($m->isDirty());

        $m->name = "Changed";
        $this->assertTrue($m->isDirty());
    }

    public function testGetDirty()
    {
        $m = TestModel::find(1);

        $this->assertEmpty($m->getDirty());

        $m->name = "Changed";

        $dirty = $m->getDirty();
        $this->assertCount(1, $dirty);
        $this->assertEquals("name", $dirty[0]);
    }

    public function testGetSetData()
    {
        $data = ['name' => 'test', 'id' => 1];

        $m = new TestModel();
        $m->setData($data);

        $this->assertTrue($data == $m->getData());
    }

    public function testAs()
    {
        $builder = TestModel::as('t');

        $this->assertEquals('test as t', $builder->getBindings('from')[0]);
    }

    public function testNewQuery()
    {
        $result = TestModel::newQuery();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testNewRecord()
    {
        $m = new TestModel();
        $m->name = 'ezDB4';
        $m->save();
        $m = TestModel::find($m->id);

        $this->assertNotNull($m);
    }

    public function testSave()
    {
        $m = TestModel::find(1);
        $m->name = "test_save";
        $m->save();

        $m = TestModel::find(1);
        $this->assertEquals("test_save", $m->name);
    }


    public function testTimestamp()
    {
        $m = new TestModel();
        $m->name = 'ezDB';
        $m->save();
        $m = TestModel::find($m->id);

        $this->assertNotNull($m->created_at);

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $m->created_at);
        $this->assertTrue($date && $date->format('Y-m-d H:i:s') === $m->created_at);
    }
}