<?php
require_once dirname(__FILE__) . "/djjob/DJJob.php";
require_once dirname(__FILE__) . "/../inc/HermesHelper.php";
$env = HermesHelper::getEnvironment();
if($env == 'PROD') {
    $host = 'dbmaster.theetailers.net';
} else {
    $host = '127.0.0.1';
}
DJJob::configure("mysql:host=" . $host . ";dbname=hermes", array(
    "mysql_user" => "hermes",
    "mysql_pass" => "dev123",
));
