<?php
/**
 * Etailers_Analyzer_Semantic_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Analyzer_Semantic_Test extends PHPUnit_Framework_TestCase {
    const INPUT_FILE = 'semantictest.csv';

    public function setUp() {
    }

    public function tearDown() {
        @unlink(self::INPUT_FILE);
    }

    /**
     * testInvalidSemantic Ensure that the error handling is correct if the provided data
     * is not valid to perform the validation.
     *
     * @access public
     * @return void
     */
    public function testInvalidSemantic() {
        $input = array();
        //Validate that $dblink is being checked
        $asValidator = new Semantic_AttributeSet();
        $ret = $asValidator->validate($input);
        $this->assertFalse($ret);
        $this->assertContains('incomplete data', $asValidator->getErrorMsg($asValidator->getErrors()));

        //Ensure that other fields such as static $QUERY and $FIELD are being checked as well
        $absValidator = new Test_Semantic(true);
        $ret = $absValidator->validate($input);
        $this->assertFalse($ret);
        $this->assertContains('incomplete data', $asValidator->getErrorMsg($asValidator->getErrors()));

    }

    /**
     * testSemanticSku Ensure that the semantic sku validation works fine on the happy case
     *
     * @access public
     * @return void
     */
    public function testValidSemanticSku() {
        $sku = 'MySku';
        $result = (object) array('num_rows' => 1);
        $input = array('input' => array('sku' => array($sku)), 'unique' => array($sku));

        // Create a Mock Object for the mysqli class, expecting query methond to be called
        $dblink = $this->getMock('Mysqli', array('query', 'real_escape_string'));
        $dblink->expects($this->once())
            ->method('query')
            ->with($this->equalTo(sprintf(Semantic_Sku::$QUERY, $sku)))
            ->will($this->returnValue($result));
        $dblink->expects($this->any())
            ->method('real_escape_string')
            ->will($this->returnValue('MySku'));

        $skuValidator = new Semantic_Sku($dblink);
        $ret = $skuValidator->validate($input);
        $this->assertTrue($ret);
    }


    /**
     * testSemanticPrice Ensure the price validator is working fine
     *
     * @access public
     * @return void
     */
    public function testSemanticPrice() {

        $input = array(
            'sku' => array('MySku1'),
            'price' => array(''),
            'type' => array('simple')
        );

        //Ensure that price can't be empty if emptyConfigurables flag is false and no store
        $priceValidator = new Semantic_Price(false);
        $res = $priceValidator->validate($input);
        $this->assertFalse((bool) $res);
        //Neither for configurable products
        $input['type'][0] = 'configurable';
        $res = $priceValidator->validate($input);
        $this->assertContains('numeric value', $priceValidator->getErrorMsg($priceValidator->getErrors()));
        $this->assertFalse((bool) $res);

        //If the store is admin, shouldn't be allowed if flag set to false
        $input['store'] = array(array('es', 'admin'));
        $priceValidator = new Semantic_Price(false);
        $res = $priceValidator->validate($input);
        $this->assertFalse((bool) $res);

        unset($input['store']);
        //If the flag is set to true, the empty price should be allowed
        $priceValidator = new Semantic_Price(true);
        $res = $priceValidator->validate($input);
        $this->assertTrue((bool) $res);



    }


    /**
     * testDuplicatedSemanticSku Ensure that the semantic sku validation works fine for the invalid cases
     *
     * @access public
     * @return void
     */
    public function testDuplicatedSemanticSku() {
        $sku = 'MySku';
        $result = (object) array('num_rows' => 1);
        $input = array('input' => array('sku' => array($sku, $sku)), 'unique' => array($sku));

        // Create a Mock Object for the mysqli class, expecting query methond to be called
        $dblink = $this->getMock('Mysqli', array('query'));

        $skuValidator = new Semantic_Sku($dblink);
        $ret = $skuValidator->validate($input);
        $this->assertFalse($ret);
        $this->assertContains('duplicated', $skuValidator->getErrorMsg($skuValidator->getErrors()));
    }

    /**
     * testDuplicatedStoreSemanticSku Ensure that the semantic sku validation works fine for the invalid cases
     *
     * @access public
     * @return void
     */
    public function testDuplicatedStoreSemanticSku() {
        $sku = 'MySku';
        $result = (object) array('num_rows' => 1);
        $input = array('input' =>
            array(
                'sku' => array($sku, $sku),
                'store' => array(0 => array('admin'), 1 => array('admin'))
            ), 'unique' => array($sku));

        // Create a Mock Object for the mysqli class, expecting query methond to be called
        $dblink = $this->getMock('Mysqli', array('query'));

        $skuValidator = new Semantic_Sku($dblink);
        $ret = $skuValidator->validate($input);
        $this->assertFalse($ret);
        $this->assertContains('Sku + Store must be unique', $skuValidator->getErrorMsg($skuValidator->getErrors()));
    }
    /**
     * testImport Ensure that an update validation works as expected.
     *
     * @access public
     * @return void
     */
    public function testUpdate() {
        $mandatory = array(
        'name', 'sku'/*, 'status', 'visibility', 'description', 'short_description', 'price', 'tax_class_id', 'qty', 'type', 'attribute_set', 'weight'*/);
        $action = 'update';
        $dbFile = dirname(__FILE__) . '/../local.xml';

        $file_content = 'sku, name' . "\n" . //, status, visibility, description, short_description, price, tax_class_id, qty, type, attribute_set, weight, categories' . "\n" .
                        'SK1234, MyProduct'; //, Habilitado, 4, My description, My short description,234.43, IVA 21%, 43, simple, default, 4.000, Moda';
        file_put_contents(self::INPUT_FILE, $file_content);
        $analyzer = new Analyzer($mandatory, $action, false, $dbFile);
        $lexical = $analyzer->analyze(Analyzer::LEXICAL, self::INPUT_FILE);
        $this->assertTrue($lexical);
        $this->assertContains('The lexical analysis has successfully passed all validations', $analyzer->getResults());

        $semantic = $analyzer->analyze(Analyzer::SEMANTIC, $analyzer->getTokens());
        $this->assertFalse($semantic);
        $this->assertContains('Unexpected query result', $analyzer->getErrors());
    }

}
class Test_Semantic extends Abstract_Semantic {
}

