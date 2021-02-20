<?php

namespace TS\ezDB\Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Database Config Array
     */
    protected static $dbConfig = [];

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        global $dbConfig;
        self::$dbConfig = $dbConfig;
    }

}