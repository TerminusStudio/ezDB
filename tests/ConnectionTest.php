<?php

namespace TS\ezDB\Tests;

use TS\ezDB\Connection;
use TS\ezDB\Connections;
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Drivers\MySQLiDriver;
use TS\ezDB\Interfaces\DriverInterface;

class ConnectionTest extends TestCase
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connections::addConnection(new DatabaseConfig(self::$dbConfig['mysqli']), 'ConnectionTest');
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connections::connection('ConnectionTest');
    }


    public function testSetUp()
    {
        $this->assertFalse($this->connection->isConnected());
    }

    public function testGetDriver()
    {
        $connection = Connections::connection('ConnectionTest');

        $this->assertInstanceOf(DriverInterface::class, $connection->getDriver());
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

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $connection = Connections::connection('ConnectionTest');
        $connection->connect();

        $connection->raw("TRUNCATE TABLE test");
    }
}
