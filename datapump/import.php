<?php
require_once '../analyzer/Analyzer.php';
require_once '../inc/magmi_defs.php';
require_once '../inc/magmi_config.php';
require_once '../integration/inc/magmi_datapump.php';
require_once  '../inc/magmi_statemanager.php';
require_once '../queue/config.php';
require_once 'Loggers.php';
require_once 'Importer.php';

$conf=Magmi_Config::getInstance();

if(isset($_POST['upload'])) {

    $content_dir = dirname(dirname($_SERVER['SCRIPT_FILENAME'])). '/upload/';

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
        echo '<h2> File: ' . $name_file . '</h2>' . "\n";

        if($_GET['test'] == true) {
            echo '<div class="clean-gray">Entornos de pruebas : Solo se verificará la exactitud de los datos, sin realizar ningún cambio en la base de datos.</div>';
        }

        $filename = $content_dir . $name_file;

        HermesHelper::encodeUTF8($filename);

    }
    $file = $content_dir . $name_file;

    try{
        $pf=Magmi_StateManager::getProgressFile(true);
        $importer = new Importer(new $logger(isset($_GET['debug']), $pf), isset($_GET['test']), isset($_GET['remove']), isset($_GET['new_categories']));
        $importer->run($file, $profile, $columnsMandatory);
    }catch(Exception $e){
        echo "ERROR : ".$e->getMessage();
    }
}
