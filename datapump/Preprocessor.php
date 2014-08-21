<?php
require_once  dirname(__FILE__) . '/../inc/magmi_statemanager.php';

class Preprocessor {

    const SQL_GET_PRODUCTS = 'SELECT sku from catalog_product_entity where sku in ("%s")';
    const UPLOAD_DIR = '/hermes/upload/';

    private static $DEBUG;
    private static $profiles = array(
        'import' => array('name', 'sku', 'status', 'visibility', 'description', 'short_description', 'price', 'tax_class_id', 'qty', 'type', 'attribute_set', 'weight', 'image'),
        'update' => array('sku'),
    );

    private $logger;

    function __construct($debug = false) {
        $this->rows = array();
        self::$DEBUG = $debug;
        $this->skuable = false;
    }

    function __destruct() {
        //Remove files if debug mode is disabled
        if(isset($this->files) && !self::$DEBUG) {
            foreach ($this->files as $file) {
                @unlink($file);
            }
        }
    }
    public function start($logger) {
        if(isset($_POST['upload'])) {

            $content_dir = $_SERVER["DOCUMENT_ROOT"]. self::UPLOAD_DIR;
            $tmp_file = $_FILES['stock_csv']['tmp_name'];
            $name_file = $_FILES['stock_csv']['name'];
            $file = $content_dir . $name_file;

            if(isset($_GET['test'])) {
                echo '<div class="clean-gray">Entornos de pruebas : Solo se verificará la exactitud de los datos, sin realizar ningún cambio en la base de datos.</div>';
            }
            if(!is_uploaded_file($tmp_file)) {
                exit('Error al subir el archivo, comprueba de que la carpeta ' . self::UPLOAD_DIR . ' tiene permisos de escritura');
            }

            if(!move_uploaded_file($tmp_file, $file)) {
                exit('Se ha producido un error a subir el archivo.');
            } else {
                 '<h2> File: ' . $name_file . '</h2>' . "\n";
            }

            HermesHelper::encodeUTF8($file);

            $this->process($file, $logger, isset($_GET['test']), isset($_GET['remove']), isset($_GET['new_categories']));
        }
    }
    public function process($file, $logger, $test, $remove, $new_categories) {
            $pf=Magmi_StateManager::getProgressFile(true);
            $this->logger = new $logger(self::$DEBUG, $pf);
            $this->files = $this->getParsedData($file);

            $importer = new Importer($this->logger, $test, $remove, $new_categories);
            $importer->run($this->files, null, self::$profiles);
    }

    private function readFile($file) {
        $delimiter = Analyzer::getDelimiter($file);
        $handle = fopen($file, 'r');
        $this->header = fgetcsv($handle, 0, $delimiter);
        //Clear the data
        $this->header  = array_filter(array_map('trim', array_map('strtolower', $this->header)));

        if(!in_array('sku', $this->header)) {
            fclose($handle);
            die('<div class="error">Error processing the file, at least the sku must be specified, please, <a href="javascript:history.back()">retry again</a>.</div>');
        }

        $this->sku = array_search('sku', $this->header);
        $this->store = array_search('store', $this->header);

        //Attempt to retrieve the brand, just in case it's a customSite
        $marca = array_search('marca', $this->header);
        $brand = array_search('brand', $this->header);
        if($marca !== false || $brand !== false) {
            $this->skuable = $marca !== false ? $marca : $brand;
        }

        $fields = array('sku' => array(), 'store' => array());

        while(($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        
            $this->rows[] = $data;
            $fields['sku'][] = $data[$this->sku];
            $fields['store'][] = $this->store ? $data[$this->store] : NULL;
            //Store the brand in a known column
            $fields['skuable'][] = $this->skuable !== false ? $data[$this->skuable] : NULL;
        }
        fclose($handle);
        return $fields;
    }

    private function checkDBData(&$fields) {
        $dbConfig = HermesHelper::getMagentoDBConfig();
        $this->mysqli = new mysqli($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->dbname);
        $this->mysqli->set_charset("utf8");

        //TODO: try to refactor the analyzer's code and make a static reusable method.
        $customSite = in_array($dbConfig->dbname, Analyzer::$_CUSTOM_SITES);
        if($customSite && $this->skuable !== false) {
            $skus_list = array();
            //Convert all the skus preppending the brand.
            foreach ($fields['sku'] as $i => $sku) {
                $skus_list[] = Analyzer::skuableBrand($fields['skuable'][$i]) . '_' . $sku;
            }
            //Replace the sku list with the custom sku
            $fields['sku'] = $skus_list;
        }

        $skus_list =  implode($fields['sku'], '","');
        $skus = $this->mysqli->query(sprintf(self::SQL_GET_PRODUCTS, $skus_list));

        //Import task
        if($skus->num_rows == 0) {
            $ret = 'import';
        } else if ($skus->num_rows == count(array_unique($fields['sku']))) {
            //Update task
            $ret = 'update';
        } else {
            //Mixed task
            $ret = array();
            while($obj = $skus->fetch_object()) {
                $ret[$obj->sku] = 1;
            }
        }
        return $ret;
    }
    private function isAdminSet($fields, $sku) {
        //Search for the matches of the sku on the file
        $matches = array_keys($fields['sku'], $sku, true);
        $adminFound = false;
        foreach ($matches as $index) {
            if($fields['store'][$index] == 'admin') {
                $adminFound = true;
                break;
            }
        }
        return $adminFound;
    }

    private function splitData($file, $fields, $found) {
        $handleU = fopen(($update = str_ireplace('.csv','U.csv', $file)), 'w');
        $handleI = fopen(($import = str_ireplace('.csv','I.csv', $file)), 'w');
        fputcsv($handleU, $this->header);
        fputcsv($handleI, $this->header);

        foreach ($this->rows as $i => $row) {

            //If sku exists, update
            if(isset($found[$fields['sku'][$i]])) {
                fputcsv($handleU, $row);
            //If sku doesn't exist, and store=null|admin, import
            } else if(!$this->store || $row[$this->store] == 'admin') {
                fputcsv($handleI, $row);
            } else {
                $adminFound = $this->isAdminSet($fields, $fields['sku'][$i]);
                //If the same sku is set for the admin store, update
                if($adminFound) {
                    fputcsv($handleU, $row);
                //If product wasn't found for admin store, import
                } else {
                    fputcsv($handleI, $row);

                }
            }
        }
        fclose($handleU);
        fclose($handleI);

        return array('import' => $import, 'update' => $update);
    }

    private function getParsedData($file) {
        $fields = $this->readFile($file);
        $found = $this->checkDBData($fields);

        if($found == 'import' || $found == 'update') {
            $ret = array($found => $file);
        } else {
            //If the number of columns is smaller than the minimun required fields for an importation, it's likely to be an update operation, show the warning
            if(count($this->header) < count(self::$profiles['import'])) {
                $this->logger->log('Seems an update, but the following skus haven\'t been found: ' .
                   implode(array_diff($fields['sku'], array_keys($found)), ', ') , 'WARNING');
            }
            $ret = $this->splitData($file, $fields, $found);
        }
        unset($this->rows);
        return $ret;
    }
}

