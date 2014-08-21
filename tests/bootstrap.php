<?php

require_once dirname(__FILE__) . '/../analyzer/ValidatorContext.php';
require_once dirname(__FILE__) . '/../analyzer/Abstract/Validator.php';
require_once dirname(__FILE__) . '/../analyzer/Analyzer.php';
foreach (glob(dirname(__FILE__) . "/../analyzer/Abstract/*.php") as $filename) {
    require_once $filename;
}
foreach (glob(dirname(__FILE__) . "/../analyzer/lexical/*.php") as $filename) {
    require_once $filename;
}
foreach (glob(dirname(__FILE__) . "/../analyzer/semantic/*.php") as $filename) {
    require_once $filename;
}
