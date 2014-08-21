<?php
/**
 * Importer
 * Perform the importation task and output the result to the given logger.
 *
 * @copyright 2012 The Etailers S.L.
 */
class Importer {

    private $_logger = NULL;
    private $_test;
    private $_customDbPath;
    //Fields that should be filtered out if empty, before ingesting data
    public static $unfiltrable = array('price', 'qty');
    public static $remove;

    private static $arrProductSkus;
    private static $arrColumns;

    /**
     * __construct
     *
     * @param Logger $logger instance of the logger
     * @param bool $test tell wether perform or not the data ingestion
     * @param bool $delete if set to true, the empty fields will not be filtered out
     * @param bool $newCategories if set to true, unexistent categories will be allowed (and created)
     * @param $customDbPath bool|string full path of magento xml database file
     * @access public
     * @return void
     */
    public function __construct(Logger $logger, $test = FALSE, $remove = FALSE, $newCategories = FALSE, $customDbPath = false) {
        $this->_logger = $logger;
        $this->_test = $test;
        $this->_newCategories = $newCategories;
        $this->_customDbPath = $customDbPath;
        Importer::$remove = $remove;
        $this->analyzers = array();
        $this->results = '';
        $this->errors = '';
        $this->warnings = '';
        self::$arrProductSkus = array();
        self::$arrColumns = array();
    }

    public static function getColumn($column = null) {
        return $column ? (isset(self::$arrColumns[$column]) ? self::$arrColumns[$column] : array()) : $arrColumns;
    }

    public static function getProductSku(){
        return array_unique(self::$arrProductSkus);
    }

    private function runLexical($files, $mandatory) {

        $lexical = true;
        foreach($files as $action => $file) {
            if (!file_exists($file)) {
                $this->_logger->log("File $file does not exist", 'ERROR');
                die();
            }

            if (!in_array($action, array('import', 'update', 'super'))) {
                $this->_logger->log("Unknown action runLexical: $action", 'ERROR');
                die();
            }
            if(isset($this->analyzers['import'])) {
                $sImport = $this->analyzers['import']->getTokens(false);
                $skusImport = $sImport['sku'];
                unset($sImport);
            } else {
                $skusImport = array();
            }
            $this->analyzers[$action] = new Analyzer($mandatory[$action], $action, $this->_newCategories, $this->_customDbPath, $skusImport);

            $this->_logger->log("Running lexical analyzer: $action  on file: $file");
            $lexical &= $this->analyzers[$action]->analyze(Analyzer::LEXICAL, $file);
            $this->warnings .= $this->analyzers[$action]->getWarnings();
            $this->errors .= $this->analyzers[$action]->getErrors();

            if(!$lexical) {
                break;
            }
        }
        return $lexical;

    }

    private function runSemantic() {
        $semantic = true;

        foreach($this->analyzers as $a => $analyzer) {
            $tokens = $analyzer->getTokens();
            self::$arrProductSkus = array_merge(self::$arrProductSkus, $tokens['sku']);
            self::$arrColumns = array_merge(self::$arrColumns, $tokens);

            $this->_logger->log("Running semantic analyzer " . $a);
            $semantic &= $analyzer->analyze(Analyzer::SEMANTIC, $tokens);
            $this->warnings .= $analyzer->getWarnings();
            $this->errors .= $analyzer->getErrors();
            $this->results .= $analyzer->getResults();
            unset($tokens);
            if(!$semantic) {
                break;
            }
        }
        return $semantic;
    }
    private function showErrors() {
        if ($this->warnings) {
            $this->_logger->log(print_r($this->warnings, TRUE), 'DEBUG');
        }

        if ($this->errors) {
            $this->_logger->log(print_r($this->errors, TRUE), 'ERROR');
        }
    }

    private function ingestData() {

        foreach ($this->analyzers as &$analyzer) {

            $indexes = $analyzer->getColumnIndexes();
            $rows = $analyzer->getRows();

            $this->_logger->log("Importing...");
            $dp = Magmi_DataPumpFactory::getDataPumpInstance('productimport');
            $dp->beginImportSession($analyzer->getProfile() . '_product', 'create', $this->_logger);
            //Configurable products must be imported at the end
            $configurables = array();
            foreach ($rows as $row) {
                //Combine indexes and rows
                $data = array_combine($indexes, $row);
                $item = $this->filterFields($data);
                $type = null;
                
                //Get Type IF NOT EXISTS: configurable or simple to force manage_stock to NO
                if(!isset($data['type'])){
                    $type = $dp->getEngine()->getProductType($item['sku']); 
                    if($type) $data['type'] = $type;
                }

                if($item['visibility'] == 1){ //NVI (Not Visible Individually)
                    if(isset($item['categories'])) $item['categories'] = "";
                    else if(isset($item['category_ids'])) $item['category_ids'] = "";
                }
                
                //Save configurable products and skip ingestion
                if(isset($data['type']) && $item['type'] === 'configurable') {
                    $configurables[] = $item;
                    continue;
                }
                $dp->ingest($item);
            }
            //Configurable products are always ingested at the end
            foreach ($configurables as $item) {
                $dp->ingest($item);
            }

            $dp->endImportSession();

            unset($analyzer);
        }

    }

    /**
     * filterFields
     * Filter out empty fields unless $remove flag is set
     * The $unfiltrable fields shouldn't be filtrable, for security reasons.
     * We allow to unset the special_price by default
     *
     * @param mixed $data
     * @access protected
     * @return void
     */
    protected function filterFields($data) {
        $item = array_filter($data, function ($i) use (&$data) {
            $filter = trim($i) !== '' || (Importer::$remove && !in_array(key($data), Importer::$unfiltrable)) || key($data) === 'special_price';
            next($data);
            return $filter;
        });
        return $item;
    }

    /**
     * run
     * Start the analysis and call magmi if all the validations passed.
     *
     * @param mixed $file the path of the CSV file
     * @param mixed $action allowed values are import, update or super
     * @param array $mandatory contain the fields that must be present on the csv
     * @access public
     * @return void
     */
    public function run($files, $action, array $mandatory) {

        //Retrocompatibily
        if(!is_array($files)) {
            $files = array($action => $files);
            $mandatory = array($action => $mandatory);
        }

        $lexical = $this->runLexical($files, $mandatory);

        if($lexical) {
            $semantic = $this->runSemantic();
            if($semantic) {
                $this->_logger->log(print_r($this->results, TRUE), 'SUCCESS');
                //If test flag is not set, ingest data
                if(!$this->_test) {
                    $this->ingestData();

                //Show an example of the data that would be sent to magmi
                } else {
                    $analyzer = array_pop($this->analyzers);
                    $indexes = $analyzer->getColumnIndexes();
                    $rows = $analyzer->getRows();
                    $this->_logger->log('Example of the data that would be ingested (first row):', 'SUCCESS');
                    for ($i = 0; $i < (isset($_GET['rows']) ? $_GET['rows'] : 1);) {
                        $this->_logger->log(
                            print_r(
                                $this->filterFields(array_combine($indexes, $rows[count($rows) - ++$i])),
                                true),
                            'SUCCESS');
                    }
                    unset($this->analyzers);

                }
            }
        }
        $this->showErrors();

    }

}
