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
use TS\ezDB\Drivers\PDODriver;

class PDODriverTest extends DriverTestCase
{
    /** @var PDODriver */
    protected static $driver;

    /** @var \PDOStatement */
    protected static $stmt;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!isset(self::$dbConfig['pdo'])) {
            throw new \Error(
                "Please set a 'pdo' key in .env to run PDO driver test. " .
                "If not use phpunit.xml as config file to skip driver testing."
            );
        }
        static::$driver = new PDODriver(new DatabaseConfig(self::$dbConfig['pdo']));
    }

    /**
     * @return PDODriver
     */
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

        $this->assertInstanceOf(\PDO::class, $handle);
    }

    /**
     * @depends testConnect
     */
    public function testPrepare()
    {
        self::$stmt = $this->getDriver()->prepare("INSERT INTO `test` (`name`) VALUES (?)");

        $this->assertInstanceOf(\PDOStatement::class, $this->getStmt());
    }

    /**
     * @depends testPrepare
     */
    public function testBind()
    {
        $name = 'ezDB';
        self::$stmt = $this->getDriver()->bind($this->getStmt(), $name);

        $this->assertInstanceOf(\PDOStatement::class, $this->getStmt());
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

        static::$driver->query("TRUNCATE TABLE `test`");
    }
}