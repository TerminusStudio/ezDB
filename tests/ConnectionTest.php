<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\Drivers\IDriver;

class ConnectionTest extends TestCase
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connections::connection('Connection1');
    }


    public function testSetUp()
    {
        $this->assertFalse($this->connection->isConnected());
    }

    public function testGetDriver()
    {
        $connection = Connections::connection('Connection1');

        $this->assertInstanceOf(IDriver::class, $connection->getDriver());
    }

    /**
     * @depends testSetUp
     */
    public function testConnect()
    {
        $this->connection->connect();

        $this->assertTrue($this->connection->isConnected());
    }

    public function testReset()
    {
        $result = $this->connection->reset();

        $this->assertNotFalse($result);
    }

    public function testRaw()
    {
        $result = $this->connection->raw("SELECT * FROM `test`");

        $this->assertIsArray($result);
    }

    /**
     * @depends testConnect
     */
    public function testInsert()
    {
        //insert 1 rows
        $result = $this->connection->insert(
            "INSERT INTO `test` (`name`, `created_at`) VALUES (?, ?)",
            "ezDB",
            date("Y-m-d H:i:s")
        );
        $this->assertEquals(1, $result);

        //insert 2 rows
        $result = $this->connection->insert(
            "INSERT INTO `test` (`name`, `created_at`) VALUES (?, ?), (?, ?)",
            "ezDB",
            date("Y-m-d H:i:s"),
            "ezDB1",
            date("Y-m-d H:i:s")
        );
        $this->assertEquals(2, $result);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        //Change inserted row by name
        $result = $this->connection
            ->update("UPDATE `test` SET `name` = ? WHERE `name` = ?", "ezDB", "ezDB1");
        $this->assertEquals(1, $result);

        //Change non existent row
        $result = $this->connection
            ->update("UPDATE `test` SET `name` = ? WHERE `name` = ?", "ezDB", "ezDB1");
        $this->assertEquals(0, $result);
    }

    /**
     * @depends testUpdate
     */
    public function testSelect()
    {
        $result = $this->connection->select("SELECT * FROM `test` WHERE `name` = ?", "ezDB");

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertIsObject($result[0]);
        $this->assertObjectHasAttribute('name', $result[0]);
    }

    /**
     * @depends testUpdate
     */
    public function testDelete()
    {
        $result = $this->connection->delete("DELETE FROM `test` WHERE `name` = ?", "ezDB");
        $this->assertEquals(3, $result);
    }

    public function testClose()
    {
        $result = $this->connection->close();

        $this->assertTrue($result);
        $this->assertFalse($this->connection->isConnected());
    }

    public function testLogging()
    {
        $this->assertEmpty($this->connection->getQueryLog());

        $this->connection->enableQueryLog();
        $this->connection->raw("SELECT * FROM `test`");
        $this->connection->insert("INSERT INTO `test` (`name`, `created_at`) VALUES (?, ?)", "ezDB", NULL);
        $this->connection->update("UPDATE `test` SET `name` = ? WHERE `name` = ?", "ezDB", "ezDB1");
        $this->connection->select("SELECT * FROM `test` WHERE `name` = ?", "ezDB");
        $this->connection->delete("DELETE FROM `test` WHERE `name` = ?", "ezDB");
        $result = $this->connection->getQueryLog();
        $this->assertCount(5, $result);
        $this->assertCount(3, $result[0]);
        $this->assertCount(2, $result[1]['bindings']);

        $this->connection->disableQueryLog();
        $this->connection->select("SELECT * FROM `test` WHERE `name` = ?", "ezDB");
        $this->assertCount(5, $this->connection->getQueryLog()); //Count should still be 5.

        $this->connection->flushQueryLog();
        $this->assertEmpty($this->connection->getQueryLog());
    }
}
