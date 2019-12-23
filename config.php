<?php
if (!empty($_REQUEST['project'])) {
    $project=$_REQUEST['project'];
    $dotenv = \Dotenv\Dotenv::create(__DIR__, ".env.".$project);
    $dotenv->overload();
}
else if (file_exists('.env')) {
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