<?php

require_once '../analyzer/Analyzer.php';
require_once '../inc/magmi_defs.php';
require_once '../inc/magmi_config.php';
require_once '../integration/inc/magmi_datapump.php';
require_once  '../inc/magmi_statemanager.php';
require_once 'Loggers.php';
require_once 'CategoryEngine.php';

$conf=Magmi_Config::getInstance();

if(isset($_POST['upload'])) {

    $content_dir = $_SERVER["DOCUMENT_ROOT"]. '/hermes/upload/';

    $tmp_file = $_FILES['stock_csv']['tmp_name'];

    if( !is_uploaded_file($tmp_file) )
    {
        exit("Error al subir el archivo, comprueba de que la carpeta upload tiene permisos de escritura");
    }

    $name_file = $_FILES['stock_csv']['name'];

    if( !move_uploaded_file($tmp_file, $content_dir . $name_file) )
    {
        exit("Se ha producido un error a subir el archivo.");
    } else {
        echo '<h2> File: ' . $name_file . '</h2>';
    }
    
    $pf=Magmi_StateManager::getProgressFile(true);
    $file = $content_dir . $name_file;
    $importer = new CategoryEngine(new $logger(isset($_GET['debug']), $pf), isset($_GET['test']), isset($_GET['remove']));
    $importer->run($file, $profile, $columnsMandatory);
}
