<?php
//Use load.php file instead of composer autoload to make sure the load.php file stays updated.
require_once __DIR__ . '/../load.php';

$dbConfig = require_once('env.php');
$dummyData = file_get_contents(__DIR__ . '/Data/dummy.sql');
