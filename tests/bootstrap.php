<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dbConfig = require_once('env.php');
$dummyData = file_get_contents(__DIR__ . '/Data/dummy.sql');
