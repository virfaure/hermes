<?php
/**
 * Etailers_TaxClassId_Test 
 * 
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Etailers_TaxClassId_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_TaxClassId();
    }

    protected static $inputs = array(
        array('IVA 21%', true),
        array('IVA 7%', true),
        array('VAT 23%', true),
        array('IVA 23,5%', true),
        array('', false),
        /* Validator disabled
        array('-4', false),
        array('34.,23', false),
        array('asdf', false),
        array('0', false)*/
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testTaxClassIdFormat 
     * Ensure that the format of the TaxClassId is being properly validated.
     * The provided value must follow the format: IVA/VAT digit[digit][,digit]%
     *
     * @dataProvider providerFormat
     * @param mixed $input 
     * @param mixed $result 
     * @access public
     * @return void
     */
    public function testTaxClassIdFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testSuperEmpty 
     * Ensure that Super profile can leave the field empty
     * 
     * @access public
     * @return void
     */
    public function testSuperEmpty() {
        $validator = new Lexical_TaxClassIdSuper();
        $this->assertTrue($validator->validate($input = ''));
    }

    /**
     * testMsgError 
     * Ensure that the proper error is being shown.
     * 
     * @access public
     * @return void
     */
    public function testMsgError() {
        $this->assertFalse($this->validator->validate($input = ''));
        //There must be an error regarding the field is required
        $this->assertContains('required field', $this->validator->getErrorMsg($input));
    }
}
