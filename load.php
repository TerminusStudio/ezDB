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

//Load Drivers
require_once $ezDBPath . '/Drivers/MySQLiDriver.php';
require_once $ezDBPath . '/Drivers/PDODriver.php';

//Load Connection
require_once $ezDBPath . '/DatabaseConfig.php';
require_once $ezDBPath . '/Connection.php';
require_once $ezDBPath . '/Connections.php';
require_once $ezDBPath . '/DB.php';

//Load Builder
require_once $ezDBPath . '/Query/Processor/Processor.php';
require_once $ezDBPath . '/Query/Builder/Builder.php';
require_once $ezDBPath . '/Query/Builder/JoinBuilder.php';
require_once $ezDBPath . '/Query/Builder/RelationshipBuilder.php';
require_once $ezDBPath . '/Query/Raw.php';

//Load Model
require_once $ezDBPath . '/Models/Relationship.php';
require_once $ezDBPath . '/Models/Model.php';
