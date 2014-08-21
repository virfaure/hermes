<?php
require_once dirname(__FILE__) . '/analyzer/Analyzer.php';
require_once dirname(__FILE__) . '/inc/HermesHelper.php';
require_once dirname(__FILE__) . '/inc/magmi_defs.php';
require_once dirname(__FILE__) . '/inc/magmi_config.php';
require_once dirname(__FILE__) . '/integration/inc/magmi_datapump.php';
require_once dirname(__FILE__) . '/datapump/Loggers.php';
require_once dirname(__FILE__) . '/datapump/Preprocessor.php';
require_once dirname(__FILE__) . '/datapump/Importer.php';
require_once dirname(__FILE__) . '/queue/config.php';

/**
 * Magento Hermes Importer script
 *
 * @category    Mage
 * @author      Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Hermes_Importer {

    public function __construct() {
    }

    public function __destruct() {

    }

    private function debug($message) {
        $this->log("DEBUG", $message);
    }

    private function log($type, $message) {
        echo "[$type] $message\n";
    }

    private function import($file, $flags) {

        $time = time();
        $this->debug("Import started at " . date('Y/m/d H:i:s'));
        $conf=Magmi_Config::getInstance();
        $this->debug('File: ' . $file);
        $profile = 'import';
        $columnsMandatory = array('sku');
        $preprocessor = new Preprocessor(in_array('debug', $flags));
        $preprocessor->process($file, new ConsoleLogger(in_array('debug', $flags)), in_array('test', $flags), in_array('remove', $flags), in_array('new_categories', $flags));
        $this->debug("Import finished at " . date('Y/m/d H:i:s'));
    }

    /**
     * Run script
     *
     */
    public function run($file = false, $flags = array()) {

        if ($file) {
            if (!file_exists($file)) {
                throw new FileDoesNotExist_Exception($file);
            }
            $flags = explode(',', $flags);
            $this->import($file, $flags);

        } else {
            echo $this->usageHelp();
        }

    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f hermesImporter.php -- [options]

  <file>           Import file
  <flags>          Comma separated list of flags (debug, test, new_categories, etc.)

Example: php -f hermesImporter.php /path/to/file.csv debug,test
USAGE;
    }
}

$shell = new Hermes_Importer();
$shell->run(isset($argv[1])? $argv[1] : false, isset($argv[2])? $argv[2] : false);

class FileDoesNotExist_Exception extends Exception {}
