<?php
/**
 * CategoryEngine
 * Perform the importation task and output the result to the given logger.
 *
 * @copyright 2012 The Etailers S.L.
 */
class CategoryEngine {

    private $_logger = NULL;
    private $_test;
     //Fields that should be filtered out if empty, before ingesting data 
    public static $filtrable = array('price', 'qty');
    public static $remove;

    /**
     * __construct 
     * 
     * @param Logger $logger instance of the logger
     * @param bool $test tell wether perform or not the data ingestion
     * @param bool $delete if set to true, the empty fields will not be filtered out
     * @access public
     * @return void
     */
    public function __construct(Logger $logger, $test = FALSE, $remove = FALSE) {
        $conf = Magmi_Config::getInstance();
        $this->_logger = $logger;
        $this->_test = $test;
        CategoryEngine::$remove = $remove;
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
    public function run($file, $action, array $mandatory) {

        if (!file_exists($file)) {
            $this->_logger->log("File $file does not exist", 'ERROR');
            die();
        }

        if (!in_array($action, array('import_category', 'update_category'))) {
            $this->_logger->log("Unknown action: $action", 'ERROR');
            die();
        }

        $analyzer = new Analyzer($mandatory, $action);

        $this->_logger->log("Running lexical analyzer");
        $lexical = $analyzer->analyze(Analyzer::LEXICAL, $file);
        $warnings = $analyzer->getWarnings();
        $errors = $analyzer->getErrors();

        if($lexical) {

        	$this->_logger->log("Running semantic analyzer");
        	$semantic = $analyzer->analyze(Analyzer::SEMANTIC, $analyzer->getTokens());
            $warnings .= $analyzer->getWarnings();
            $errors = $analyzer->getErrors();
            $results = $analyzer->getResults();

            if($semantic) {

                $this->_logger->log(print_r($results, TRUE), 'SUCCESS');

                $indexes = $analyzer->getColumnIndexes();
                $rows = $analyzer->getRows();
                unset($analyzer);

                if(!$this->_test) {
                    $this->_logger->log("Actualizando...");
                    
                    $dp = Magmi_DataPumpFactory::getDataPumpInstance('categoryimport');
                    $dp->beginImportSession('update_category', $action, $this->_logger);
                     
                    foreach ($rows as $key => $row) {
						//Combine indexes and rows
                        $data = array_combine($indexes, $row);
                        
                        //Filter out empty fields
                        $item = array_filter($data, function ($i) use (&$data) {
                            $filter = !empty($i);
                            next($data);
                            return $filter;
                        });
               
                       $dp->ingest($item);
                    }
                    $dp->endImportSession();
                } else {
                    //Show an example of the data that would be sent to magmi
                    $this->_logger->log('Example of the data that would be ingested (first row):', 'SUCCESS');
                    $this->_logger->log(
                            print_r(
                                array_combine($indexes, array_pop($rows)),
                                true),
                        'SUCCESS');
                }
            }
        }

        if ($warnings) {
            $this->_logger->log(print_r($warnings, TRUE), 'WARNING');
        }

        if ($errors) {
            $this->_logger->log(print_r($errors, TRUE), 'ERROR');
        }

    }

}
