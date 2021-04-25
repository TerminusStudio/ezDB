<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (getenv('ezDB_mysql_host')) {
    $dbConfig = [
        'mysqli' => [
            'driver' => 'mysqli',
            'host' => getenv('ezDB_mysql_host'),
            'database' => getenv('ezDB_mysql_db'),
            'username' => getenv('ezDB_mysql_user'),
            'password' => getenv('ezDB_mysql_pass')
        ],
        'pdo' => [
            'driver' => 'mysql',
            'host' => getenv('ezDB_mysql_host'),
            'database' => getenv('ezDB_mysql_db'),
            'username' => getenv('ezDB_mysql_user'),
            'password' => getenv('ezDB_mysql_pass')
        ]
    ];
} else {
    $dbConfig = require_once('env.php');
}

$dummyData = file_get_contents(__DIR__ . '/Data/dummy.sql');
