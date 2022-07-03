<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Drivers;

use TS\ezDB\Query\Processor\Processor;
use TS\ezDB\Tests\TestCase;

/**
 * Driver Tests can extend from this class. Please make sure you setup driver using setUpBeforeClass() and empty the
 * table using tearDownAfterClass(). This is important!
 *
 * Class DriverTestCase
 * @package TS\ezDB\Tests\Drivers
 */
abstract class DriverTestCase extends TestCase
{
    public abstract function getDriver();

    public abstract function getStmt();

    public abstract function testHandle();

    public abstract function testPrepare();

    public abstract function testBind();

    public abstract function testExecute();

    public function testConnect()
    {
        if ($this->getDriver() == null) {
            throw new \Exception("Please setup driver using setUpBeforeClass().");
        }

        $result = $this->getDriver()->connect();
        $this->assertTrue($result);
    }

    /**
     * @depends testConnect
     * @depends testProcessor
     */
    public function testClose()
    {
        $result = $this->getDriver()->close();
        $this->assertTrue($result);
    }

    /**
     * @depends testClose
     */
    public function testReset()
    {
        $result = $this->getDriver()->reset();
        $this->assertTrue($result);
    }

    /**
     * @depends testExecute
     */
    public function testQuery()
    {
        $results = $this->getDriver()->query("SELECT * FROM `test`");

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertIsObject($results[0]);
        $this->assertObjectHasAttribute('name', $results[0]);
    }

    /**
     * @depends testExecute
     */
    public function testExec()
    {
        $results = $this->getDriver()->exec("SELECT * FROM test; SELECT * FROM test_related");

        $this->assertNotFalse($results);
    }

    /**
     * @depends testExecute
     */
    public function testCharset()
    {
        //Test UTF8MB4 charset
        $this->getDriver()->query("INSERT INTO test (name) VALUES('💩')");
        $id = $this->getDriver()->getLastInsertId();
        $results = $this->getDriver()->query(sprintf("SELECT `name` FROM `test` WHERE id=%d", $id));

        $this->assertEquals('💩', $results[0]->name);
    }

    /**
     * @depends testConnect
     */
    public function testEscape()
    {
        $string = "D'ox";
        $result = $this->getDriver()->escape($string);

        $this->assertIsString($result);
        $this->assertEquals('D\\\'ox', $result);
    }

    /**
     * @depends testConnect
     */
    public function testLastInsertId()
    {
        $this->getDriver()->query("INSERT INTO `test` (`name`) VALUES ('ezDB')");
        $result = $this->getDriver()->getLastInsertId();

        $id = $this->getDriver()->query("SELECT * FROM `test` ORDER BY `id` DESC LIMIT 1")[0]->id;

        $this->assertEquals($id, $result);

        $this->getDriver()->query(sprintf("DELETE FROM `test` WHERE `id`=%d", $id));
    }

    /**
     * @depends testConnect
     */
    public function testProcessor()
    {
        $processor = $this->getDriver()->getProcessor();

        $this->assertInstanceOf(Processor::class, $processor);
    }


}