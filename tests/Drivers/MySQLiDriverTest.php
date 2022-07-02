<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Drivers;

use TS\ezDB\DatabaseConfig;
use TS\ezDB\Drivers\MySqlIDriver;
use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Query\Processor\Processor;

/**
 * Class MySQLiDriverTest
 * @package TS\ezDB\Tests\Drivers
 */
class MySQLiDriverTest extends DriverTestCase
{
    /** @var MySqlIDriver */
    protected static $driver;

    /** @var \mysqli_stmt */
    protected static $stmt;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!isset(self::$dbConfig['mysqli'])) {
            throw new \Error(
                "Please set a 'mysqli' key in .env to run MySQLi driver test. " .
                "If not use phpunit.xml as config file to skip driver testing."
            );
        }
        $databaseConfig = new DatabaseConfig(self::$dbConfig['mysqli']);
        $processor = $databaseConfig->getProcessorClass();
        static::$driver = new MySqlIDriver($databaseConfig, new $processor());
    }

    public function getDriver()
    {
        return static::$driver;
    }

    public function getStmt()
    {
        return static::$stmt;
    }

    /**
     * @depends testConnect
     */
    public function testHandle()
    {
        $handle = $this->getDriver()->handle();

        $this->assertInstanceOf(\mysqli::class, $handle);
    }

    /**
     * @depends testConnect
     */
    public function testPrepare()
    {
        self::$stmt = $this->getDriver()->prepare("INSERT INTO `test` (`name`) VALUES (?)");

        $this->assertInstanceOf(\mysqli_stmt::class, $this->getStmt());
    }

    /**
     * @depends testPrepare
     */
    public function testBind()
    {
        $name = 'ezDB';
        self::$stmt = $this->getDriver()->bind($this->getStmt(), $name);

        $this->assertInstanceOf(\mysqli_stmt::class, $this->getStmt());
    }

    /**
     * @depends testBind
     */
    public function testExecute()
    {
        $results = $this->getDriver()->execute($this->getStmt(), true, false);

        $this->assertEquals(1, $results);
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::$driver->connect();
        static::$driver
            ->exec("TRUNCATE TABLE `test`; TRUNCATE TABLE `test_intermediate`; TRUNCATE TABLE `test_related`;");
    }


}
