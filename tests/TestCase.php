<?php

namespace TS\ezDB\Tests;

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
    }
}