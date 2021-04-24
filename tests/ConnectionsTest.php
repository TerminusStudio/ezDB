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
use TS\ezDB\DatabaseConfig;
use TS\ezDB\Exceptions\ConnectionException;

class ConnectionsTest extends TestCase
{
    public function testCreateConnection()
    {
        $databaseConfig = new DatabaseConfig(self::$dbConfig['mysqli']);
        $connection = Connections::addConnection($databaseConfig, 'ConnectionsTest');

        self::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @depends testCreateConnection
     */
    public function testConnection()
    {
        $connection = Connections::connection('ConnectionsTest');

        $this->assertInstanceOf(Connection::class, $connection);

        $this->expectException(ConnectionException::class);
        Connections::connection('InvalidConnection'); //This should throw an error.
    }
}
