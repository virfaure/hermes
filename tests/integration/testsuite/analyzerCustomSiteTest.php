<?php
/**
 * Etailers_Analyzer_Custom_Site_Test 
 * 
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Etailers_Analyzer_Custom_Site_Test extends PHPUnit_Framework_TestCase {
    const INPUT_FILE = 'testcustom.csv';
    const INPUT_FILE_BRAND = 'testcustombrand.csv';
    private $_dbFileCustom = 'dbcustom.xml';
    private $_dbFile;

    private static $customSiteFiles = array(array(self::INPUT_FILE), array(self::INPUT_FILE_BRAND));

    private static $mandatory = array('sku', 'qty');
    const ACTION = 'update';

    public function setUp() {
        $this->_dbFile = dirname(__FILE__) . '/../local.xml';

        //Replace the dbname with a custom one
        $customDbFile = file_get_contents($this->_dbFile);
        file_put_contents($this->_dbFileCustom, str_replace('hermestests', reset(Analyzer::$_CUSTOM_SITES), $customDbFile)); 

        $file_content = 'sku, qty, marca, type, simples_skus' . "\n" . 'SK1234, 234, MyBrand, configurable, SK1234Azul';
        file_put_contents(self::INPUT_FILE, $file_content);
        file_put_contents(self::INPUT_FILE_BRAND, str_replace('marca', 'brand', $file_content));
    }

    public function tearDown() {
        unlink(self::INPUT_FILE);
        unlink(self::INPUT_FILE_BRAND);
    }

    /**
     * testNotCustomSite 
     * Ensure that everything goes as expected if it's not a customSite
     *
     * @access public
     * @return void
     */
    public function testNotCustomSite() {
        $this->_analyzer = new Analyzer(self::$mandatory, self::ACTION, false, $this->_dbFile);
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, self::INPUT_FILE);
        $this->assertTrue($lexical);

        $this->assertTrue(strpos($this->_analyzer->getResults(), 'The lexical analysis has successfully passed all validations') !== FALSE);
        $tokens = $this->_analyzer->getTokens();
        //Ensure that the BRAND hasn't been appended to the sku
        $this->assertContains('SK1234', $tokens['sku']);
    }

    /**
     * testCustomSite 
     * Ensure that everything goes as expected if it's a customSite
     * It should work for both fields: brand and marca
     *
     * @dataProvider customSiteFiles
     * @access public
     * @return void
     */
    public function testCustomSite($file) {
        $this->_analyzer = new Analyzer(self::$mandatory, self::ACTION, false, $this->_dbFileCustom);
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, $file);
        $this->assertTrue($lexical);

        $this->assertTrue(strpos($this->_analyzer->getResults(), 'The lexical analysis has successfully passed all validations') !== FALSE);
        $tokens = $this->_analyzer->getTokens();
        //Ensure that the BRAND has been appended to the sku
        $this->assertContains('MYBRAND_SK1234', $tokens['sku']);
    }

    public static function customSiteFiles() {
        return self::$customSiteFiles;
    }
}
