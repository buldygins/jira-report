<?php
require 'vendor/autoload.php';
require "config.php";
use YourResult\JiraReport\JiraReport;

$z=new JiraReport();
$z->run();