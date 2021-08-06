<?php
ini_set('display_errors', 'on');
require __DIR__ . '/vendor/autoload.php';
include "config.php";
//phpinfo();exit;
//print_r($_ENV);exit;

header('Content-Type: text/html; charset=utf-8');
$my = new \YourResult\JiraReport($db);
$my->curr_project = \YourResult\models\Project::whereGet(['name' => 'Клиенты'])[0];
$logger = new \Monolog\Logger('log');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/logs/log.log'));
$logger->info('Start sync jira ' . date('Y-m-d H:i:s'));
$my->sync();
$logger->info('End sync jira ' . date('Y-m-d H:i:s'));
