<?php
require dirname(__FILE__) . "/config.php";


$worker = new DJWorker(array("max_attempts" => 2, "sleep" => 300), $env);
$worker->start();

