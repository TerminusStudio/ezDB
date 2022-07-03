<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

$ezDBPath = __DIR__ . '/src';

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

//Load base builder and processor
require_once $ezDBPath . '/Query/Builder/QueryType.php';
require_once $ezDBPath . '/Query/Builder/IBuilderInfo.php';
require_once $ezDBPath . '/Query/Builder/IWhereBuilder.php';
require_once $ezDBPath . '/Query/Builder/IAggregateQuery.php';
require_once $ezDBPath . '/Query/Builder/INestedJoinBuilder.php';
require_once $ezDBPath . '/Query/Builder/IBuilder.php';

require_once $ezDBPath . '/Query/Builder/WhereHelper.php';
require_once $ezDBPath . '/Query/Builder/BuilderInfo.php';
require_once $ezDBPath . '/Query/Builder/WhereBuilder.php';
require_once $ezDBPath . '/Query/Builder/AggregateQuery.php';
require_once $ezDBPath . '/Query/Builder/NestedJoinBuilder.php';
require_once $ezDBPath . '/Query/Builder/Builder.php';

require_once $ezDBPath . '/Query/IQuery.php';
require_once $ezDBPath . '/Query/DefaultQuery.php';

require_once $ezDBPath . '/Query/Processor/IProcessor.php';
require_once $ezDBPath . '/Query/Processor/ProcessorContext.php';
require_once $ezDBPath . '/Query/Processor/BaseProcessor.php';
require_once $ezDBPath . '/Query/Processor/MySQLProcessor.php';
require_once $ezDBPath . '/Query/Processor/PostgresProcessor.php';

//For connections:

require_once $ezDBPath . '/DatabaseConfig.php';

//Load Drivers
require_once $ezDBPath . '/Drivers/IDriver.php';
require_once $ezDBPath . '/Drivers/MySqlIDriver.php'; //for mysqli driver
require_once $ezDBPath . '/Drivers/PdoDriver.php'; //for mysql, pgsql driver

//Load Connection
require_once $ezDBPath . '/Connection.php';
require_once $ezDBPath . '/Connections.php';
require_once $ezDBPath . '/DB.php'; //Optional. Some functions may not work without loading Builder

//Load connection builder
require_once $ezDBPath . '/Connections/Builder/IConnectionAwareBuilder.php';
require_once $ezDBPath . '/Connections/Builder/ConnectionAwareBuilder.php';

//For models:

//Load model builder
require_once $ezDBPath . '/Models/Builder/RelationshipBuilder.php';
require_once $ezDBPath . '/Models/Builder/ModelAwareBuilder.php';

//Load Model
require_once $ezDBPath . '/Models/Relationship.php';
require_once $ezDBPath . '/Models/Model.php';
