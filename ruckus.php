#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

define('RUCKUSING_WORKING_BASE', getcwd()); //'/vendor/ruckusing/ruckusing-migrations'
$db_config = [
    'db' => [
        'development' => [
            'type'      => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => $_ENV['DB_NAME'],
            'user'      => $_ENV['DB_USER'],
            'password'  => $_ENV['DB_PASS'],
            'directory'=>''
        ]
    ],
    'migrations_dir' => RUCKUSING_WORKING_BASE .'/src/db/migrations',
    'db_dir' => RUCKUSING_WORKING_BASE . '/src/db/utility',
    'log_dir' => RUCKUSING_WORKING_BASE . '/src/db/logs',
    'ruckusing_base'=>RUCKUSING_WORKING_BASE. '/vendor/ruckusing/ruckusing-migrations'
];

if (isset($db_config['ruckusing_base'])) {
    define('RUCKUSING_BASE', $db_config['ruckusing_base']);
} else {
    define('RUCKUSING_BASE', dirname(__FILE__));
}



$main = new Ruckusing_FrameworkRunner($db_config, $argv);
echo $main->execute();
