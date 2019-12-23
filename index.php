<?php
ini_set('display_errors', 'on');
require __DIR__ . '/vendor/autoload.php';
include "config.php";

header('Content-Type: text/html; charset=utf-8');
?>
<h1>Микросервис Jira Report</h1>
<hr>

<ul>
    <li>Получает задачи за указанный период и залогированное на них время
    <li> url: /run.php - строит отчет за месяц
</ul>