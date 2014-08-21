<?php
require dirname(__FILE__) . '/config.php';
if(isset($_SERVER['HTTP_HOST'])) {
 die(json_encode(DJJob::getPercentage($_SERVER['HTTP_HOST'])));
}
