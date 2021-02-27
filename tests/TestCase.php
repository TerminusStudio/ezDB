<?php

namespace TS\ezDB\Tests;

use TS\ezDB\Connections;
use TS\ezDB\DatabaseConfig;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Database Config Array
     */
    protected static $dbConfig = [];

    protected static $dummyData = "";

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        global $dbConfig, $dummyData;
        self::$dbConfig = $dbConfig;
        self::$dummyData = $dummyData;

        Connections::addConnection(new DatabaseConfig(self::$dbConfig['mysqli']), 'Connection1');
        Connections::addConnection(new DatabaseConfig(self::$dbConfig['pdo']), 'Connection2');
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        Connections::connection('Connection1')->getDriver()
            ->exec("TRUNCATE TABLE `test`; TRUNCATE TABLE `test2`;" .
                "TRUNCATE TABLE `test_intermediate`; TRUNCATE TABLE `test_related`;");
        Connections::connection('Connection1')->close();
        Connections::connection('Connection2')->close();
    }
}