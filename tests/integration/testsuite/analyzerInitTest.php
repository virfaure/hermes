<?php
/**
 * Etailers_Analyzer_Init_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Analyzer_Init_Test extends PHPUnit_Framework_TestCase {
    private $_validFile = 'testvalid.csv';
    private $_invalidFile = 'testinvalid.csv';
    private $_dbFile;
    private static $message;

    public function setUp() {
        $mandatory = array('sku', 'qty', 'type');
        $action = 'import';
        $this->_dbFile = dirname(__FILE__) . '/../local.xml';
        $this->_analyzer = new Analyzer($mandatory, $action, false, $this->_dbFile);
        file_put_contents($this->_validFile, 'sku, qty, type, categories, field_not_validated' . "\n" . '1234, 234, simple, Bolso, 2343');
        file_put_contents($this->_invalidFile, 'sku; color; qty' . "\n" . '1234; Rojo; 3');
    }

    public function tearDown() {
        unlink($this->_validFile);
        unlink($this->_invalidFile);
    }

    /**
     * setMessage
     * Helper method to capture exit/die messages
     *
     * @param mixed $message
     * @access public
     * @return void
     */
    public function setMessage($message) {
        self::$message = $message;
    }

    /**
     * testAnalyzerInitialization
     * Ensure that the Analyzer is returning false if the provided file doesn't exist.
     *
     * @access public
     * @return void
     */
    public function testFileNotExist() {
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, 'not exists.csv');
        $this->assertFalse($lexical);
    }

    /**
     * testParseFirstRow
     * Ensure that Analyzer is returning an error if the columns specified aren't present,
     * or if some of the fields are duplicated.
     *
     * @access public
     * @return void
     */
    public function testParseFirstRow() {
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, $this->_invalidFile);
        $this->assertFalse((bool) $lexical);
        //There should be an error regarding the missing columns (type and category)
        $this->assertContains('category',$this->_analyzer->getErrors());
        $this->assertContains('type',$this->_analyzer->getErrors());

        file_put_contents($this->_invalidFile, 'sku; sku; qty' . "\n" . '1234; Rojo; 3');
        $this->_analyzer = new Analyzer(array('sku'), 'update', false, $this->_dbFile);
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, $this->_invalidFile);
        $this->assertFalse((bool) $lexical);
        //There should be an error regarding the duplicated columns (sku)
        $this->assertContains('sku',$this->_analyzer->getErrors());
        $this->assertContains('duplicated',$this->_analyzer->getErrors());
    }

    /**
     * testValidFile
     * Ensure that everything goes as expected if the file is correct.
     *
     * @access public
     * @return void
     */
    public function testValidFile() {
        $lexical = $this->_analyzer->analyze(Analyzer::LEXICAL, $this->_validFile);
        $this->assertTrue($lexical);
        //Ensure the results are the expected.
        $this->assertTrue(strpos($this->_analyzer->getResults(), 'The lexical analysis has successfully passed all validations') !== FALSE);

        //Check the warnings and errors content
        $this->assertContains('Strategy field_not_validated (Lexical_FieldNotValidated) is not implemented for the lexical analizer', $this->_analyzer->getWarnings());
        $this->assertEquals('', $this->_analyzer->getErrors());

        //Ensure the rows have been properly generated
        $this->assertTrue(count($this->_analyzer->getRows()) == 1);
        //Check the tokens
        $tokens = $this->_analyzer->getTokens();
        $this->assertEquals(5, count($tokens));
        $this->assertTrue(in_array('2343', array_pop($tokens)));

        //Check the column indexes retrieval
        $this->assertContains('sku', $this->_analyzer->getColumnIndexes());
        $this->assertContains('qty', $this->_analyzer->getColumnIndexes());
        $this->assertContains('field_not_validated', $this->_analyzer->getColumnIndexes());
    }

    /**
     * testDelimiter
     * Ensure that the analyzer is able to retrieve the guess the proper delimiter for each CSV
     *
     * @access public
     * @return void
     */
    public function testDelimiter() {
        $this->assertEquals(',', Analyzer::getDelimiter($this->_validFile));
        $this->assertEquals(';', Analyzer::getDelimiter($this->_invalidFile));
        file_put_contents($this->_invalidFile, 'hola;adios,invalid,csv');

        //If delimiter not found, the app should exit with an error.
        set_exit_overload(function($message) { Etailers_Analyzer_Init_Test::setMessage($message); return FALSE; });
        Analyzer::getDelimiter($this->_invalidFile);
        unset_exit_overload();
        $this->assertEquals('Unable to find the CSV delimiter character. Make sure you use "," or ";" as delimiter and try again.', self::$message);
    }

    /**
     * testInvalidProfile
     * Ensure that if an invalid profile is selected, the execution will end.
     *
     * @access public
     * @return void
     */
    public function testInvalidProfile() {
        set_exit_overload(function($message) { Etailers_Analyzer_Init_Test::setMessage($message); return FALSE; });
        $analyzer = new Analyzer(array(), 'invalid', false, $this->_dbFile);
        unset_exit_overload();
        $this->assertEquals('Unknown profile specified.', self::$message);

    }

    /**
     * testImportSkus
     * Check that the import skus are being properly stored if given on the constructor.
     *
     * @access public
     * @return void
     */
    public function testImportSkus() {
        $analyzer = new Analyzer(array(), 'update', false, $this->_dbFile, array('MySku'));

        $this->assertContains('MySku', $analyzer->getSkusImport());

    }

    /**
     * testErrors
     * Ensure that the errors are being properly detected and showed.
     *
     * @access public
     * @return void
     */
    public function testErrors() {
        $analyzer = new Analyzer(array(), 'update', false, $this->_dbFile);
        //Generate invalid file
        file_put_contents($this->_invalidFile, str_replace('simple', 'invalid', file_get_contents($this->_validFile)));

        //Execute analyzer and check results
        $lexical = $analyzer->analyze(Analyzer::LEXICAL, $this->_invalidFile);
        $this->assertFalse($lexical);
        $this->assertContains('Error found at item 1234:', $analyzer->getErrors());
        $this->assertContains('Column: type, allowed values: (simple|configurable), but "invalid" provided.', $analyzer->getErrors());
    }

    /**
     * testInvalidRows ensure that if a CSV has empty columns on the header, its rows are being skipped as well, no matter its content
     *
     * @access public
     * @return void
     */
    public function testInvalidRows() {
        $analyzer = new Analyzer(array(), 'update', false, $this->_dbFile);

        //Generate invalid file
        $data = 'sku, type,,,,,,,,,,,,,,,' . "\n" . '1234, simple, this, content, should, be, skipped';
        file_put_contents($this->_invalidFile, $data);

        //Execute analyzer and check results
        $lexical = $analyzer->analyze(Analyzer::LEXICAL, $this->_invalidFile);
        //The validation should succeed
        $this->assertTrue($lexical);
        //There should be only two column indexes (sky and type) and its content, the rest should have been skiped
        $this->assertEquals(2, count($analyzer->getColumnIndexes()));
        $this->assertEquals(2, count($analyzer->getTokens()));
    }
}
