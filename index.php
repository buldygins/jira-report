<?php
ini_set('display_errors', 'on');
require __DIR__ . '/vendor/autoload.php';
include "config.php";
//phpinfo();exit;
//print_r($_ENV);exit;

header('Content-Type: text/html; charset=utf-8');
$my = new \YourResult\JiraReport($db);