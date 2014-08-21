<?php
/**
 * Etailers_Marca_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Marca_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Semantic_Marca();
    }

    /**
     * testNoBrand
     * Brand is not mandatory by default
     *
     * @access public
     * @return void
     */
    public function testNoBrand() {
        $input=array();
        $this->assertTrue($this->validator->validate($input));
    }

    /**
     * testCustomSiteBrand
     * Brand is mandatory for customSites
     * @access public
     * @return void
     */
    public function testCustomSiteBrand() {
        $input=array('sku' => array('productwithoutbrand'), 'marca' => array(''));
        $validator = new Semantic_Marca(true);
        $this->assertFalse($validator->validate($input));

        $input['marca'][0] = 'Brand1';
        $this->assertTrue($validator->validate($input));
    }

    /**
     * testMsgError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testMsgError() {
        $input = array('brand1', 'brand2');
        //There must be an error regarding the given input values
        $this->assertContains(implode($input, ','), $this->validator->getErrorMsg($input));
    }
}
