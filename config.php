<?php

if (file_exists('.env')) {
    $dotenv = \Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();

    $dsn = 'mysql:host=localhost;dbname=' . $_ENV['DB_NAME'];
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    );
    $db = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
}
else{
    echo "Отсутствует файл .env";
    exit;
}

global $dbpdo;
$dbpdo = $db;


//--------------------------------------------
// FOR Ruckus Migrations
//--------------------------------------------

//These might already be defined, so wrap them in checks

// DB table where the version info is stored
if (!defined('RUCKUSING_SCHEMA_TBL_NAME'))
{
    define('RUCKUSING_SCHEMA_TBL_NAME', 'migrations_info');
}

if (!defined('RUCKUSING_TS_SCHEMA_TBL_NAME'))
{
    define('RUCKUSING_TS_SCHEMA_TBL_NAME', 'migrations');
}