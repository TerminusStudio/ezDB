<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

$ezDBPath = __DIR__ . '/src';

//Load Interfaces
require_once $ezDBPath . '/Interfaces/DriverInterface.php';

//Load Exceptions
require_once $ezDBPath . '/Exceptions/Exception.php';
require_once $ezDBPath . '/Exceptions/ConnectionException.php';
require_once $ezDBPath . '/Exceptions/DriverException.php';
require_once $ezDBPath . '/Exceptions/ModelMethodException.php';
require_once $ezDBPath . '/Exceptions/QueryException.php';

/**
 * Load only the classes and drivers you require
 *
 * Be mindful of what you remove.
 * If you are not sure what to remove then ask for help in GitHub or leave the file as it is.
 */

//Load Drivers
require_once $ezDBPath . '/Drivers/MySQLiDriver.php'; //for mysqli driver
require_once $ezDBPath . '/Drivers/PDODriver.php'; //for mysql, pgsql driver

//Load Connection
require_once $ezDBPath . '/DatabaseConfig.php';
require_once $ezDBPath . '/Connection.php';
require_once $ezDBPath . '/Connections.php';
require_once $ezDBPath . '/DB.php'; //Optional. Some functions may not work without loading Builder

//Load Builder
require_once $ezDBPath . '/Query/Processor/Processor.php';
require_once $ezDBPath . '/Query/Processor/MySQLProcessor.php'; //Required when using mysql/mysqli
require_once $ezDBPath . '/Query/Processor/PostgresProcessor.php'; //Required when using pgsql
require_once $ezDBPath . '/Query/Builder/Builder.php';
require_once $ezDBPath . '/Query/Builder/JoinBuilder.php';
require_once $ezDBPath . '/Query/Builder/RelationshipBuilder.php';
require_once $ezDBPath . '/Query/Raw.php';

//Load Model
require_once $ezDBPath . '/Models/Relationship.php';
require_once $ezDBPath . '/Models/Model.php';
