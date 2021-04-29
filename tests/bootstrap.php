<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

require_once __DIR__ . '/../vendor/autoload.php';


$dbConfig = [];
if (getenv('ezDB_driver') !== false) {
    //This is used for GitHub Actions. The environment values are loaded.
    $db1 = [
        'driver' => getenv('ezDB_driver'),
        'host' => getenv('ezDB_host'),
        'port' => getenv('ezDB_port'),
        'database' => getenv('ezDB_db'),
        'username' => getenv('ezDB_user'),
        'password' => getenv('ezDB_pass')
    ];

    //config_key env variable needs to be set when doing Driver Tests.
    $configName = (getenv('ezDB_config_key') !== false) ? getenv('ezDB_config_key') : 'db1';

    //Add database details to the global $dbConfig array.
    $dbConfig[$configName] = $db1;
} else {
    //If running manually, rename env.php.example file to env.php, and fill in the values.
    $dbConfig = require_once('env.php');
}

$dummyData = file_get_contents(__DIR__ . '/Data/dummy.sql');
