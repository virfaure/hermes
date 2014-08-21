<?php
/**
 * Etailers_ConfigurableAttributes_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_ConfigurableAttributes_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_ConfigurableAttributes();
    }

    protected static $inputs = array(
        array('talla, color', 'talla,color'),
        array('TALLA COLOR', 'talla,color'),
        array('(%&$·$"·', ''), //Special characters should be escaped
        array('', '')    //the field might be empty
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testConfigurableAttributesFormat
     * Ensure that the configurable attributes are being properly formated.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $output
     * @access public
     * @return void
     */
    public function testConfigurableAttributesFormat($input, $output) {
        $this->assertTrue($this->validator->validate($input));
        $this->assertEquals($output, $input);
    }

    /**
     * testConfigurableAttributesError Proper error reporting
     *
     * @access public
     * @return void
     */
    public function testConfigurableAttributesError() {
        $input = 'anything';
        $this->assertContains($input, $this->validator->getErrorMsg($input));

    }

    /**
     * testConfigurableAttributesSemanticEmpty
     *
     * @access public
     * @return void
     */
    public function testConfigurableAttributesSemanticEmpty() {
        $method = new ReflectionMethod('Semantic_ConfigurableAttributes', 'validateEmpty');
        $method->setAccessible(true);

        $input = array('type' => array('configurable'), 'sku' => array('sku1'));
        $index = 0;
        $attributes = array();

        //attributess cant be empty
        $this->assertFalse($method->invoke($validator = new Semantic_ConfigurableAttributes, $input, $index, $attributes));
        $this->assertEquals(sprintf(Semantic_ConfigurableAttributes::EMTPY_ERROR, 'sku1'), $validator->getErrorMsg(array()));

        $input['type'][0] = 'simple';
        $this->assertTrue($method->invoke(new Semantic_ConfigurableAttributes, $input, $index, $attributes));

        $input['type'][0] = 'configurable';
        $attributes = array('talla');
        $this->assertTrue($method->invoke(new Semantic_ConfigurableAttributes, $input, $index, $attributes));
    }

    /**
     * testConfigurableAttributesSemanticNotNull
     *
     * @access public
     * @return void
     */
    public function testConfigurableAttributesSemanticNotNull() {
        $method = new ReflectionMethod('Semantic_ConfigurableAttributes', 'validateNotNull');
        $method->setAccessible(true);

        $input = array('type' => array('configurable'), 'sku' => array('sku1'));
        $index = 0;
        $attributes = array('talla');
        $this->assertTrue($method->invoke(new Semantic_ConfigurableAttributes, $input, $index, $attributes));

        $input['type'][0] = 'simple';
        $this->assertFalse($method->invoke($validator = new Semantic_ConfigurableAttributes, $input, $index, $attributes));
        $this->assertEquals(sprintf(Semantic_ConfigurableAttributes::NULL_ERROR, 'talla', 'sku1'), $validator->getErrorMsg(array()));

        $input['talla'] = array();
        $this->assertFalse($method->invoke(new Semantic_ConfigurableAttributes, $input, $index, $attributes));
        $input['talla'][0] = 'XL';
        $this->assertTrue($method->invoke(new Semantic_ConfigurableAttributes, $input, $index, $attributes));
    }

    /**
     * testSemanticExistAndBelong
     * This test should return false as we don't have a testing environment and we have not set up the db connection.
     * TODO: Create a real test once we have a full working testing envirnoment or mock the db object.
     *
     * @param mixed $input
     * @access public
     * @return void
     */
    public function testSemanticExistAndBelong() {
        $method = new ReflectionMethod('Semantic_ConfigurableAttributes', 'validateExistAndBelong');
        $method->setAccessible(true);

        $input = array('type' => array('configurable'), 'sku' => array('sku1'), 'attribute_set' => array('Producto con talla'));
        $index = 0;
        $attributes = array('talla');
        $this->assertFalse($method->invoke($validator = new Semantic_ConfigurableAttributes, $input, $index, $attributes));
        $this->assertEquals(sprintf(Semantic_ConfigurableAttributes::INVALID_ATTRIBUTE_ERROR, 'talla', 'Producto con talla', 'sku1'), $validator->getErrorMsg(array('talla')));
    }

    /**
     * testSemanticExistAndBelong
     * This test should return false as we don't have a testing environment and we have not set up the db connection.
     * TODO: Create a real test once we have a full working testing envirnoment or mock the db object.
     *
     * @param mixed $input
     * @access public
     * @return void
     */
    public function testSemanticValidator() {
        $validator = new Semantic_ConfigurableAttributes();

        $input = array('type' => array('configurable'), 'sku' => array('sku1'), 'attribute_set' => array('Producto con talla'), 'configurable_attributes' => array('talla'));
        $this->assertFalse($validator->validate($input));
        //Should fail at the last step (checking that the attribute exists on the db and belongs to the attribute set.
        $this->assertEquals(sprintf(Semantic_ConfigurableAttributes::INVALID_ATTRIBUTE_ERROR, 'talla', 'Producto con talla', 'sku1'), $validator->getErrorMsg(array('talla')));
    }
}
