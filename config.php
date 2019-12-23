<?php
//print_r($_REQUEST); exit;

if (!empty($_REQUEST['project'])) {
    $project=$_REQUEST['project'];
    $dotenv = \Dotenv\Dotenv::create(__DIR__, ".env.".$project);
    $dotenv->overload();
}
else if (file_exists('.env')) {
    $dotenv = \Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
}
else{
    echo "Отсутствует файл .env";
    exit;
}

//print_r($_ENV);exit;

$dsn = 'mysql:host=localhost;dbname=' . $_ENV['DB_NAME'];
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
);
$db = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
//echo "333";exit;